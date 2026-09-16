<?php
/**
 * Opt-in field-set presets provided by other plugins.
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discovers, previews, and safely merges external field-set presets.
 */
final class Atshift_UPF_Presets {
	const PAGE_SLUG      = 'atshift-user-profile-fields-presets';
	const HISTORY_OPTION = 'atshift_upf_preset_applications';
	const LOCK_OPTION    = 'atshift_upf_preset_apply_lock';
	const NONCE_ACTION   = 'atshift_upf_apply_preset';
	const HISTORY_LIMIT  = 20;

	/**
	 * Import validator shared with the Tools screen.
	 *
	 * @var Atshift_UPF_Tools
	 */
	private $tools;

	/**
	 * @param Atshift_UPF_Tools $tools Tools validator.
	 */
	public function __construct( $tools ) {
		$this->tools = $tools;
		add_action( 'admin_menu', array( $this, 'add_menu' ), 20 );
		add_action( 'admin_init', array( $this, 'handle_apply' ) );
	}

	/**
	 * Add the page only when at least one valid provider is available.
	 *
	 * @return void
	 */
	public function add_menu() {
		if ( empty( $this->get_providers() ) ) {
			return;
		}

		add_submenu_page(
			'atshift-user-profile-fields',
			__( 'Field Set Presets', 'atshift-user-profile-fields' ),
			__( 'Field Set Presets', 'atshift-user-profile-fields' ),
			$this->get_capability(),
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render available provider definitions and recent application history.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to manage field set presets.', 'atshift-user-profile-fields' ) );
		}

		$providers = $this->get_providers();
		?>
		<div class="wrap atshift-upf-presets">
			<h1><?php esc_html_e( 'Field Set Presets', 'atshift-user-profile-fields' ); ?></h1>
			<p><?php esc_html_e( 'Add fields supplied by compatible plugins without replacing your current field order, settings, or saved profile values.', 'atshift-user-profile-fields' ); ?></p>
			<?php $this->render_notice(); ?>

			<?php foreach ( $providers as $provider ) : ?>
				<?php $this->render_provider( $provider ); ?>
			<?php endforeach; ?>

			<?php $this->render_history(); ?>
		</div>
		<?php
	}

