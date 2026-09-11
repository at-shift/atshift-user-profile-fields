<?php
/**
 * Public profile integration contract.
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes a versioned, server-side profile contract.
 *
 * This contract is not an HTTP endpoint. Transport authorization remains the
 * responsibility of the caller.
 */
trait Atshift_UPF_Public_Profile {
	/**
	 * Provide the profile integration API.
	 *
	 * @param mixed $previous Previously filtered API value.
	 * @return array<string, mixed>
	 */
	public function public_profile_api( $previous ) {
		unset( $previous );

		return array(
			'version'         => 1,
			'owner_form'      => $this->owner_form_api(),
			'fields'          => array( $this, 'public_profile_fields' ),
			'render'          => array( $this, 'public_profile_render' ),
			'validate'        => array( $this, 'public_profile_validate' ),
			'save'            => array( $this, 'public_profile_save' ),
			'values'          => array( $this, 'public_profile_values' ),
			'form_schema'     => array( $this, 'public_profile_form_schema' ),
			'settings_fields' => array( $this, 'public_profile_settings_fields' ),
		);
	}

	/**
	 * Return configuration labels and integration status without user values.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function public_profile_settings_fields() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array();
		}

		$rows     = array();
		$active   = $this->owner_form_fields();
		$excluded = array( 'core_username', 'core_notification', 'core_role', 'core_admin_color', 'core_language', 'core_visual_editor', 'core_syntax_highlighting', 'core_keyboard_shortcuts', 'core_toolbar', 'core_sessions', 'core_application_passwords' );

		foreach ( Atshift_UPF_Plugin::get_enabled_fields() as $field ) {
			$type   = isset( $field['type'] ) ? $field['type'] : '';
			$key    = isset( $field['key'] ) ? $field['key'] : '';
			$status = '';
			$reason = '';

			if ( in_array( $type, $excluded, true ) || ! empty( $field['admin_only'] ) || ! empty( $field['disabled'] ) ) {
				$status = 'excluded';
				$reason = __( 'Excluded', 'atshift-user-profile-fields' );
			} elseif ( in_array( $type, array( 'core_email', 'core_password', 'core_submit_button' ), true ) ) {
				$status = 'managed';
				$reason = __( 'Managed by Members', 'atshift-user-profile-fields' );
			} elseif ( 'passkeys' === $type ) {
				$status = isset( $active[ $key ] ) ? 'passkey' : 'passkey-disconnected';
				$reason = isset( $active[ $key ] ) ? __( 'Configure after registration', 'atshift-user-profile-fields' ) : __( 'Integration unavailable', 'atshift-user-profile-fields' );
			} elseif ( 'pro_organization_groups' === $type && ! $this->owner_field_adapter( $field ) ) {
				$status = 'disconnected';
				$reason = __( 'Integration unavailable', 'atshift-user-profile-fields' );
			} elseif ( ! isset( $active[ $key ] ) ) {
				$status = $this->owner_structure( $field ) ? 'excluded' : 'unsupported';
				$reason = 'excluded' === $status ? __( 'Excluded', 'atshift-user-profile-fields' ) : __( 'Integration not implemented', 'atshift-user-profile-fields' );
			}

			$rows[] = array(
				'label'                 => (string) ( isset( $field['label'] ) ? $field['label'] : $key ),
				'status'                => $status,
				'status_label'          => $reason,
				'classification_linked' => 'pro_organization_groups' === $type && isset( $active[ $key ] ),
			);
		}

		return $rows;
	}

	/**
	 * Describe the legacy flat profile form contract.
	 *
	 * @return array<string, mixed>
	 */
	public function public_profile_form_schema() {
		$standard = array( 'first_name', 'last_name', 'nickname', 'display_name', 'user_url', 'bio' );
		$settings = Atshift_UPF_Plugin::get_settings();

		if ( ! $this->is_field_group_enabled() ) {
			return array(
				'configured' => false,
				'standard'   => array(),
				'custom'     => array(),
			);
		}

		$map = array(
			'core_first_name'   => 'first_name',
			'core_last_name'    => 'last_name',
			'core_nickname'     => 'nickname',
			'core_display_name' => 'display_name',
			'core_website'      => 'user_url',
			'core_bio'          => 'bio',
		);

		$hidden = array_map(
			static function ( $key ) {
				return 'website' === $key ? 'user_url' : $key;
			},
			(array) $settings['hidden_core_fields']
		);
		$native = array();

		foreach ( $standard as $key ) {
			if ( ! in_array( $key, $hidden, true ) ) {
				$native[ $key ] = array();
			}
		}

		$all    = Atshift_UPF_Plugin::get_enabled_fields();
		$custom = $this->public_profile_fields( array_column( $all, 'key' ) );
		foreach ( $custom as $key => $field ) {
			if ( ! $this->public_profile_owner_field( $field ) ) {
				unset( $custom[ $key ] );
			}
		}

		$core_configured = false;
		foreach ( $all as $field ) {
			$type = isset( $field['type'] ) ? $field['type'] : '';
			if ( ! isset( $map[ $type ] ) ) {
				continue;
			}

			$core_configured = true;
			$key             = $map[ $type ];
			unset( $native[ $key ] );

			if ( $this->public_profile_owner_field( $field ) ) {
				$native[ $key ] = array( 'label' => isset( $field['label'] ) ? $field['label'] : '' );
			}
		}

		$ordered     = array();
		$represented = array();
		foreach ( $all as $field ) {
			$type = isset( $field['type'] ) ? $field['type'] : '';
			$key  = isset( $field['key'] ) ? $field['key'] : '';
			$core = isset( $map[ $type ] ) ? $map[ $type ] : '';

			if ( $core && isset( $native[ $core ] ) ) {
				$ordered[]     = 'wp_' . $core;
				$represented[] = $core;
			} elseif ( isset( $custom[ $key ] ) ) {
				$ordered[] = 'upf_' . $key;
			}
		}

		$first = array_map(
			static function ( $key ) {
				return 'wp_' . $key;
			},
			array_diff( array_keys( $native ), $represented )
		);

		return array(
			'configured' => ! empty( $custom ) || $core_configured || (bool) array_intersect( $standard, $hidden ),
			'standard'   => $native,
			'custom'     => array_keys( $custom ),
			'order'      => array_values( array_unique( array_merge( $first, $ordered ) ) ),
		);
	}

