<?php
/**
 * Import, export, and data deletion tools.
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the plugin tools screen and handles its AJAX actions.
 */
class Atshift_UPF_Tools {
	const PAGE_SLUG      = 'atshift-user-profile-fields-tools';
	const EXPORT_ID      = 'atshift-user-profile-fields';
	const FORMAT_VERSION = 1;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_atshift_upf_tools', array( $this, 'ajax_handler' ) );
	}

	/**
	 * Add the tools submenu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			'tools.php',
			__( 'atshift User Profile Fields Tools', 'atshift-user-profile-fields' ),
			__( 'atshift User Profile Fields Tools', 'atshift-user-profile-fields' ),
			$this->get_capability(),
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Load tools-only assets.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( 'tools_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'atshift-upf-tools',
			ATSHIFT_UPF_URL . 'assets/tools.css',
			array(),
			ATSHIFT_UPF_VERSION
		);
		wp_add_inline_style(
			'atshift-upf-tools',
			Atshift_UPF_Plugin::get_admin_color_scheme_css( '.atshift-upf-tools' )
		);

		wp_enqueue_script(
			'atshift-upf-tools',
			ATSHIFT_UPF_URL . 'assets/tools.js',
			array(),
			ATSHIFT_UPF_VERSION,
			true
		);
		wp_localize_script(
			'atshift-upf-tools',
			'atshiftUPFTools',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'atshift_upf_tools' ),
				'importConfirm' => __( 'Importing will replace the current field configuration and display settings. Continue?', 'atshift-user-profile-fields' ),
				'deleteConfirm' => __( 'Delete the selected atshift User Profile Fields data? This cannot be undone.', 'atshift-user-profile-fields' ),
				'strings'       => array(
					'working'       => __( 'Processing...', 'atshift-user-profile-fields' ),
					'requestFailed' => __( 'The request could not be completed.', 'atshift-user-profile-fields' ),
					'copySuccess'   => __( 'Export code copied.', 'atshift-user-profile-fields' ),
					'copyFailed'    => __( 'The export code could not be copied.', 'atshift-user-profile-fields' ),
					'downloadFailed' => __( 'The distribution set could not be downloaded.', 'atshift-user-profile-fields' ),
					'fileReadFailed' => __( 'The selected distribution set could not be read.', 'atshift-user-profile-fields' ),
						/* translators: %s: Selected field set file name. */
						'fileSelected'   => __( 'Selected file: %s', 'atshift-user-profile-fields' ),
				),
			)
		);
	}

	/**
	 * Render the tools screen.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to use these tools.', 'atshift-user-profile-fields' ) );
		}

		$field_count = count( Atshift_UPF_Plugin::get_fields() );
		$active_tab  = sanitize_key( apply_filters( 'atshift_upf_tools_active_tab', 'export' ) );
		?>
		<div class="wrap atshift-upf-tools">
			<div class="atshift-upf-tools-page-head">
				<h1><?php esc_html_e( 'atshift User Profile Fields Tools', 'atshift-user-profile-fields' ); ?></h1>
			</div>

			<nav class="nav-tab-wrapper" role="tablist" aria-label="<?php esc_attr_e( 'Tools', 'atshift-user-profile-fields' ); ?>">
				<a href="#export" class="nav-tab <?php echo 'export' === $active_tab ? 'nav-tab-active' : ''; ?>" role="tab" aria-selected="<?php echo 'export' === $active_tab ? 'true' : 'false'; ?>" aria-controls="atshift-upf-tools-export" data-atshift-upf-tools-tab="export"><?php esc_html_e( 'Export', 'atshift-user-profile-fields' ); ?></a>
				<a href="#import" class="nav-tab <?php echo 'import' === $active_tab ? 'nav-tab-active' : ''; ?>" role="tab" aria-selected="<?php echo 'import' === $active_tab ? 'true' : 'false'; ?>" aria-controls="atshift-upf-tools-import" data-atshift-upf-tools-tab="import"><?php esc_html_e( 'Import', 'atshift-user-profile-fields' ); ?></a>
				<?php
				/**
				 * Fires after the built-in Tools tabs are rendered.
				 *
				 * Add-ons can append their own tabs. Use the same tab key in
				 * atshift_upf_tools_tab_panels.
				 *
				 * @param string $active_tab Active tab key.
				 */
				do_action( 'atshift_upf_tools_nav_tabs', $active_tab );
				?>
				<a href="#delete" class="nav-tab <?php echo 'delete' === $active_tab ? 'nav-tab-active' : ''; ?>" role="tab" aria-selected="<?php echo 'delete' === $active_tab ? 'true' : 'false'; ?>" aria-controls="atshift-upf-tools-delete" data-atshift-upf-tools-tab="delete"><?php esc_html_e( 'Delete', 'atshift-user-profile-fields' ); ?></a>
			</nav>

			<div class="atshift-upf-tools-content">
				<section id="atshift-upf-tools-export" class="atshift-upf-tools-tab-content <?php echo 'export' === $active_tab ? 'is-active' : ''; ?>" role="tabpanel" data-atshift-upf-tools-panel="export" <?php echo 'export' === $active_tab ? '' : 'hidden'; ?>>
					<p class="atshift-upf-tools-description"><?php esc_html_e( 'Create an importable file containing the complete field configuration and display settings. Profile values saved by individual users are not included.', 'atshift-user-profile-fields' ); ?></p>
					<div class="atshift-upf-tools-summary">
						<strong><?php esc_html_e( 'Current configuration', 'atshift-user-profile-fields' ); ?></strong>
						<span>
							<?php
							printf(
								/* translators: %d: number of field definitions. */
								esc_html( _n( '%d field', '%d fields', $field_count, 'atshift-user-profile-fields' ) ),
								(int) $field_count
							);
							?>
						</span>
					</div>
					<div class="atshift-upf-tools-field atshift-upf-tools-name-field">
						<label for="atshift-upf-distribution-name"><?php esc_html_e( 'Field Set Name', 'atshift-user-profile-fields' ); ?></label>
						<input type="text" id="atshift-upf-distribution-name" class="regular-text" maxlength="80" value="<?php esc_attr_e( 'Profile Fields Set', 'atshift-user-profile-fields' ); ?>" data-atshift-upf-tools-package-name>
					</div>
					<div class="atshift-upf-tools-actions">
						<button type="button" class="button button-primary" data-atshift-upf-tools-export><?php esc_html_e( 'Download Field Set', 'atshift-user-profile-fields' ); ?></button>
					</div>
					<details class="atshift-upf-tools-code-details" data-atshift-upf-tools-export-area hidden>
						<summary><?php esc_html_e( 'Show Export Code', 'atshift-user-profile-fields' ); ?></summary>
						<div class="atshift-upf-tools-field">
							<label for="atshift-upf-export-output"><?php esc_html_e( 'Export Code', 'atshift-user-profile-fields' ); ?></label>
							<textarea id="atshift-upf-export-output" rows="14" readonly data-atshift-upf-tools-export-output></textarea>
							<div class="atshift-upf-tools-actions">
								<button type="button" class="button" data-atshift-upf-tools-copy><?php esc_html_e( 'Copy', 'atshift-user-profile-fields' ); ?></button>
							</div>
						</div>
					</details>
					<div class="atshift-upf-tools-message" role="status" aria-live="polite" data-atshift-upf-tools-message="export"></div>
				</section>

				<section id="atshift-upf-tools-import" class="atshift-upf-tools-tab-content <?php echo 'import' === $active_tab ? 'is-active' : ''; ?>" role="tabpanel" data-atshift-upf-tools-panel="import" <?php echo 'import' === $active_tab ? '' : 'hidden'; ?>>
					<p class="atshift-upf-tools-description"><?php esc_html_e( 'Select a field set created by this plugin. Importing replaces the current field configuration and display settings.', 'atshift-user-profile-fields' ); ?></p>
					<div class="atshift-upf-tools-warning">
						<strong><?php esc_html_e( 'Before importing', 'atshift-user-profile-fields' ); ?></strong>
						<span><?php esc_html_e( 'Download the current field set first if you may need to restore it.', 'atshift-user-profile-fields' ); ?></span>
					</div>
					<div class="atshift-upf-tools-field">
						<label for="atshift-upf-import-file"><?php esc_html_e( 'Field Set File', 'atshift-user-profile-fields' ); ?></label>
						<input type="file" id="atshift-upf-import-file" accept=".json,application/json" data-atshift-upf-tools-import-file>
						<p class="description" data-atshift-upf-tools-file-name></p>
					</div>
					<details class="atshift-upf-tools-code-details">
						<summary><?php esc_html_e( 'Paste Export Code Instead', 'atshift-user-profile-fields' ); ?></summary>
						<div class="atshift-upf-tools-field">
							<label for="atshift-upf-import-code"><?php esc_html_e( 'Import Code', 'atshift-user-profile-fields' ); ?></label>
							<textarea id="atshift-upf-import-code" rows="14" placeholder="<?php esc_attr_e( 'Paste the import code here', 'atshift-user-profile-fields' ); ?>" data-atshift-upf-tools-import-code></textarea>
						</div>
					</details>
					<div class="atshift-upf-tools-actions">
						<button type="button" class="button button-primary" data-atshift-upf-tools-import><?php esc_html_e( 'Import and Replace', 'atshift-user-profile-fields' ); ?></button>
					</div>
					<div class="atshift-upf-tools-message" role="status" aria-live="polite" data-atshift-upf-tools-message="import"></div>
				</section>

				<section id="atshift-upf-tools-delete" class="atshift-upf-tools-tab-content atshift-upf-tools-delete <?php echo 'delete' === $active_tab ? 'is-active' : ''; ?>" role="tabpanel" data-atshift-upf-tools-panel="delete" <?php echo 'delete' === $active_tab ? '' : 'hidden'; ?>>
					<h2><?php esc_html_e( 'Delete plugin data', 'atshift-user-profile-fields' ); ?></h2>
					<p class="atshift-upf-tools-danger"><?php esc_html_e( 'Field configuration and display settings will be deleted. WordPress standard profile data is never deleted.', 'atshift-user-profile-fields' ); ?></p>
					<label class="atshift-upf-tools-check">
						<input type="checkbox" value="1" data-atshift-upf-tools-delete-values>
						<span>
							<strong><?php esc_html_e( 'Also delete custom profile values', 'atshift-user-profile-fields' ); ?></strong>
							<small><?php esc_html_e( 'Deletes user metadata created by this plugin, including values left by fields that were removed earlier.', 'atshift-user-profile-fields' ); ?></small>
						</span>
					</label>
					<?php
					/**
					 * Fires after the base deletion options are rendered.
					 *
					 * Add-ons can render additional checkboxes with the
					 * data-atshift-upf-tools-delete-addon attribute.
					 */
					do_action( 'atshift_upf_tools_delete_options' );
					?>
					<label class="atshift-upf-tools-check atshift-upf-tools-confirm">
						<input type="checkbox" value="1" data-atshift-upf-tools-delete-confirm>
						<span><?php esc_html_e( 'I understand that deleted data cannot be restored.', 'atshift-user-profile-fields' ); ?></span>
					</label>
					<div class="atshift-upf-tools-actions">
						<button type="button" class="button atshift-upf-tools-danger-button" data-atshift-upf-tools-delete disabled><?php esc_html_e( 'Delete Selected Data', 'atshift-user-profile-fields' ); ?></button>
					</div>
					<div class="atshift-upf-tools-message" role="status" aria-live="polite" data-atshift-upf-tools-message="delete"></div>
				</section>
				<?php
				/**
				 * Fires after the built-in Tools panels are rendered.
				 *
				 * Add-ons can append matching panels for custom tabs.
				 *
				 * @param string $active_tab Active tab key.
				 */
				do_action( 'atshift_upf_tools_tab_panels', $active_tab );
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle tools AJAX requests.
	 *
	 * @return void
	 */
	public function ajax_handler() {
		if ( ! current_user_can( $this->get_capability() ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to use these tools.', 'atshift-user-profile-fields' ) ), 403 );
		}

		check_ajax_referer( 'atshift_upf_tools', 'nonce' );

		$action = isset( $_POST['action_type'] ) ? sanitize_key( wp_unslash( $_POST['action_type'] ) ) : '';

		if ( 'export' === $action ) {
			$this->export_configuration();
		}

		if ( 'import' === $action ) {
			$this->import_configuration();
		}

		if ( 'import_empty' === $action ) {
			if ( ! empty( Atshift_UPF_Plugin::get_fields() ) ) {
				wp_send_json_error( array( 'message' => __( 'The field set is no longer empty. Reload the screen before importing.', 'atshift-user-profile-fields' ) ), 409 );
			}

			$this->import_configuration();
		}

		if ( 'import_default_empty' === $action ) {
			if ( ! empty( Atshift_UPF_Plugin::get_fields() ) ) {
				wp_send_json_error( array( 'message' => __( 'The field set is no longer empty. Reload the screen before importing.', 'atshift-user-profile-fields' ) ), 409 );
			}

			$this->import_default_configuration();
		}

		if ( 'delete' === $action ) {
			$this->delete_plugin_data();
		}

		wp_send_json_error( array( 'message' => __( 'Unknown tools action.', 'atshift-user-profile-fields' ) ), 400 );
	}

	/**
	 * Send the current configuration as versioned JSON.
	 *
	 * @return void
	 */
	private function export_configuration() {
		$package_name  = isset( $_POST['package_name'] ) ? sanitize_text_field( wp_unslash( $_POST['package_name'] ) ) : '';
		$package_name  = '' !== $package_name ? wp_html_excerpt( $package_name, 80, '' ) : __( 'Profile Fields Set', 'atshift-user-profile-fields' );
		$filename_slug = sanitize_title( $package_name );

		if ( '' === $filename_slug || false !== strpos( $filename_slug, '%' ) ) {
			$filename_slug = 'profile-fields-set';
		}

		$payload = array(
			'plugin'         => self::EXPORT_ID,
			'format_version' => self::FORMAT_VERSION,
			'plugin_version' => ATSHIFT_UPF_VERSION,
			'exported_at'    => gmdate( 'c' ),
			'distribution_set' => array(
				'name' => $package_name,
			),
			'fields'         => Atshift_UPF_Plugin::get_fields(),
			'settings'       => Atshift_UPF_Plugin::get_settings(),
		);
		/**
		 * Filters the field-set export payload before it is encoded as JSON.
		 *
		 * Add-ons can append their own namespaced data for round-trip imports.
		 *
		 * @param array<string, mixed> $payload Export payload.
		 */
		$payload = apply_filters( 'atshift_upf_export_payload', $payload );
		$code    = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $code ) {
			wp_send_json_error( array( 'message' => __( 'The configuration could not be encoded.', 'atshift-user-profile-fields' ) ), 500 );
		}

		wp_send_json_success(
			array(
				'code'     => $code,
				'filename' => sanitize_file_name( 'atshift-upf-' . $filename_slug . '-' . wp_date( 'Ymd-His' ) . '.json' ),
				'message'  => __( 'Distribution set downloaded.', 'atshift-user-profile-fields' ),
			)
		);
	}

	/**
	 * Validate and replace the current configuration.
	 *
	 * @param string|null $code Optional JSON code. Uses the posted import code when null.
	 * @return void
	 */
	private function import_configuration( $code = null ) {
		if ( null === $code ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Import JSON is validated and normalized by validate_import_payload().
			$code = isset( $_POST['import_code'] ) ? trim( wp_unslash( $_POST['import_code'] ) ) : '';
		} else {
			$code = trim( (string) $code );
		}

		if ( '' === $code ) {
			wp_send_json_error( array( 'message' => __( 'Paste an import code.', 'atshift-user-profile-fields' ) ), 400 );
		}

		if ( strlen( $code ) > 1048576 ) {
			wp_send_json_error( array( 'message' => __( 'The import code is too large.', 'atshift-user-profile-fields' ) ), 413 );
		}

		$payload = json_decode( $code, true );

		if ( ! is_array( $payload ) || JSON_ERROR_NONE !== json_last_error() ) {
			wp_send_json_error( array( 'message' => __( 'The import code is not valid JSON.', 'atshift-user-profile-fields' ) ), 400 );
		}

		if ( self::EXPORT_ID !== ( $payload['plugin'] ?? '' ) ) {
			wp_send_json_error( array( 'message' => __( 'This export code was not created by atshift User Profile Fields.', 'atshift-user-profile-fields' ) ), 400 );
		}

		if ( self::FORMAT_VERSION !== absint( $this->scalar_string( $payload['format_version'] ?? 0 ) ) ) {
			wp_send_json_error( array( 'message' => __( 'This export format is not supported by the installed plugin version.', 'atshift-user-profile-fields' ) ), 400 );
		}

		$fields = $this->sanitize_import_fields( $payload['fields'] ?? null );

		if ( is_wp_error( $fields ) ) {
			wp_send_json_error( array( 'message' => $fields->get_error_message() ), 400 );
		}

		$settings = $this->sanitize_import_settings( $payload['settings'] ?? null );

		if ( is_wp_error( $settings ) ) {
			wp_send_json_error( array( 'message' => $settings->get_error_message() ), 400 );
		}

		$settings = $this->synchronize_managed_core_fields( $settings, $fields );

		update_option( 'atshift_upf_fields', $fields, false );
		update_option( 'atshift_upf_settings', $settings, false );

		/**
		 * Fires after a field-set import replaces the base plugin configuration.
		 *
		 * Add-ons can restore their own namespaced data from the original payload.
		 *
		 * @param array<string, mixed>              $payload Original decoded import payload.
		 * @param array<int, array<string, mixed>> $fields Imported field definitions.
		 * @param array<string, mixed>              $settings Imported plugin settings.
		 */
		do_action( 'atshift_upf_imported_payload', $payload, $fields, $settings );

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of imported fields. */
					_n( '%d field imported. Current settings were replaced.', '%d fields imported. Current settings were replaced.', count( $fields ), 'atshift-user-profile-fields' ),
					count( $fields )
				),
			)
		);
	}

	/**
	 * Import the bundled preset matching the site language.
	 *
	 * @return void
	 */
	private function import_default_configuration() {
		$current_locale = function_exists( 'get_user_locale' ) ? (string) get_user_locale() : '';
		$current_locale = '' !== $current_locale ? $current_locale : (string) get_option( 'WPLANG', '' );
		$current_locale = '' !== $current_locale ? $current_locale : 'en_US';
		$path           = $this->get_default_preset_path( $current_locale );

		if ( '' !== $path ) {
			$code = file_get_contents( $path );
			if ( false !== $code ) {
				$this->import_configuration( $code );
			}
		}

		wp_send_json_error( array( 'message' => __( 'The default field set could not be loaded.', 'atshift-user-profile-fields' ) ), 500 );
	}

	/**
	 * Resolve a bundled preset using exact locale, language, then English.
	 *
	 * @param string $site_locale Current admin language locale.
	 * @return string
	 */
	private function get_default_preset_path( $site_locale ) {
		$locale_slug = strtolower( str_replace( '_', '-', $site_locale ) );
		$locale_slug = preg_replace( '/[^a-z0-9-]/', '', $locale_slug );
		$language    = strtok( $locale_slug, '-' );
		$candidates  = array_unique( array_filter( array( $locale_slug, $language, 'en' ) ) );
		$preset_dir  = ATSHIFT_UPF_DIR . 'presets/';

		foreach ( $candidates as $candidate ) {
			$path = $preset_dir . 'wordpress-default-profile-' . $candidate . '.json';

			if ( ! is_readable( $path ) ) {
				continue;
			}

			return $path;
		}

		return '';
	}

	/**
	 * Delete configuration and optionally plugin-owned user metadata.
	 *
	 * @return void
	 */
	private function delete_plugin_data() {
		if ( empty( $_POST['delete_confirm'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Confirm that deleted data cannot be restored before continuing.', 'atshift-user-profile-fields' ) ), 400 );
		}

		$delete_values     = ! empty( $_POST['delete_values'] );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized with sanitize_key() immediately below.
		$raw_addon_data    = isset( $_POST['delete_addon_data'] ) ? (array) wp_unslash( $_POST['delete_addon_data'] ) : array();
		$delete_addon_data = array_values( array_unique( array_filter( array_map( 'sanitize_key', $raw_addon_data ) ) ) );
		$meta_keys         = $delete_values ? $this->get_plugin_user_meta_keys() : array();
		/**
		 * Filters plugin-owned user metadata keys before destructive cleanup.
		 *
		 * @param array<int, string> $meta_keys         Metadata keys selected by the base plugin.
		 * @param bool               $delete_values     Whether custom profile values were selected.
		 * @param array<int, string> $delete_addon_data Add-on data groups selected for deletion.
		 */
		$meta_keys = (array) apply_filters( 'atshift_upf_tools_delete_user_meta_keys', $meta_keys, $delete_values, $delete_addon_data );

		delete_option( 'atshift_upf_fields' );
		delete_option( 'atshift_upf_settings' );

		foreach ( $meta_keys as $meta_key ) {
			delete_metadata( 'user', 0, $meta_key, '', true );
		}

		/**
		 * Fires after the base plugin deletes its settings and, optionally, values.
		 *
		 * @param bool              $delete_values     Whether plugin-owned user meta was deleted.
		 * @param array<int, string> $delete_addon_data Add-on data groups selected for deletion.
		 */
		do_action( 'atshift_upf_deleted_plugin_data', $delete_values, $delete_addon_data );

		$message = $delete_values
			? __( 'Plugin settings and custom profile values were deleted. WordPress standard profile data was preserved.', 'atshift-user-profile-fields' )
			: __( 'Plugin settings were deleted. Existing custom profile values were preserved.', 'atshift-user-profile-fields' );

		/**
		 * Filters the deletion result shown on the Tools screen.
		 *
		 * @param string             $message           Base deletion result.
		 * @param bool               $delete_values     Whether plugin-owned user meta was deleted.
		 * @param array<int, string> $delete_addon_data Add-on data groups selected for deletion.
		 */
		$message = apply_filters( 'atshift_upf_tools_delete_success_message', $message, $delete_values, $delete_addon_data );

		wp_send_json_success( array( 'message' => $message ) );
	}

	/**
	 * Sanitize imported field definitions.
	 *
	 * @param mixed $raw_fields Imported fields.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	private function sanitize_import_fields( $raw_fields ) {
		if ( ! is_array( $raw_fields ) ) {
			return new WP_Error( 'invalid_fields', __( 'The import code does not contain a valid fields list.', 'atshift-user-profile-fields' ) );
		}

		if ( count( $raw_fields ) > 500 ) {
			return new WP_Error( 'too_many_fields', __( 'The import contains too many fields.', 'atshift-user-profile-fields' ) );
		}

		$allowed_types = $this->get_allowed_field_types();
		$core_types    = $this->get_core_field_types();
		$core_keys     = $this->get_core_field_keys();
		$fields        = array();
		$type_by_id    = array();
		$seen_ids      = array();
		$seen_keys     = array();
		$seen_core     = array();

		foreach ( $raw_fields as $raw_field ) {
			if ( ! is_array( $raw_field ) ) {
				return new WP_Error( 'invalid_field', __( 'The import contains an invalid field definition.', 'atshift-user-profile-fields' ) );
			}

			$id   = sanitize_key( $this->scalar_string( $raw_field['id'] ?? '' ) );
			$type = sanitize_key( $this->scalar_string( $raw_field['type'] ?? '' ) );
			$key  = sanitize_key( $this->scalar_string( $raw_field['key'] ?? '' ) );

			if ( '' === $id || isset( $seen_ids[ $id ] ) ) {
				return new WP_Error( 'invalid_field_id', __( 'Every imported field must have a unique field ID.', 'atshift-user-profile-fields' ) );
			}

			if ( ! in_array( $type, $allowed_types, true ) ) {
				return new WP_Error( 'invalid_field_type', __( 'The import contains an unsupported field type.', 'atshift-user-profile-fields' ) );
			}

			if ( in_array( $type, $core_types, true ) ) {
				if ( isset( $seen_core[ $type ] ) ) {
					return new WP_Error( 'duplicate_core_field', __( 'A standard WordPress field is included more than once.', 'atshift-user-profile-fields' ) );
				}
				$seen_core[ $type ] = true;
				$key                = $core_keys[ $type ];
			}

			if ( '' === $key || isset( $seen_keys[ $key ] ) ) {
				return new WP_Error( 'invalid_field_key', __( 'Every imported field must have a unique field key.', 'atshift-user-profile-fields' ) );
			}

			$choices = $raw_field['choices'] ?? array();
			if ( is_string( $choices ) ) {
				$choices = preg_split( '/\r\n|\r|\n/', $choices );
			}
			if ( ! is_array( $choices ) ) {
				$choices = array();
			}
			$choices = array_values(
				array_filter(
					array_map(
						static function ( $choice ) {
							return is_scalar( $choice ) ? sanitize_text_field( (string) $choice ) : '';
						},
						array_slice( $choices, 0, 500 )
					),
					static function ( $choice ) {
						return '' !== $choice;
					}
				)
			);

			$role_control = $this->sanitize_role_control( $raw_field, $type );

			$field = array(
				'id'                  => $id,
				'key'                 => $key,
				'label'               => sanitize_text_field( $this->scalar_string( $raw_field['label'] ?? '' ) ),
				'type'                => $type,
				'description'         => sanitize_textarea_field( $this->scalar_string( $raw_field['description'] ?? '' ) ),
				'placeholder'         => sanitize_text_field( $this->scalar_string( $raw_field['placeholder'] ?? '' ) ),
				'choices'             => $choices,
				'parent_id'           => sanitize_key( $this->scalar_string( $raw_field['parent_id'] ?? '' ) ),
				'conditional_value'   => sanitize_text_field( $this->scalar_string( $raw_field['conditional_value'] ?? '' ) ),
				'group_columns'       => min( 3, max( 2, absint( $this->scalar_string( $raw_field['group_columns'] ?? 2 ) ) ) ),
				'conditional_input'   => 'radio' === sanitize_key( $this->scalar_string( $raw_field['conditional_input'] ?? '' ) ) ? 'radio' : 'select',
				'accordion_open'      => $this->import_bool( $raw_field['accordion_open'] ?? false ),
				'role_control'        => $role_control['mode'],
				'role_control_roles'  => $role_control['roles'],
				'required'            => ! in_array( $type, array( 'core_username', 'core_email', 'core_password', 'core_language', 'core_notification', 'core_role' ), true ) && $this->import_bool( $raw_field['required'] ?? false ),
				'validation_enabled'  => in_array( $type, array( 'email', 'url', 'phone' ), true ) && $this->import_bool( $raw_field['validation_enabled'] ?? false ),
				'initial_enabled'     => Atshift_UPF_Plugin::supports_initial_state( $type )
					? ( array_key_exists( 'initial_enabled', $raw_field ) ? $this->import_bool( $raw_field['initial_enabled'] ) : Atshift_UPF_Plugin::get_field_initial_enabled( array( 'type' => $type ) ) )
					: false,
				'sort_order'          => ( count( $fields ) + 1 ) * 10,
			);
			/**
			 * Filters one imported field definition after base sanitization.
			 *
			 * @param array<string, mixed> $field Sanitized field definition.
			 * @param array<string, mixed> $raw_field Raw imported field definition.
			 */
			$fields[] = apply_filters( 'atshift_upf_sanitize_import_field', $field, $raw_field );
			$seen_ids[ $id ]  = true;
			$seen_keys[ $key ] = true;
			$type_by_id[ $id ] = $type;
		}

		foreach ( $fields as $index => $field ) {
			$parent_id = $field['parent_id'];

			if ( '' === $parent_id ) {
				continue;
			}

			if ( ! isset( $type_by_id[ $parent_id ] ) || ! $this->can_use_parent( $field['type'], $type_by_id[ $parent_id ] ) ) {
				return new WP_Error( 'invalid_parent', __( 'The import contains an invalid field parent relationship.', 'atshift-user-profile-fields' ) );
			}

			if ( 'conditional' !== $type_by_id[ $parent_id ] ) {
				$fields[ $index ]['conditional_value'] = '';
			}
		}

		if ( $this->has_parent_cycle( $fields ) ) {
			return new WP_Error( 'parent_cycle', __( 'The import contains a circular field parent relationship.', 'atshift-user-profile-fields' ) );
		}

		return $fields;
	}

	/**
	 * Sanitize imported plugin settings.
	 *
	 * @param mixed $raw_settings Imported settings.
	 * @return array<string, mixed>|WP_Error
	 */
	private function sanitize_import_settings( $raw_settings ) {
		if ( ! is_array( $raw_settings ) ) {
			return new WP_Error( 'invalid_settings', __( 'The import code does not contain valid display settings.', 'atshift-user-profile-fields' ) );
		}

		$core_options   = Atshift_UPF_Profile::get_core_field_options();
		$allowed_hidden = array_keys( $core_options );
		$allowed_disabled = array();

		foreach ( $core_options as $key => $option ) {
			if ( ! empty( $option['off_label'] ) ) {
				$allowed_disabled[] = $key;
			}
		}

		$hidden         = isset( $raw_settings['hidden_core_fields'] ) && is_array( $raw_settings['hidden_core_fields'] )
			? array_values( array_intersect( $this->sanitize_key_list( $raw_settings['hidden_core_fields'] ), $allowed_hidden ) )
			: array();
		$disabled       = isset( $raw_settings['disabled_hidden_core_fields'] ) && is_array( $raw_settings['disabled_hidden_core_fields'] )
			? array_values( array_intersect( $this->sanitize_key_list( $raw_settings['disabled_hidden_core_fields'] ), $allowed_disabled, $hidden ) )
			: array();

		return array(
			'hidden_core_fields'          => $hidden,
			'disabled_hidden_core_fields' => $disabled,
			'apply_to_own_profile'        => $this->import_bool( $raw_settings['apply_to_own_profile'] ?? false ),
			'editor_layout'               => isset( $raw_settings['editor_layout'] ) && 'one' === $raw_settings['editor_layout'] ? 'one' : 'two',
			'show_extras'                 => $this->import_bool( $raw_settings['show_extras'] ?? false ),
			'field_group_enabled'         => $this->import_bool( $raw_settings['field_group_enabled'] ?? false ),
		);
	}

	/**
	 * Keep native-field hiding in sync with imported standard fields.
	 *
	 * @param array<string, mixed>              $settings Imported settings.
	 * @param array<int, array<string, mixed>> $fields Imported fields.
	 * @return array<string, mixed>
	 */
	private function synchronize_managed_core_fields( $settings, $fields ) {
		$type_map = array(
			'core_username'              => 'username',
			'core_email'                 => 'email',
			'core_visual_editor'         => 'visual_editor',
			'core_admin_color'           => 'admin_color',
			'core_syntax_highlighting'   => 'syntax_highlighting',
			'core_keyboard_shortcuts'    => 'keyboard_shortcuts',
			'core_toolbar'               => 'toolbar',
			'core_first_name'            => 'first_name',
			'core_last_name'             => 'last_name',
			'core_nickname'              => 'nickname',
			'core_display_name'          => 'display_name',
			'core_language'              => 'language',
			'core_website'               => 'website',
			'core_bio'                   => 'bio',
			'core_profile_picture'       => 'profile_picture',
			'core_password'              => 'password',
			'core_sessions'              => 'sessions',
			'core_application_passwords' => 'application_passwords',
			'core_notification'          => 'notification',
			'core_role'                  => 'role',
			'core_submit_button'         => 'submit_button',
		);
		$hidden   = array_fill_keys( (array) $settings['hidden_core_fields'], true );
		$disabled = array_fill_keys( (array) $settings['disabled_hidden_core_fields'], true );

		foreach ( $fields as $field ) {
			$type = $field['type'] ?? '';
			if ( ! isset( $type_map[ $type ] ) ) {
				continue;
			}

			if ( ! empty( $settings['field_group_enabled'] ) ) {
				$hidden[ $type_map[ $type ] ] = true;
			} else {
				unset( $hidden[ $type_map[ $type ] ] );
			}

			unset( $disabled[ $type_map[ $type ] ] );
		}

		$settings['hidden_core_fields'] = array_keys( $hidden );
		$settings['disabled_hidden_core_fields'] = array_values( array_intersect( array_keys( $disabled ), array_keys( $hidden ) ) );

		return $settings;
	}

	/**
	 * Return all metadata keys owned by this plugin.
	 *
	 * @return array<int, string>
	 */
	private function get_plugin_user_meta_keys() {
		global $wpdb;

		$like = $wpdb->esc_like( '_atshift_upf_' ) . '%';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Lists plugin-owned usermeta keys for the destructive cleanup tool; table name is provided by WordPress.
		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $like ) );

		return array_values(
			array_filter(
				array_map( 'sanitize_key', (array) $keys ),
				static function ( $key ) {
					return 0 === strpos( $key, '_atshift_upf_' );
				}
			)
		);
	}

	/**
	 * Check imported fields for circular parent references.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields.
	 * @return bool
	 */
	private function has_parent_cycle( $fields ) {
		$parents = array();

		foreach ( $fields as $field ) {
			$parents[ $field['id'] ] = $field['parent_id'];
		}

		foreach ( array_keys( $parents ) as $field_id ) {
			$seen   = array();
			$cursor = $field_id;

			while ( '' !== $cursor && isset( $parents[ $cursor ] ) ) {
				if ( isset( $seen[ $cursor ] ) ) {
					return true;
				}
				$seen[ $cursor ] = true;
				$cursor          = $parents[ $cursor ];
			}
		}

		return false;
	}

	/**
	 * Sanitize role visibility settings.
	 *
	 * @param array<string, mixed> $field Raw field.
	 * @param string               $type Field type.
	 * @return array{mode:string,roles:array<int,string>}
	 */
	private function sanitize_role_control( $field, $type ) {
		$controlled = array(
			'core_visual_editor',
			'core_admin_color',
			'core_syntax_highlighting',
			'core_keyboard_shortcuts',
			'core_toolbar',
			'core_language',
			'core_sessions',
			'core_application_passwords',
			'core_notification',
			'core_role',
		);

		if ( ! in_array( $type, $controlled, true ) ) {
			return array(
				'mode'  => 'all',
				'roles' => array(),
			);
		}

		$roles = isset( $field['role_control_roles'] ) && is_array( $field['role_control_roles'] ) ? $field['role_control_roles'] : array();
		$roles = array_values( array_intersect( $this->sanitize_key_list( $roles ), array_keys( wp_roles()->roles ) ) );

		return array(
			'mode'  => empty( $roles ) ? 'all' : 'selected',
			'roles' => $roles,
		);
	}

	/**
	 * Convert imported scalar values to strings without accepting nested data.
	 *
	 * @param mixed $value Imported value.
	 * @return string
	 */
	private function scalar_string( $value ) {
		return is_scalar( $value ) || null === $value ? (string) $value : '';
	}

	/**
	 * Accept only explicit true values from imported JSON.
	 *
	 * @param mixed $value Imported value.
	 * @return bool
	 */
	private function import_bool( $value ) {
		return true === $value || 1 === $value || '1' === $value;
	}

	/**
	 * Sanitize a list of imported scalar keys.
	 *
	 * @param array<int|string, mixed> $values Imported values.
	 * @return array<int, string>
	 */
	private function sanitize_key_list( $values ) {
		$keys = array();

		foreach ( $values as $value ) {
			if ( ! is_scalar( $value ) && null !== $value ) {
				continue;
			}

			$key = sanitize_key( (string) $value );
			if ( '' !== $key ) {
				$keys[] = $key;
			}
		}

		return array_values( array_unique( $keys ) );
	}

	/**
	 * Return whether an imported field can use the requested parent type.
	 *
	 * @param string $type Child type.
	 * @param string $parent_type Parent type.
	 * @return bool
	 */
	private function can_use_parent( $type, $parent_type ) {
		$structures = array( 'group', 'box', 'conditional', 'accordion' );

		if ( ! in_array( $parent_type, $structures, true ) ) {
			return false;
		}

		if ( 'group' === $parent_type && in_array( $type, $structures, true ) ) {
			return false;
		}

		if ( 'box' === $parent_type && in_array( $type, $structures, true ) && 'conditional' !== $type ) {
			return false;
		}

		if ( 'conditional' === $parent_type && 'conditional' === $type ) {
			return false;
		}

		return true;
	}

	/**
	 * Return supported field types.
	 *
	 * @return array<int, string>
	 */
	private function get_allowed_field_types() {
		$types = array(
			'text',
			'textarea',
			'email',
			'url',
			'phone',
			'number',
			'image',
			'checkbox',
			'radio',
			'select',
			'additional_name',
			'group',
			'box',
			'conditional',
			'accordion',
			'core_username',
			'core_email',
			'core_visual_editor',
			'core_admin_color',
			'core_syntax_highlighting',
			'core_keyboard_shortcuts',
			'core_toolbar',
			'core_first_name',
			'core_last_name',
			'core_nickname',
			'core_display_name',
			'core_language',
			'core_website',
			'core_bio',
			'core_profile_picture',
			'core_password',
			'core_sessions',
			'core_application_passwords',
			'core_notification',
			'core_role',
			'core_submit_button',
		);

		if ( (bool) apply_filters( 'atshift_upf_passkeys_field_available', false ) ) {
			$types[] = 'passkeys';
		}

		/**
		 * Filters field types accepted by field-set imports.
		 *
		 * Add-ons that register field types in the editor should add matching
		 * import support here.
		 *
		 * @param array<int, string> $types Field type keys.
		 */
		return array_values( array_unique( array_map( 'sanitize_key', (array) apply_filters( 'atshift_upf_import_allowed_field_types', $types ) ) ) );
	}

	/**
	 * Return standard WordPress field types.
	 *
	 * @return array<int, string>
	 */
	private function get_core_field_types() {
		return array_values(
			array_filter(
				$this->get_allowed_field_types(),
				static function ( $type ) {
					return 0 === strpos( $type, 'core_' );
				}
			)
		);
	}

	/**
	 * Return fixed keys for standard WordPress fields.
	 *
	 * @return array<string, string>
	 */
	private function get_core_field_keys() {
		return array(
			'core_username'              => 'user_login',
			'core_email'                 => 'user_email',
			'core_visual_editor'         => 'rich_editing',
			'core_admin_color'           => 'admin_color',
			'core_syntax_highlighting'   => 'syntax_highlighting',
			'core_keyboard_shortcuts'    => 'comment_shortcuts',
			'core_toolbar'               => 'show_admin_bar_front',
			'core_first_name'            => 'first_name',
			'core_last_name'             => 'last_name',
			'core_nickname'              => 'nickname',
			'core_display_name'          => 'display_name',
			'core_language'              => 'locale',
			'core_website'               => 'user_url',
			'core_bio'                   => 'description',
			'core_profile_picture'       => 'profile_picture',
			'core_password'              => 'password',
			'core_sessions'              => 'sessions',
			'core_application_passwords' => 'application_passwords',
			'core_notification'          => 'send_user_notification',
			'core_role'                  => 'role',
			'core_submit_button'         => 'submit_button',
		);
	}

	/**
	 * Return the capability required to use tools.
	 *
	 * @return string
	 */
	private function get_capability() {
		return (string) apply_filters( 'atshift_upf_manage_capability', 'manage_options' );
	}
}