	/**
	 * Render one provider card.
	 *
	 * @param array<string, mixed> $provider Provider descriptor.
	 * @return void
	 */
	private function render_provider( $provider ) {
		$payload = $this->resolve_provider( $provider );
		if ( is_wp_error( $payload ) ) {
			return;
		}

		$components = $payload['optional_components'];
		$fingerprint = $this->configuration_fingerprint( $provider, $payload );
		?>
		<div class="card" style="max-width: 900px; margin-top: 20px;">
			<h2><?php echo esc_html( $provider['label'] ); ?></h2>
			<?php if ( '' !== $provider['description'] ) : ?>
				<p><?php echo esc_html( $provider['description'] ); ?></p>
			<?php endif; ?>
			<p class="description">
				<?php
				printf(
					/* translators: 1: provider plugin name, 2: preset version. */
					esc_html__( 'Provided by %1$s. Preset version %2$s.', 'atshift-user-profile-fields' ),
					esc_html( $provider['provider']['plugin'] ),
					esc_html( $provider['version'] )
				);
				?>
			</p>

			<?php if ( ! empty( $payload['fields'] ) ) : ?>
				<h3><?php esc_html_e( 'Included fields', 'atshift-user-profile-fields' ); ?></h3>
				<ul class="ul-disc">
					<?php foreach ( $payload['fields'] as $field ) : ?>
						<li><?php echo esc_html( $field['label'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<form method="post">
				<input type="hidden" name="atshift_upf_preset_action" value="apply">
				<input type="hidden" name="provider_id" value="<?php echo esc_attr( $provider['id'] ); ?>">
				<input type="hidden" name="configuration_fingerprint" value="<?php echo esc_attr( $fingerprint ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>

				<?php if ( ! empty( $components ) ) : ?>
					<h3><?php esc_html_e( 'Optional additions', 'atshift-user-profile-fields' ); ?></h3>
					<fieldset>
						<?php foreach ( $components as $component ) : ?>
							<?php $available = $this->component_is_available( $component ); ?>
							<label style="display: block; margin: 0 0 12px;">
								<input type="checkbox" name="components[]" value="<?php echo esc_attr( $component['id'] ); ?>" <?php checked( $component['default_selected'] ); ?> <?php disabled( ! $available ); ?>>
								<strong><?php echo esc_html( $component['label'] ); ?></strong>
								<?php if ( ! $available ) : ?>
									<span><?php esc_html_e( '(Unavailable)', 'atshift-user-profile-fields' ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $component['description'] ) : ?>
									<br><span class="description"><?php echo esc_html( $component['description'] ); ?></span>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
				<?php endif; ?>

				<?php submit_button( __( 'Apply Selected Additions', 'atshift-user-profile-fields' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle an administrator-initiated preset merge.
	 *
	 * @return void
	 */
	public function handle_apply() {
		if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
			return;
		}
		if ( 'apply' !== ( isset( $_POST['atshift_upf_preset_action'] ) ? sanitize_key( wp_unslash( $_POST['atshift_upf_preset_action'] ) ) : '' ) ) {
			return;
		}
		if ( ! current_user_can( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to manage field set presets.', 'atshift-user-profile-fields' ) );
		}
		check_admin_referer( self::NONCE_ACTION );

		if ( ! $this->acquire_lock() ) {
			$this->redirect_with_notice( 'busy' );
		}

		try {
			$result = $this->apply_request();
		} catch ( Throwable $error ) {
			$result = new WP_Error( 'provider_exception', __( 'The preset provider could not complete the request.', 'atshift-user-profile-fields' ) );
		}

		delete_option( self::LOCK_OPTION );

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( $result->get_error_code() );
		}

		$this->redirect_with_notice( $result['notice'], $result );
	}

	/**
	 * Validate and apply the current request.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	private function apply_request() {
		$provider_id = isset( $_POST['provider_id'] ) ? $this->sanitize_provider_id( wp_unslash( $_POST['provider_id'] ) ) : '';
		$providers   = $this->get_providers();
		if ( '' === $provider_id || ! isset( $providers[ $provider_id ] ) ) {
			return new WP_Error( 'invalid_provider' );
		}

		$provider = $providers[ $provider_id ];
		$payload  = $this->resolve_provider( $provider );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$submitted_fingerprint = isset( $_POST['configuration_fingerprint'] ) ? sanitize_text_field( wp_unslash( $_POST['configuration_fingerprint'] ) ) : '';
		if ( ! hash_equals( $this->configuration_fingerprint( $provider, $payload ), $submitted_fingerprint ) ) {
			return new WP_Error( 'stale' );
		}

		$selected = isset( $_POST['components'] ) && is_array( $_POST['components'] )
			? array_values( array_unique( array_map( 'sanitize_key', wp_unslash( $_POST['components'] ) ) ) )
			: array();
		$candidates = $payload['fields'];
		$extensions = array();
		$known      = array();

		foreach ( $payload['optional_components'] as $component ) {
			$known[] = $component['id'];
			if ( ! in_array( $component['id'], $selected, true ) ) {
				continue;
			}
			if ( ! $this->component_is_available( $component ) ) {
				return new WP_Error( 'unavailable_component' );
			}
			$candidates = array_merge( $candidates, $component['fields'] );
			foreach ( $component['extensions'] as $extension_id => $extension_payload ) {
				if ( ! isset( $extensions[ $extension_id ] ) ) {
					$extensions[ $extension_id ] = array();
				}
				$extensions[ $extension_id ][] = $extension_payload;
			}
		}

		if ( array_diff( $selected, $known ) ) {
			return new WP_Error( 'invalid_component' );
		}

		$normalized = $this->tools->normalize_preset_fields( $candidates );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}

		$current = get_option( 'atshift_upf_fields', array() );
		$current = is_array( $current ) ? array_values( $current ) : array();
		$merge   = $this->merge_fields( $current, $normalized );
		if ( ! empty( $merge['conflicts'] ) ) {
			return new WP_Error( 'conflict' );
		}

		$handlers = $this->get_extension_handlers();
		$extension_context = array(
			'phase'       => 'validate',
			'provider_id' => $provider_id,
			'version'     => $provider['version'],
			'components'  => $selected,
		);
		$extension_validation = array();
		foreach ( $extensions as $extension_id => $extension_payloads ) {
			$validation = call_user_func( $handlers[ $extension_id ], $extension_payloads, $extension_context );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}
			$extension_validation[ $extension_id ] = is_array( $validation ) ? $validation : array();
		}

		$settings = Atshift_UPF_Plugin::get_settings();
		if ( ! empty( $payload['settings_patch'] ) ) {
			$settings = array_merge( $settings, $payload['settings_patch'] );
		}
		$settings = $this->tools->normalize_preset_settings( $settings );
		if ( is_wp_error( $settings ) ) {
			return $settings;
		}
		$settings = $this->tools->synchronize_preset_settings( $settings, $merge['fields'] );

		$extension_changes = array_filter(
			$extension_validation,
			static function ( $validation ) {
				return ! empty( $validation['added'] );
			}
		);
		if ( empty( $merge['added'] ) && empty( $extension_changes ) ) {
			return array( 'notice' => 'unchanged', 'added' => 0, 'unchanged' => count( $merge['unchanged'] ) );
		}

		$stored_settings = get_option( 'atshift_upf_settings', null );
		$before = array( 'fields' => $current, 'settings' => $stored_settings );
		update_option( 'atshift_upf_fields', $merge['fields'], false );
		update_option( 'atshift_upf_settings', $settings, false );

		$extension_results = array();
		$extension_context['phase'] = 'apply';
		foreach ( $extensions as $extension_id => $extension_payloads ) {
			$extension_results[ $extension_id ] = call_user_func( $handlers[ $extension_id ], $extension_payloads, $extension_context );
			if ( is_wp_error( $extension_results[ $extension_id ] ) ) {
				update_option( 'atshift_upf_fields', $current, false );
				if ( null === $stored_settings ) {
					delete_option( 'atshift_upf_settings' );
				} else {
					update_option( 'atshift_upf_settings', $stored_settings, false );
				}
				return $extension_results[ $extension_id ];
			}
		}

		$receipt = array(
			'provider_id'      => $provider_id,
			'provider_version' => $provider['version'],
			'applied_at'       => gmdate( 'c' ),
			'user_id'          => get_current_user_id(),
			'components'       => $selected,
			'added'            => $merge['added'],
			'unchanged'        => $merge['unchanged'],
			'conflicts'        => array(),
			'field_fingerprint'=> $this->fingerprint( $normalized ),
			'extensions'       => $this->summarize_extension_results( $extension_results ),
		);
		$this->record_history( $receipt );

		/**
		 * Fires after a preset has been merged into the base configuration.
		 *
		 * @param array<string, mixed> $receipt Application receipt without profile values.
		 * @param array<string, mixed> $payload Resolved provider payload.
		 * @param array<string, mixed> $before Configuration before the merge.
		 * @param array<string, mixed> $after Configuration after the merge.
		 */
		do_action( 'atshift_upf_preset_applied', $receipt, $payload, $before, array( 'fields' => $merge['fields'], 'settings' => $settings ) );

		return array( 'notice' => 'applied', 'added' => count( $merge['added'] ), 'unchanged' => count( $merge['unchanged'] ) );
	}

	/**
	 * Discover and validate provider descriptors.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_providers() {
		$providers = apply_filters( 'atshift_upf_preset_providers', array(), $this->get_context() );
		if ( ! is_array( $providers ) ) {
			return array();
		}

		$valid = array();
		foreach ( $providers as $key => $provider ) {
			if ( ! is_array( $provider ) ) {
				continue;
			}
			$id = $this->sanitize_provider_id( isset( $provider['id'] ) ? $provider['id'] : $key );
			if ( '' === $id || ! is_callable( $provider['resolve'] ?? null ) ) {
				continue;
			}
			$provider['id']          = $id;
			$provider['version']     = sanitize_text_field( (string) ( $provider['version'] ?? '' ) );
			$provider['label']       = sanitize_text_field( (string) ( $provider['label'] ?? $id ) );
			$provider['description'] = sanitize_textarea_field( (string) ( $provider['description'] ?? '' ) );
			$provider['provider']    = is_array( $provider['provider'] ?? null ) ? $provider['provider'] : array();
			$provider['provider']['plugin']  = sanitize_text_field( (string) ( $provider['provider']['plugin'] ?? $id ) );
			$provider['provider']['version'] = sanitize_text_field( (string) ( $provider['provider']['version'] ?? '' ) );
			$valid[ $id ] = $provider;
		}

		return $valid;
	}

	/**
	 * Resolve and normalize one provider payload.
	 *
	 * @param array<string, mixed> $provider Provider descriptor.
	 * @return array<string, mixed>|WP_Error
	 */
	private function resolve_provider( $provider ) {
		try {
			$payload = call_user_func( $provider['resolve'], $this->get_context() );
		} catch ( Throwable $error ) {
			return new WP_Error( 'provider_exception' );
		}
		if ( ! is_array( $payload ) || ! isset( $payload['fields'] ) || ! is_array( $payload['fields'] ) ) {
			return new WP_Error( 'invalid_payload' );
		}

		$components = array();
		foreach ( (array) ( $payload['optional_components'] ?? array() ) as $component ) {
			if ( ! is_array( $component ) ) {
				continue;
			}
			$id = sanitize_key( $component['id'] ?? '' );
			if ( '' === $id || isset( $components[ $id ] ) ) {
				continue;
			}
			$components[ $id ] = array(
				'id'               => $id,
				'label'            => sanitize_text_field( (string) ( $component['label'] ?? $id ) ),
				'description'      => sanitize_textarea_field( (string) ( $component['description'] ?? '' ) ),
				'default_selected' => ! empty( $component['default_selected'] ),
				'requires'         => array_values( array_filter( array_map( 'sanitize_key', (array) ( $component['requires'] ?? array() ) ) ) ),
				'fields'           => is_array( $component['fields'] ?? null ) ? $component['fields'] : array(),
				'extensions'       => is_array( $component['extensions'] ?? null ) ? $component['extensions'] : array(),
			);
		}

		return array(
			'fields'              => $payload['fields'],
			'settings_patch'      => is_array( $payload['settings_patch'] ?? null ) ? $payload['settings_patch'] : array(),
			'optional_components' => array_values( $components ),
		);
	}

	/**
	 * Merge normalized candidates without changing existing definitions.
	 *
	 * @param array<int, array<string, mixed>> $current Existing fields.
	 * @param array<int, array<string, mixed>> $candidates Candidate fields.
	 * @return array<string, mixed>
	 */
	private function merge_fields( $current, $candidates ) {
		$by_id = array();
		$by_key = array();
		$max_order = 0;
		foreach ( $current as $index => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$id  = sanitize_key( $field['id'] ?? '' );
			$key = sanitize_key( $field['key'] ?? '' );
			if ( '' !== $id ) {
				$by_id[ $id ] = $index;
			}
			if ( '' !== $key ) {
				$by_key[ $key ] = $index;
			}
			$max_order = max( $max_order, (int) ( $field['sort_order'] ?? 0 ) );
		}

		$added = array();
		$unchanged = array();
		$conflicts = array();
		$id_map = array();

		foreach ( $candidates as $candidate ) {
			$id = $candidate['id'];
			$key = $candidate['key'];
			$type = $candidate['type'];
			if ( isset( $by_key[ $key ] ) ) {
				$existing = $current[ $by_key[ $key ] ];
				if ( ( $existing['type'] ?? '' ) === $type ) {
					$unchanged[] = $existing['id'];
					$id_map[ $id ] = $existing['id'];
				} else {
					$conflicts[] = $id;
				}
				continue;
			}
			if ( isset( $by_id[ $id ] ) ) {
				$conflicts[] = $id;
				continue;
			}
			$id_map[ $id ] = $id;
		}

		if ( ! empty( $conflicts ) ) {
			return compact( 'current', 'added', 'unchanged', 'conflicts' ) + array( 'fields' => $current );
		}

		foreach ( $candidates as $candidate ) {
			if ( isset( $by_key[ $candidate['key'] ] ) ) {
				continue;
			}
			if ( '' !== $candidate['parent_id'] && isset( $id_map[ $candidate['parent_id'] ] ) ) {
				$candidate['parent_id'] = $id_map[ $candidate['parent_id'] ];
			}
			$max_order += 10;
			$candidate['sort_order'] = $max_order;
			$current[] = $candidate;
			$by_key[ $candidate['key'] ] = count( $current ) - 1;
			$by_id[ $candidate['id'] ] = count( $current ) - 1;
			$added[] = $candidate['id'];
		}

		return array( 'fields' => $current, 'added' => $added, 'unchanged' => array_values( array_unique( $unchanged ) ), 'conflicts' => array() );
	}

	/**
	 * Return the provider context.
	 *
	 * @return array<string, mixed>
	 */
	private function get_context() {
		$extensions = array();
		if ( defined( 'ATSHIFT_UPF_PRO_VERSION' ) ) {
			$extensions['atshift-user-profile-fields-pro'] = ATSHIFT_UPF_PRO_VERSION;
		}

		return array(
			'api_version'       => 1,
			'locale'            => determine_locale(),
			'upf_version'       => ATSHIFT_UPF_VERSION,
			'active_extensions' => $extensions,
		);
	}

	/**
	 * Return validated extension handlers.
	 *
	 * @return array<string, callable>
	 */
	private function get_extension_handlers() {
		$handlers = apply_filters( 'atshift_upf_preset_extension_handlers', array(), $this->get_context() );
		if ( ! is_array( $handlers ) ) {
			return array();
		}
		return array_filter( $handlers, 'is_callable' );
	}

	/**
	 * Whether all requirements and namespaced extension handlers are present.
	 *
	 * @param array<string, mixed> $component Component.
	 * @return bool
	 */
	private function component_is_available( $component ) {
		$active = $this->get_context()['active_extensions'];
		foreach ( $component['requires'] as $required ) {
			if ( ! isset( $active[ $required ] ) ) {
				return false;
			}
		}
		$handlers = $this->get_extension_handlers();
		foreach ( array_keys( $component['extensions'] ) as $extension_id ) {
			if ( ! isset( $handlers[ $extension_id ] ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Build a fingerprint that rejects stale apply forms.
	 *
	 * @param array<string, mixed> $provider Provider.
	 * @param array<string, mixed> $payload Payload.
	 * @return string
	 */
	private function configuration_fingerprint( $provider, $payload ) {
		return $this->fingerprint(
			array(
				'provider' => array( 'id' => $provider['id'], 'version' => $provider['version'] ),
				'payload'  => $payload,
				'fields'   => get_option( 'atshift_upf_fields', array() ),
				'settings' => get_option( 'atshift_upf_settings', array() ),
			)
		);
	}

	/**
	 * Hash nested data after stable associative-key ordering.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function fingerprint( $value ) {
		$value = $this->canonicalize( $value );
		return hash( 'sha256', wp_json_encode( $value ) );
	}

	/**
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private function canonicalize( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
			ksort( $value );
		}
		foreach ( $value as $key => $item ) {
			$value[ $key ] = $this->canonicalize( $item );
		}
		return $value;
	}

	/**
	 * Keep extension receipts free of provider payload and profile data.
	 *
	 * @param array<string, mixed> $results Handler results.
	 * @return array<string, mixed>
	 */
	private function summarize_extension_results( $results ) {
		$summary = array();
		foreach ( $results as $id => $result ) {
			$summary[ sanitize_key( $id ) ] = is_wp_error( $result ) ? 'error' : 'applied';
		}
		return $summary;
	}

	/**
	 * Append a bounded, non-PII application receipt.
	 *
	 * @param array<string, mixed> $receipt Receipt.
	 * @return void
	 */
	private function record_history( $receipt ) {
		$history = get_option( self::HISTORY_OPTION, array() );
		$history = is_array( $history ) ? $history : array();
		array_unshift( $history, $receipt );
		update_option( self::HISTORY_OPTION, array_slice( $history, 0, self::HISTORY_LIMIT ), false );
	}

	/**
	 * Render recent receipts.
	 *
	 * @return void
	 */
	private function render_history() {
		$history = get_option( self::HISTORY_OPTION, array() );
		if ( ! is_array( $history ) || empty( $history ) ) {
			return;
		}
		?>
		<h2 style="margin-top: 32px;"><?php esc_html_e( 'Recent preset applications', 'atshift-user-profile-fields' ); ?></h2>
		<table class="widefat striped" style="max-width: 900px;">
			<thead><tr><th><?php esc_html_e( 'Preset', 'atshift-user-profile-fields' ); ?></th><th><?php esc_html_e( 'Applied', 'atshift-user-profile-fields' ); ?></th><th><?php esc_html_e( 'Fields added', 'atshift-user-profile-fields' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( array_slice( $history, 0, 10 ) as $receipt ) : ?>
				<tr>
					<td><?php echo esc_html( ( $receipt['provider_id'] ?? '' ) . ' ' . ( $receipt['provider_version'] ?? '' ) ); ?></td>
					<td><?php echo esc_html( $receipt['applied_at'] ?? '' ); ?></td>
					<td><?php echo esc_html( count( (array) ( $receipt['added'] ?? array() ) ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render a result notice selected from fixed server-side messages.
	 *
	 * @return void
	 */
	private function render_notice() {
		$code = isset( $_GET['atshift_upf_preset_notice'] ) ? sanitize_key( wp_unslash( $_GET['atshift_upf_preset_notice'] ) ) : '';
		$messages = array(
			'applied'               => array( 'success', __( 'The selected additions were applied.', 'atshift-user-profile-fields' ) ),
			'unchanged'             => array( 'info', __( 'The selected fields are already present. No changes were needed.', 'atshift-user-profile-fields' ) ),
			'busy'                  => array( 'warning', __( 'Another preset application is in progress. Try again shortly.', 'atshift-user-profile-fields' ) ),
			'stale'                 => array( 'warning', __( 'The configuration changed after this page was opened. Review the preset and try again.', 'atshift-user-profile-fields' ) ),
			'conflict'              => array( 'error', __( 'The preset conflicts with an existing field ID, key, or type. No changes were made.', 'atshift-user-profile-fields' ) ),
			'invalid_provider'      => array( 'error', __( 'The selected preset provider is no longer available.', 'atshift-user-profile-fields' ) ),
			'invalid_component'     => array( 'error', __( 'The request contained an unknown optional addition.', 'atshift-user-profile-fields' ) ),
			'unavailable_component' => array( 'error', __( 'A selected optional addition is not available with the currently active extensions.', 'atshift-user-profile-fields' ) ),
			'provider_exception'    => array( 'error', __( 'The preset provider could not complete the request.', 'atshift-user-profile-fields' ) ),
			'invalid_payload'       => array( 'error', __( 'The preset provider returned an invalid field set.', 'atshift-user-profile-fields' ) ),
		);
		if ( ! isset( $messages[ $code ] ) ) {
			return;
		}
		printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $messages[ $code ][0] ), esc_html( $messages[ $code ][1] ) );
	}

	/**
	 * Redirect back to the page with a fixed notice code.
	 *
	 * @param string               $notice Notice code.
	 * @param array<string, mixed> $result Optional counts.
	 * @return void
	 */
	private function redirect_with_notice( $notice, $result = array() ) {
		$args = array( 'page' => self::PAGE_SLUG, 'atshift_upf_preset_notice' => sanitize_key( $notice ) );
		if ( isset( $result['added'] ) ) {
			$args['added'] = absint( $result['added'] );
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Acquire a short option-backed lock.
	 *
	 * @return bool
	 */
	private function acquire_lock() {
		$now = time();
		$lock = (int) get_option( self::LOCK_OPTION, 0 );
		if ( $lock && $lock > $now - 300 ) {
			return false;
		}
		if ( $lock ) {
			delete_option( self::LOCK_OPTION );
		}
		return add_option( self::LOCK_OPTION, $now, '', false );
	}

	/**
	 * Preserve dots in globally unique provider IDs.
	 *
	 * @param mixed $id Provider ID.
	 * @return string
	 */
	private function sanitize_provider_id( $id ) {
		$id = strtolower( trim( is_scalar( $id ) ? (string) $id : '' ) );
		return preg_match( '/^[a-z0-9][a-z0-9._-]*$/', $id ) ? $id : '';
	}

	/**
	 * @return string
	 */
	private function get_capability() {
		return atshift_upf_admin_capability( 'presets' );
	}
}
