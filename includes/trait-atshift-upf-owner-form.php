<?php
/**
 * Owner-facing profile form integration.
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes a server-side form contract for registration and self-service screens.
 *
 * The caller remains responsible for authorizing registration or access to the
 * target user account.
 */
trait Atshift_UPF_Owner_Form {
	/**
	 * Return the versioned owner-form contract.
	 *
	 * @return array<string, mixed>
	 */
	public function owner_form_api() {
		$api = array( 'version' => 1 );

		foreach ( array( 'fields', 'render', 'validate', 'save', 'values', 'display' ) as $method ) {
			$api[ $method ] = array( $this, 'owner_form_' . $method );
		}

		return $api;
	}

	/**
	 * Return a validated add-on adapter for a field type.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return array<string, callable>|null
	 */
	private function owner_field_adapter( $field ) {
		$adapters = apply_filters( 'atshift_upf_owner_field_adapters', array() );
		$type     = isset( $field['type'] ) ? (string) $field['type'] : '';
		$adapter  = isset( $adapters[ $type ] ) ? $adapters[ $type ] : null;

		if ( ! is_array( $adapter ) ) {
			return null;
		}

		foreach ( array( 'validate', 'save', 'values', 'display' ) as $method ) {
			if ( ! isset( $adapter[ $method ] ) || ! is_callable( $adapter[ $method ] ) ) {
				return null;
			}
		}

		return $adapter;
	}

	/**
	 * Map supported WordPress profile field types to WP_User properties.
	 *
	 * @return array<string, string>
	 */
	private function owner_core_map() {
		return array(
			'core_first_name'   => 'first_name',
			'core_last_name'    => 'last_name',
			'core_nickname'     => 'nickname',
			'core_display_name' => 'display_name',
			'core_website'      => 'user_url',
			'core_bio'          => 'description',
		);
	}

	/**
	 * Determine whether a field is a layout container.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function owner_structure( $field ) {
		return in_array( isset( $field['type'] ) ? $field['type'] : '', array( 'group', 'box', 'accordion' ), true );
	}

	/**
	 * Build the ordered owner-editable field plan.
	 *
	 * Child fields are never promoted when their parent is excluded. Cyclic
	 * ancestry and empty layout containers are removed.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function owner_form_plan() {
		if ( ! $this->is_field_group_enabled() ) {
			return array();
		}

		$all   = Atshift_UPF_Plugin::get_enabled_fields();
		$map   = $this->owner_core_map();
		$types = array_merge(
			array_keys( $map ),
			array( 'text', 'textarea', 'email', 'url', 'phone', 'number', 'checkbox', 'radio', 'select', 'image', 'additional_name', 'passkeys', 'group', 'box', 'accordion', 'conditional' )
		);
		$plan  = array();

		foreach ( $all as $field ) {
			$type = isset( $field['type'] ) ? (string) $field['type'] : '';
			$key  = isset( $field['key'] ) ? (string) $field['key'] : '';

			if ( 'passkeys' === $type && ( ! shortcode_exists( 'atshift_passkey_profile' ) || ! apply_filters( 'atshift_freeform_login_passkeys_available', false ) ) ) {
				continue;
			}

			if ( ! preg_match( '/^[a-z0-9_\-]+$/D', $key ) || ( ! in_array( $type, $types, true ) && ! $this->owner_field_adapter( $field ) ) || ! empty( $field['admin_only'] ) || ! empty( $field['disabled'] ) ) {
				continue;
			}

			// Membership forms use the administrator's field layout directly.
			// Shortcode visibility rules and admin-screen role controls do not apply.
			$plan[ $key ] = $field;
		}

		do {
			$before = count( $plan );
			$ids    = array_column( $plan, 'id' );

			foreach ( $plan as $key => $field ) {
				if ( ! empty( $field['parent_id'] ) && ! in_array( $field['parent_id'], $ids, true ) ) {
					unset( $plan[ $key ] );
				}
			}

			$parents = array_column( $plan, 'parent_id' );
			foreach ( $plan as $key => $field ) {
				if ( $this->owner_structure( $field ) && ! in_array( isset( $field['id'] ) ? $field['id'] : '', $parents, true ) ) {
					unset( $plan[ $key ] );
				}
			}
		} while ( count( $plan ) !== $before );

		$by_id = array_column( $plan, null, 'id' );
		foreach ( $plan as $key => $field ) {
			$seen = array();

			while ( ! empty( $field['parent_id'] ) ) {
				$parent_id = (string) $field['parent_id'];
				if ( isset( $seen[ $parent_id ] ) || ! isset( $by_id[ $parent_id ] ) ) {
					unset( $plan[ $key ] );
					break;
				}

				$seen[ $parent_id ] = true;
				$field              = $by_id[ $parent_id ];
			}
		}

		return $plan;
	}

	/**
	 * Return owner-form fields, optionally constrained by a trusted allowlist.
	 *
	 * @param array<int, string>|null $allowed Trusted field keys, or null for all.
	 * @return array<string, array<string, mixed>>
	 */
	public function owner_form_fields( $allowed = null ) {
		$fields = $this->owner_form_plan();

		if ( null === $allowed ) {
			return $fields;
		}

		return array_intersect_key( $fields, array_flip( (array) $allowed ) );
	}