	/**
	 * Determine whether a legacy flat-contract field can be owner edited.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function public_profile_owner_field( $field ) {
		if ( ! empty( $field['parent_id'] ) || ! empty( $field['admin_only'] ) || ! empty( $field['disabled'] ) || ! empty( $field['role_control_roles'] ) ) {
			return false;
		}

		// Persisted Pro rules still apply when the add-on is temporarily unavailable.
		if ( isset( $field['pro'] ) && ( empty( $field['pro']['show_frontend_form'] ) || empty( $field['pro']['owner_editable'] ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return simple custom fields from a trusted server-side allowlist.
	 *
	 * @param array<int, string> $allowed Trusted custom field keys.
	 * @return array<string, array<string, mixed>>
	 */
	public function public_profile_fields( $allowed ) {
		if ( ! $this->is_field_group_enabled() ) {
			return array();
		}

		$types  = array( 'text', 'textarea', 'email', 'url', 'phone', 'number', 'checkbox', 'radio', 'select' );
		$result = array();

		foreach ( Atshift_UPF_Plugin::get_enabled_fields() as $field ) {
			$key  = isset( $field['key'] ) ? $field['key'] : '';
			$type = isset( $field['type'] ) ? $field['type'] : '';

			if ( ! in_array( $key, $allowed, true ) || ! in_array( $type, $types, true ) ) {
				continue;
			}

			// Conditional and grouped fields require the owner-form contract.
			if ( ! empty( $field['parent_id'] ) || ! empty( $field['admin_only'] ) || ! empty( $field['disabled'] ) || ! preg_match( '/^[a-z0-9_\-]+$/D', $key ) ) {
				continue;
			}

			$result[ $key ] = $field;
		}

		return $result;
	}