	/**
	 * Read values for an owner-form field set.
	 *
	 * @param int                $user_id User ID.
	 * @param array<int, string> $allowed Trusted field keys.
	 * @return array<string, mixed>
	 */
	public function owner_form_values( $user_id, $allowed ) {
		$user   = get_userdata( $user_id );
		$values = array();
		$map    = $this->owner_core_map();
		$fields = $this->owner_form_fields( $allowed );

		if ( ! $user ) {
			return $values;
		}

		foreach ( $fields as $key => $field ) {
			$type = isset( $field['type'] ) ? $field['type'] : '';

			if ( $this->owner_structure( $field ) || 'passkeys' === $type ) {
				continue;
			}

			$adapter = $this->owner_field_adapter( $field );
			if ( $adapter ) {
				$values[ $key ] = call_user_func( $adapter['values'], $user_id, $field );
				continue;
			}

			$values[ $key ] = isset( $map[ $type ] ) ? (string) $user->{$map[ $type ]} : (string) get_user_meta( $user_id, $this->meta_key( $key ), true );
		}

		foreach ( $fields as $key => $field ) {
			if ( 'additional_name' !== ( isset( $field['type'] ) ? $field['type'] : '' ) ) {
				continue;
			}

			$values[ $key ] = array(
				'value' => isset( $values[ $key ] ) ? $values[ $key ] : '',
				'kind'  => $this->sanitize_additional_name_type( get_user_meta( $user_id, $this->meta_key( $key . '_type' ), true ) ),
			);
		}

		return $values;
	}

	/**
	 * Enqueue owner-form assets.
	 *
	 * @param bool $include_script Whether to enqueue conditional-form behavior.
	 * @return void
	 */
	private function owner_form_assets( $include_script ) {
		wp_enqueue_style( 'atshift-upf-owner-form', ATSHIFT_UPF_URL . 'assets/owner-form.css', array(), $this->asset_version( 'assets/owner-form.css' ) );

		if ( $include_script ) {
			wp_enqueue_script( 'atshift-upf-owner-form', ATSHIFT_UPF_URL . 'assets/owner-form.js', array(), $this->asset_version( 'assets/owner-form.js' ), true );
		}
	}

	/**
	 * Render editable owner fields.
	 *
	 * @param array<int, string>   $allowed Trusted field keys.
	 * @param array<string, mixed> $values Current values.
	 * @return void
	 */
	public function owner_form_render( $allowed, $values = array() ) {
		$this->owner_form_assets( true );
		echo '<div class="atshift-upf-owner-form">';
		$this->owner_form_nodes( $this->owner_form_fields( $allowed ), '', $values, false );
		echo '</div>';
	}

	/**
	 * Render read-only owner fields.
	 *
	 * @param int                $user_id User ID.
	 * @param array<int, string> $allowed Trusted field keys.
	 * @return void
	 */
	public function owner_form_display( $user_id, $allowed ) {
		$this->owner_form_assets( false );
		echo '<div class="atshift-upf-owner-form">';
		$this->owner_form_nodes( $this->owner_form_fields( $allowed ), '', $this->owner_form_values( $user_id, $allowed ), true );
		echo '</div>';
	}