	/**
	 * Render simple legacy-contract fields.
	 *
	 * @param array<int, string>   $allowed Trusted field keys.
	 * @param array<string, mixed> $values Current values.
	 * @return void
	 */
	public function public_profile_render( $allowed, $values = array() ) {
		foreach ( $this->public_profile_fields( $allowed ) as $key => $field ) {
			echo '<p><label for="atshift_upf_' . esc_attr( $key ) . '">' . esc_html( isset( $field['label'] ) ? $field['label'] : $key );
			if ( $this->is_required_field( $field ) ) {
				echo ' ' . esc_html__( '(required)', 'atshift-user-profile-fields' );
			}
			echo '</label><br>';
			$this->render_input( $field, 'asm_fields[' . $key . ']', isset( $values[ $key ] ) ? $values[ $key ] : '' );
			echo '</p>';
		}
	}

	/**
	 * Validate simple legacy-contract fields.
	 *
	 * @param array<int, string>   $allowed Trusted field keys.
	 * @param array<string, mixed> $input Submitted values.
	 * @return array<string, mixed>|WP_Error
	 */
	public function public_profile_validate( $allowed, $input ) {
		$errors = new WP_Error();
		$fields = $this->public_profile_fields( $allowed );

		if ( ! is_array( $input ) || array_diff( array_keys( $input ), array_keys( $fields ) ) ) {
			return new WP_Error( 'public_fields', __( 'The submitted profile contains fields that are not allowed.', 'atshift-user-profile-fields' ) );
		}

		$values = array();
		foreach ( $fields as $key => $field ) {
			$raw = isset( $input[ $key ] ) ? $input[ $key ] : '';

			if ( ! is_scalar( $raw ) || strlen( (string) $raw ) > 10000 ) {
				$errors->add( 'public_value', __( 'A profile field has an invalid format or length.', 'atshift-user-profile-fields' ) );
				continue;
			}

			if ( in_array( $field['type'], array( 'select', 'radio' ), true ) && '' !== $raw && ! in_array( $raw, isset( $field['choices'] ) ? $field['choices'] : array(), true ) ) {
				$errors->add( 'public_choice', __( 'A selected value is invalid.', 'atshift-user-profile-fields' ) );
			}

			if ( '' !== trim( (string) $raw ) && $this->should_validate_format( $field ) && ! $this->is_valid_format( $raw, $field ) ) {
				$this->add_field_error( $errors, $field, $this->get_format_error_message( $field ), 'format' );
			}

			$value = $this->sanitize_value( $raw, $field );
			if ( $this->is_required_field( $field ) && ( $this->is_empty_required_value( $value ) || ( 'checkbox' === $field['type'] && empty( $value ) ) ) ) {
				$this->add_field_error( $errors, $field, __( 'This field is required.', 'atshift-user-profile-fields' ), 'required' );
			}

			do_action( 'atshift_upf_validate_public_profile_field', $errors, $field, $value );
			$values[ $key ] = $value;
		}

		return $errors->has_errors() ? $errors : $values;
	}

	/**
	 * Save simple legacy-contract fields.
	 *
	 * @param int                  $user_id User ID.
	 * @param array<int, string>   $allowed Trusted field keys.
	 * @param array<string, mixed> $input Submitted values.
	 * @return true|WP_Error
	 */
	public function public_profile_save( $user_id, $allowed, $input ) {
		if ( ! get_userdata( $user_id ) ) {
			return new WP_Error( 'public_user', __( 'The requested user could not be found.', 'atshift-user-profile-fields' ) );
		}

		$values = $this->public_profile_validate( $allowed, $input );
		if ( is_wp_error( $values ) ) {
			return $values;
		}

		foreach ( $values as $key => $value ) {
			$meta_key = $this->meta_key( $key );
			if ( false === update_user_meta( $user_id, $meta_key, $value ) && get_user_meta( $user_id, $meta_key, true ) !== $value ) {
				return new WP_Error( 'public_save', __( 'The profile could not be saved.', 'atshift-user-profile-fields' ) );
			}
		}

		do_action( 'atshift_upf_public_profile_saved', $user_id, array_keys( $values ) );
		return true;
	}

	/**
	 * Read simple legacy-contract field values.
	 *
	 * @param int                $user_id User ID.
	 * @param array<int, string> $allowed Trusted field keys.
	 * @return array<string, mixed>
	 */
	public function public_profile_values( $user_id, $allowed ) {
		$values = array();

		foreach ( array_keys( $this->public_profile_fields( $allowed ) ) as $key ) {
			$values[ $key ] = get_user_meta( $user_id, $this->meta_key( $key ), true );
		}

		return $values;
	}
}