	/**
	 * Render a level of the owner-form tree.
	 *
	 * @param array<string, array<string, mixed>> $fields Field plan.
	 * @param string                              $parent Parent field ID.
	 * @param array<string, mixed>                $values Current values.
	 * @param bool                                $display Whether this is read-only output.
	 * @return void
	 */
	private function owner_form_nodes( $fields, $parent, $values, $display ) {
		foreach ( $fields as $key => $field ) {
			if ( (string) ( isset( $field['parent_id'] ) ? $field['parent_id'] : '' ) !== (string) $parent ) {
				continue;
			}

			if ( $display && ! $this->field_matches_submitted_conditions( $field, $fields, $values ) ) {
				continue;
			}

			$type = isset( $field['type'] ) ? $field['type'] : 'text';
			$id   = isset( $field['id'] ) ? $field['id'] : $key;

			echo '<div class="atshift-upf-owner-node atshift-upf-owner-' . esc_attr( $type ) . '" data-owner-id="' . esc_attr( $id ) . '" data-owner-parent="' . esc_attr( $parent ) . '" data-owner-choice="' . esc_attr( isset( $field['conditional_value'] ) ? $field['conditional_value'] : '' ) . '">';

			if ( 'passkeys' === $type ) {
				$this->owner_form_passkeys( $field, $display );
			} elseif ( $this->owner_structure( $field ) ) {
				if ( 'accordion' === $type ) {
					echo '<details open><summary>' . esc_html( isset( $field['label'] ) ? $field['label'] : '' ) . '</summary>';
				} else {
					echo '<h3>' . esc_html( isset( $field['label'] ) ? $field['label'] : '' ) . '</h3>';
				}

				echo '<div class="atshift-upf-owner-children">';
				$this->owner_form_nodes( $fields, $id, $values, $display );
				echo '</div>';

				if ( 'accordion' === $type ) {
					echo '</details>';
				}
			} else {
				$this->owner_form_field( $key, $field, isset( $values[ $key ] ) ? $values[ $key ] : '', $display );

				if ( 'conditional' === $type ) {
					$this->owner_form_nodes( $fields, $id, $values, $display );
				}
			}

			echo '</div>';
		}
	}

	/**
	 * Render the optional passkey placement.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param bool                 $display Whether this is read-only output.
	 * @return void
	 */
	private function owner_form_passkeys( $field, $display ) {
		$label = ! empty( $field['label'] ) ? $field['label'] : __( 'Passkey registration and management', 'atshift-user-profile-fields' );

		echo '<section id="asm-passkeys"><h3>' . esc_html( $label ) . '</h3>';
		if ( ! get_current_user_id() ) {
			echo '<p>' . esc_html__( 'After registration, you can add a passkey from the account edit screen.', 'atshift-user-profile-fields' ) . '</p>';
		} elseif ( $display ) {
			echo '<p>' . esc_html__( 'You can add and manage passkeys from the account edit screen.', 'atshift-user-profile-fields' ) . '</p>';
		} elseif ( apply_filters( 'atshift_freeform_login_passkey_profile_available', false ) ) {
			echo do_shortcode( '[atshift_passkey_profile heading="false"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted integration shortcode owns its escaped markup.
		}
		echo '</section>';
	}

	/**
	 * Render one editable or read-only field.
	 *
	 * @param string               $key Field key.
	 * @param array<string, mixed> $field Field definition.
	 * @param mixed                $value Current value.
	 * @param bool                 $display Whether this is read-only output.
	 * @return void
	 */
	private function owner_form_field( $key, $field, $value, $display ) {
		$type     = isset( $field['type'] ) ? $field['type'] : 'text';
		$label    = isset( $field['label'] ) ? $field['label'] : $key;
		$required = ! $display && $this->is_required_field( $field ) ? ' ' . __( '(required)', 'atshift-user-profile-fields' ) : '';

		echo '<label for="atshift_upf_' . esc_attr( $key ) . '">' . esc_html( $label . $required ) . '</label>';

		$adapter = $this->owner_field_adapter( $field );
		if ( $display && $adapter ) {
			echo '<p>' . esc_html( call_user_func( $adapter['display'], $value, $field ) ) . '</p>';
			return;
		}

		if ( $display ) {
			$display_value = is_array( $value ) ? ( isset( $value['value'] ) ? $value['value'] : '' ) : (string) $value;
			echo '<p>' . nl2br( esc_html( $display_value ) ) . '</p>';
			return;
		}

		$input_field = $field;
		$core_types  = array(
			'core_first_name'   => 'text',
			'core_last_name'    => 'text',
			'core_nickname'     => 'text',
			'core_display_name' => 'text',
			'core_website'      => 'url',
			'core_bio'          => 'textarea',
		);

		if ( isset( $core_types[ $type ] ) ) {
			$input_field['type'] = $core_types[ $type ];
		}

		if ( 'image' === $type ) {
			$input_field['type']        = 'url';
			$input_field['placeholder'] = 'https://example.com/image.jpg';
		}

		if ( 'additional_name' === $type ) {
			echo '<select name="asm_fields[' . esc_attr( $key ) . '][kind]" aria-label="' . esc_attr__( 'Name type', 'atshift-user-profile-fields' ) . '">';
			foreach ( $this->get_additional_name_type_options() as $kind => $kind_label ) {
				echo '<option value="' . esc_attr( $kind ) . '" ' . selected( isset( $value['kind'] ) ? $value['kind'] : '', $kind, false ) . '>' . esc_html( $kind_label ) . '</option>';
			}
			echo '</select>';

			$input_field['type'] = 'text';
			$this->render_input( $input_field, 'asm_fields[' . $key . '][value]', isset( $value['value'] ) ? $value['value'] : '', null );
		} else {
			$this->render_input( $input_field, 'asm_fields[' . $key . ']', $value, get_current_user_id() ? wp_get_current_user() : null );
		}

		if ( ! empty( $field['description'] ) ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}
	}

	/**
	 * Validate owner-form input against the trusted field allowlist.
	 *
	 * @param array<int, string>   $allowed Trusted field keys.
	 * @param array<string, mixed> $input Submitted profile values.
	 * @return array<string, mixed>|WP_Error
	 */
	public function owner_form_validate( $allowed, $input ) {
		$fields   = $this->owner_form_fields( $allowed );
		$values   = array();
		$writable = array_filter(
			$fields,
			function ( $field ) {
				return ! $this->owner_structure( $field ) && 'passkeys' !== ( isset( $field['type'] ) ? $field['type'] : '' );
			}
		);

		if ( ! is_array( $input ) || array_diff( array_keys( $input ), array_keys( $writable ) ) ) {
			return new WP_Error( 'owner_fields', __( 'The submitted profile contains fields that are not allowed.', 'atshift-user-profile-fields' ) );
		}

		foreach ( $writable as $key => $field ) {
			$adapter = $this->owner_field_adapter( $field );
			if ( $adapter ) {
				$value = call_user_func( $adapter['validate'], isset( $input[ $key ] ) ? $input[ $key ] : array(), $field );
				if ( is_wp_error( $value ) ) {
					return $value;
				}
				$values[ $key ] = $value;
				continue;
			}

			$type = isset( $field['type'] ) ? $field['type'] : 'text';
			$raw  = isset( $input[ $key ] ) ? $input[ $key ] : '';

			if ( 'additional_name' === $type ) {
				$options = $this->get_additional_name_type_options();
				$default = array_key_first( $options );
				$raw     = isset( $input[ $key ] ) ? $input[ $key ] : array( 'value' => '', 'kind' => $default );

				if ( ! is_array( $raw ) || array_diff( array_keys( $raw ), array( 'value', 'kind' ) ) || ! is_string( isset( $raw['value'] ) ? $raw['value'] : null ) || ! is_string( isset( $raw['kind'] ) ? $raw['kind'] : null ) || ! array_key_exists( $raw['kind'], $options ) || strlen( $raw['value'] ) > 10000 ) {
					return new WP_Error( 'owner_name', __( 'The name format is invalid.', 'atshift-user-profile-fields' ) );
				}

				$values[ $key ] = array(
					'value' => sanitize_text_field( $raw['value'] ),
					'kind'  => $raw['kind'],
				);
				continue;
			}

			if ( ! is_string( $raw ) || strlen( $raw ) > 10000 ) {
				return new WP_Error( 'owner_value', __( 'A profile field has an invalid format or length.', 'atshift-user-profile-fields' ) );
			}

			if ( '' !== $raw && in_array( $type, array( 'select', 'radio', 'conditional' ), true ) && ! in_array( $raw, isset( $field['choices'] ) ? $field['choices'] : array(), true ) ) {
				return new WP_Error( 'owner_choice', __( 'A selected value is invalid.', 'atshift-user-profile-fields' ) );
			}

			if ( '' !== $raw && in_array( $type, array( 'core_website', 'image' ), true ) && ( ! preg_match( '#^https?://#i', $raw ) || ! filter_var( $raw, FILTER_VALIDATE_URL ) ) ) {
				return new WP_Error( 'owner_url', __( 'Enter a valid HTTP or HTTPS URL.', 'atshift-user-profile-fields' ) );
			}

			$values[ $key ] = $this->sanitize_value( $raw, $field );
		}

		foreach ( $writable as $key => $field ) {
			if ( ! $this->field_matches_submitted_conditions( $field, $fields, $values ) ) {
				continue;
			}

			$adapter = $this->owner_field_adapter( $field );
			if ( $adapter ) {
				if ( $this->is_required_field( $field ) && empty( $values[ $key ] ) ) {
					return new WP_Error(
						'owner_required',
						sprintf(
							/* translators: %s: Profile field label. */
							__( '%s is required.', 'atshift-user-profile-fields' ),
							isset( $field['label'] ) ? $field['label'] : $key
						)
					);
				}
				continue;
			}

			$value = is_array( $values[ $key ] ) ? $values[ $key ]['value'] : $values[ $key ];
			$type  = isset( $field['type'] ) ? $field['type'] : 'text';

			if ( $this->is_required_field( $field ) && ( $this->is_empty_required_value( $value ) || ( 'checkbox' === $type && empty( $value ) ) ) ) {
				return new WP_Error(
					'owner_required',
					sprintf(
						/* translators: %s: Profile field label. */
						__( '%s is required.', 'atshift-user-profile-fields' ),
						isset( $field['label'] ) ? $field['label'] : $key
					)
				);
			}

			if ( '' !== $value && in_array( $type, array( 'select', 'radio', 'conditional' ), true ) && ! in_array( $value, isset( $field['choices'] ) ? $field['choices'] : array(), true ) ) {
				return new WP_Error( 'owner_choice', __( 'A selected value is invalid.', 'atshift-user-profile-fields' ) );
			}

			$raw_value = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ]['value'] : ( isset( $input[ $key ] ) ? $input[ $key ] : '' );
			if ( '' !== $value && $this->should_validate_format( $field ) && ! $this->is_valid_format( $raw_value, $field ) ) {
				return new WP_Error( 'owner_format', $this->get_format_error_message( $field ) );
			}

			$errors = new WP_Error();
			do_action( 'atshift_upf_validate_public_profile_field', $errors, $field, $value );
			if ( $errors->has_errors() ) {
				return $errors;
			}
		}

		$active = array();
		foreach ( $writable as $key => $field ) {
			if ( $this->field_matches_submitted_conditions( $field, $fields, $values ) ) {
				$active[ $key ] = $values[ $key ];
			}
		}

		return $active;
	}

	/**
	 * Save validated owner-form values.
	 *
	 * This is not an HTTP endpoint. The caller must authorize creation or editing
	 * of this exact user before invoking the callback.
	 *
	 * @param int                  $user_id User ID.
	 * @param array<int, string>   $allowed Trusted field keys.
	 * @param array<string, mixed> $input Submitted profile values.
	 * @return true|WP_Error
	 */
	public function owner_form_save( $user_id, $allowed, $input ) {
		if ( ! get_userdata( $user_id ) ) {
			return new WP_Error( 'owner_user', __( 'The requested user could not be found.', 'atshift-user-profile-fields' ) );
		}

		$values = $this->owner_form_validate( $allowed, $input );
		if ( is_wp_error( $values ) ) {
			return $values;
		}

		$fields = $this->owner_form_fields( $allowed );
		$map    = $this->owner_core_map();
		$core   = array( 'ID' => (int) $user_id );

		foreach ( $values as $key => $value ) {
			$type = $fields[ $key ]['type'];
			if ( isset( $map[ $type ] ) ) {
				$core[ $map[ $type ] ] = $value;
			}
		}

		if ( count( $core ) > 1 ) {
			$saved = wp_update_user( wp_slash( $core ) );
			if ( is_wp_error( $saved ) ) {
				return $saved;
			}
		}

		foreach ( $values as $key => $value ) {
			$type = $fields[ $key ]['type'];
			if ( isset( $map[ $type ] ) ) {
				continue;
			}

			$adapter = $this->owner_field_adapter( $fields[ $key ] );
			if ( $adapter ) {
				$saved = call_user_func( $adapter['save'], $user_id, $value, $fields[ $key ] );
				if ( is_wp_error( $saved ) ) {
					return $saved;
				}
				continue;
			}

			if ( is_array( $value ) ) {
				$kind_meta = $this->meta_key( $key . '_type' );
				if ( false === update_user_meta( $user_id, $kind_meta, $value['kind'] ) && get_user_meta( $user_id, $kind_meta, true ) !== $value['kind'] ) {
					return new WP_Error( 'owner_save', __( 'The profile could not be saved.', 'atshift-user-profile-fields' ) );
				}
				$value = $value['value'];
			}

			$meta_key = $this->meta_key( $key );
			if ( false === update_user_meta( $user_id, $meta_key, wp_slash( $value ) ) && (string) get_user_meta( $user_id, $meta_key, true ) !== $value ) {
				return new WP_Error( 'owner_save', __( 'The profile could not be saved.', 'atshift-user-profile-fields' ) );
			}
		}

		do_action( 'atshift_upf_public_profile_saved', $user_id, array_keys( $values ) );
		return true;
	}
}
