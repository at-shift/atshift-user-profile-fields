<?php
/**
 * Admin screen.
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the plugin settings interface.
 */
class Atshift_UPF_Admin {
	const PAGE_SLUG        = 'atshift-user-profile-fields';
	const EXTRAS_PAGE_SLUG = 'atshift-user-profile-fields-extras';

	/**
	 * Supported custom field types.
	 *
	 * @var array<string, string>
	 */
	private $field_types = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->field_types = array(
			'text'     => __( 'Text', 'atshift-user-profile-fields' ),
			'textarea' => __( 'Textarea', 'atshift-user-profile-fields' ),
			'email'    => __( 'Email (Other)', 'atshift-user-profile-fields' ),
			'url'      => __( 'URL (Other)', 'atshift-user-profile-fields' ),
			'phone'    => __( 'Phone', 'atshift-user-profile-fields' ),
			'number'   => __( 'Number', 'atshift-user-profile-fields' ),
			'image'    => __( 'Image', 'atshift-user-profile-fields' ),
			'checkbox' => __( 'Checkbox', 'atshift-user-profile-fields' ),
			'radio'    => __( 'Radio', 'atshift-user-profile-fields' ),
			'select'   => __( 'Select', 'atshift-user-profile-fields' ),
			'additional_name' => __( 'Additional Name', 'atshift-user-profile-fields' ),
			'group'    => __( 'Horizontal Group', 'atshift-user-profile-fields' ),
			'box'      => __( 'Box Group', 'atshift-user-profile-fields' ),
			'conditional' => __( 'Conditional Group', 'atshift-user-profile-fields' ),
			'accordion' => __( 'Accordion Group', 'atshift-user-profile-fields' ),
			'core_username'     => __( 'Username', 'atshift-user-profile-fields' ),
			'core_email'        => __( 'Email', 'atshift-user-profile-fields' ),
			'core_visual_editor' => __( 'Visual editor', 'atshift-user-profile-fields' ),
			'core_admin_color'  => __( 'Admin color scheme', 'atshift-user-profile-fields' ),
			'core_syntax_highlighting' => __( 'Syntax highlighting', 'atshift-user-profile-fields' ),
			'core_keyboard_shortcuts' => __( 'Keyboard shortcuts', 'atshift-user-profile-fields' ),
			'core_toolbar'      => __( 'Toolbar', 'atshift-user-profile-fields' ),
			'core_first_name'   => __( 'First name', 'atshift-user-profile-fields' ),
			'core_last_name'    => __( 'Last name', 'atshift-user-profile-fields' ),
			'core_nickname'     => __( 'Nickname', 'atshift-user-profile-fields' ),
			'core_display_name' => __( 'Display name', 'atshift-user-profile-fields' ),
			'core_language'     => __( 'Language', 'atshift-user-profile-fields' ),
			'core_website'      => __( 'Website', 'atshift-user-profile-fields' ),
			'core_bio'          => __( 'Biographical info', 'atshift-user-profile-fields' ),
			'core_profile_picture' => __( 'Profile picture', 'atshift-user-profile-fields' ),
			'core_password'     => __( 'Password', 'atshift-user-profile-fields' ),
			'core_sessions'     => __( 'Sessions', 'atshift-user-profile-fields' ),
			'core_application_passwords' => __( 'Application passwords', 'atshift-user-profile-fields' ),
			'core_notification' => __( 'Email Notification', 'atshift-user-profile-fields' ),
			'core_role'         => __( 'Role', 'atshift-user-profile-fields' ),
			'core_submit_button' => __( 'Add / Save User button', 'atshift-user-profile-fields' ),
		);
		/**
		 * Filters field types available in the field editor.
		 *
		 * Add-ons should return an associative array of field type keys to labels.
		 *
		 * @param array<string, string> $field_types Field type labels.
		 */
		$this->field_types = apply_filters( 'atshift_upf_admin_field_types', $this->field_types );

		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'load-toplevel_page_atshift-user-profile-fields', array( $this, 'register_screen_options' ) );
		add_action( 'admin_init', array( $this, 'redirect_legacy_page_url' ), 1 );
		add_action( 'admin_init', array( $this, 'handle_posts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
		add_action( 'wp_ajax_atshift_upf_save_screen_options', array( $this, 'ajax_save_screen_options' ) );
	}

	/**
	 * Register menu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'atshift User Profile Fields', 'atshift-user-profile-fields' ),
			__( 'atshift User Profile Fields', 'atshift-user-profile-fields' ),
			$this->get_capability(),
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-id'
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Field Management', 'atshift-user-profile-fields' ),
			__( 'Field Management', 'atshift-user-profile-fields' ),
			$this->get_capability(),
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Display Settings', 'atshift-user-profile-fields' ),
			__( 'Display Settings', 'atshift-user-profile-fields' ),
			$this->get_capability(),
			self::EXTRAS_PAGE_SLUG,
			array( $this, 'render_extras_page' )
		);
	}

	/**
	 * Return the capability required to manage profile field settings.
	 *
	 * @return string
	 */
	private function get_capability() {
		return (string) apply_filters( 'atshift_upf_manage_capability', 'manage_options' );
	}

	/**
	 * Redirect previous submenu URLs to the current top-level pages.
	 *
	 * @return void
	 */
	public function redirect_legacy_page_url() {
		global $pagenow;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only legacy URL routing.
		if ( ! in_array( $pagenow, array( 'users.php', 'options-general.php' ), true ) || empty( $_GET['page'] ) || self::PAGE_SLUG !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		if ( ! current_user_can( $this->get_capability() ) ) {
			return;
		}

		$args = array(
			'page' => self::PAGE_SLUG,
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only legacy URL routing.
		if ( ! empty( $_GET['tab'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only legacy URL routing.
			$tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
			if ( 'extras' === $tab ) {
				$args['page'] = self::EXTRAS_PAGE_SLUG;
			} else {
				$args['tab'] = $tab;
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only legacy URL routing.
		if ( ! empty( $_GET['edit'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only legacy URL routing.
			$args['edit'] = sanitize_key( wp_unslash( $_GET['edit'] ) );
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Load admin CSS.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen selection.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( ! in_array( $hook, array( 'toplevel_page_atshift-user-profile-fields', 'atshift-user-profile-fields_page_atshift-user-profile-fields-extras' ), true ) && ! in_array( $page, array( self::PAGE_SLUG, self::EXTRAS_PAGE_SLUG ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'atshift-upf-select2',
			ATSHIFT_UPF_URL . 'assets/js/select2/select2.css',
			array(),
			ATSHIFT_UPF_VERSION
		);

		wp_enqueue_style(
			'atshift-upf-admin',
			ATSHIFT_UPF_URL . 'assets/admin.css',
			array( 'atshift-upf-select2' ),
			ATSHIFT_UPF_VERSION
		);
		wp_add_inline_style(
			'atshift-upf-admin',
			Atshift_UPF_Plugin::get_admin_color_scheme_css( '.atshift-upf' )
		);

		wp_enqueue_script(
			'atshift-upf-select2',
			ATSHIFT_UPF_URL . 'assets/js/select2/select2.min.js',
			array( 'jquery' ),
			ATSHIFT_UPF_VERSION,
			true
		);

		wp_enqueue_script(
			'atshift-upf-admin',
			ATSHIFT_UPF_URL . 'assets/admin.js',
			array( 'jquery', 'jquery-ui-sortable', 'atshift-upf-select2' ),
			ATSHIFT_UPF_VERSION,
			true
		);

		wp_localize_script(
			'atshift-upf-admin',
			'atshiftUPFAdmin',
				array(
					'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
					'screenOptionsNonce' => wp_create_nonce( 'atshift_upf_screen_options' ),
					'toolsNonce'        => wp_create_nonce( 'atshift_upf_tools' ),
					'pageUrl'           => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
					'fieldTypeButtonLabels' => $this->get_field_type_toggle_labels(),
					'singleUseCoreFieldTypes' => $this->single_use_core_field_types(),
					'strings'           => array(
					'automaticallyNamed'      => __( 'Automatically named when saved.', 'atshift-user-profile-fields' ),
					'alwaysShow'              => __( 'Always show', 'atshift-user-profile-fields' ),
						'closeFieldSettings'      => __( 'Close field settings', 'atshift-user-profile-fields' ),
						'openFieldSettings'       => __( 'Open field settings', 'atshift-user-profile-fields' ),
						'resetConfirm'            => __( 'Start over with this field set? All current fields, Extra settings, and editor settings will return to their initial state. Existing user profile values are not deleted. This cannot be undone.', 'atshift-user-profile-fields' ),
						'loadingDefaultFieldSet'   => __( 'Loading the default field set...', 'atshift-user-profile-fields' ),
						'defaultFieldSetFailed'    => __( 'The default field set could not be loaded.', 'atshift-user-profile-fields' ),
						'uploadingFieldSet'       => __( 'Uploading field set...', 'atshift-user-profile-fields' ),
						'fieldSetReadFailed'      => __( 'The selected field set could not be read.', 'atshift-user-profile-fields' ),
						'fieldSetUploadFailed'    => __( 'The field set could not be uploaded.', 'atshift-user-profile-fields' ),
						'newField'                => __( 'New Field', 'atshift-user-profile-fields' ),
						'addFieldBelow'           => __( 'Add new field below', 'atshift-user-profile-fields' ),
						'addFieldInsideGroup'     => __( 'Add field inside group', 'atshift-user-profile-fields' ),
						'standardFieldAlreadyAdded' => __( 'Already added', 'atshift-user-profile-fields' ),
						'initialStateEnabled'     => __( 'Enabled', 'atshift-user-profile-fields' ),
						'initialStateAccountEmail' => __( 'Send account email', 'atshift-user-profile-fields' ),
						/* translators: %s: Conditional choice label. */
						'conditionalBranchDropLabel' => __( 'Condition "%s"', 'atshift-user-profile-fields' ),
						/* translators: %s: Field label. */
						'fieldDisplayLabel'       => __( 'Field "%s"', 'atshift-user-profile-fields' ),
						'listSeparator'           => __( ', ', 'atshift-user-profile-fields' ),
						/* translators: 1: Hidden condition labels, 2: Shown condition labels. */
						'conditionalNewPartial'   => __( 'On the Add New User screen, %1$s are unavailable, so only %2$s are shown.', 'atshift-user-profile-fields' ),
						/* translators: 1: Hidden condition labels, 2: Shown condition labels. */
						'conditionalEditPartial'  => __( 'When editing an existing user, %1$s are unavailable, so only %2$s are shown.', 'atshift-user-profile-fields' ),
						'conditionalNewHidden'    => __( 'This Conditional Group is not shown on the Add New User screen because none of its conditions contain fields available there.', 'atshift-user-profile-fields' ),
						'conditionalEditHidden'   => __( 'This Conditional Group is not shown when editing an existing user because none of its conditions contain fields available there.', 'atshift-user-profile-fields' ),
						/* translators: %s: Omitted field labels. */
						'accordionNewPartial'     => __( 'On the Add New User screen, edit-only fields in this Accordion are omitted: %s.', 'atshift-user-profile-fields' ),
						/* translators: %s: Omitted field labels. */
						'accordionEditPartial'    => __( 'When editing an existing user, registration-only fields in this Accordion are omitted: %s.', 'atshift-user-profile-fields' ),
						'accordionNewHidden'      => __( 'This Accordion is not shown on the Add New User screen because it contains no fields available there.', 'atshift-user-profile-fields' ),
						'accordionEditHidden'     => __( 'This Accordion is not shown when editing an existing user because it contains no fields available there.', 'atshift-user-profile-fields' ),
						'coreUsernameDescription' => $this->get_default_field_description( 'core_username' ),
					'coreEmailDescription'    => $this->get_default_field_description( 'core_email' ),
					'corePasswordDescription' => $this->get_default_field_description( 'core_password' ),
					'additionalNameDescription' => $this->get_default_field_description( 'additional_name' ),
				),
				'legacyDefaultDescriptions' => array_values( array_merge( ...array_values( $this->get_legacy_default_field_descriptions() ) ) ),
			)
		);
	}

	/**
	 * Render admin notices.
	 *
	 * @return void
	 */
	public function render_notices() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice routing.
		if ( empty( $_GET['page'] ) || 'atshift-user-profile-fields' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		if ( Atshift_UPF_Plugin::is_safe_mode() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Emergency safe mode is active. WordPress native profile fields are being used and this plugin is not changing profile screens.', 'atshift-user-profile-fields' ) . '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Status is set by a nonce-protected redirect.
		if ( ! empty( $_GET['atshift_upf_updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'atshift-user-profile-fields' ) . '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Status is set by a nonce-protected redirect.
		if ( ! empty( $_GET['atshift_upf_reset'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'The field set is ready to be created again from the beginning.', 'atshift-user-profile-fields' ) . '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Status is set by a nonce-protected redirect.
		if ( ! empty( $_GET['atshift_upf_imported'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Field set loaded.', 'atshift-user-profile-fields' ) . '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Status is set by a nonce-protected redirect.
		if ( ! empty( $_GET['deleted'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Field deleted.', 'atshift-user-profile-fields' ) . '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice selection.
		if ( ! empty( $_GET['error'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice selection.
			$error = sanitize_key( wp_unslash( $_GET['error'] ) );
			$text  = __( 'The field could not be saved.', 'atshift-user-profile-fields' );

			if ( 'missing_key' === $error ) {
				$text = __( 'Enter a field key before saving.', 'atshift-user-profile-fields' );
			}

			if ( 'duplicate_key' === $error ) {
				$text = __( 'That field key is already used by another field.', 'atshift-user-profile-fields' );
			}

			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
		}
	}

	/**
	 * Handle admin form submissions.
	 *
	 * @return void
	 */
	public function handle_posts() {
		// Each dispatched handler verifies its action-specific nonce before changing data.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['atshift_upf_action'] ) || ! current_user_can( $this->get_capability() ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$action = sanitize_key( wp_unslash( $_POST['atshift_upf_action'] ) );

		if ( 'save_field' === $action ) {
			$this->save_field();
		}

		if ( 'save_fields' === $action ) {
			$this->save_fields();
		}

		if ( 'reset_fields' === $action ) {
			$this->reset_fields();
		}

		if ( 'save_order' === $action ) {
			$this->save_order();
		}

		if ( 'delete_field' === $action ) {
			$this->delete_field();
		}

		if ( in_array( $action, array( 'save_visibility', 'save_display_options' ), true ) ) {
			$this->save_display_options();
		}
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		?>
		<div class="wrap atshift-upf">
			<div class="atshift-upf-page-head">
				<h1>
					<?php esc_html_e( 'Field Management', 'atshift-user-profile-fields' ); ?>
					<?php echo wp_kses_post( apply_filters( 'atshift_upf_admin_field_management_title_suffix', '' ) ); ?>
				</h1>
			</div>
			<?php
			$this->render_fields_tab();
			?>
		</div>
		<?php
	}

	/**
	 * Render the standalone Extras page.
	 *
	 * @return void
	 */
	public function render_extras_page() {
		?>
		<div class="wrap atshift-upf">
			<div class="atshift-upf-page-head">
				<h1><?php esc_html_e( 'Display Settings', 'atshift-user-profile-fields' ); ?></h1>
			</div>
			<form method="post" class="atshift-upf-extras-page-form">
				<?php wp_nonce_field( 'atshift_upf_save_display_options' ); ?>
				<input type="hidden" name="atshift_upf_action" value="save_display_options">
				<input type="hidden" name="atshift_upf_options_context" value="extras">
				<input type="hidden" name="show_extras" value="1">
				<?php $this->render_extras_panel( true ); ?>
				<p class="submit">
					<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save Display Settings', 'atshift-user-profile-fields' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Register custom controls in the WordPress Screen Options drawer.
	 *
	 * @return void
	 */
	public function register_screen_options() {
		add_filter( 'screen_settings', array( $this, 'render_screen_options_meta' ), 10, 2 );
	}

	/**
	 * Render atshift Fields-like display controls in WordPress Screen Options.
	 *
	 * @param string    $settings_html Existing screen settings markup.
	 * @param WP_Screen $screen Current screen.
	 * @return string
	 */
	public function render_screen_options_meta( $settings_html, $screen ) {
		if ( 'toplevel_page_atshift-user-profile-fields' !== $screen->id ) {
			return $settings_html;
		}

		$settings      = Atshift_UPF_Plugin::get_settings();
		$editor_layout = isset( $settings['editor_layout'] ) && 'one' === $settings['editor_layout'] ? 'one' : 'two';

		ob_start();
		?>
		<div class="atshift-upf-display-options-panel">
			<h3><?php esc_html_e( 'Screen elements', 'atshift-user-profile-fields' ); ?></h3>
			<p><?php esc_html_e( 'Choose how the field editor is arranged on this screen.', 'atshift-user-profile-fields' ); ?></p>
			<div class="atshift-upf-display-options-row atshift-upf-display-options-elements">
				<label><input type="checkbox" checked disabled> <?php esc_html_e( 'Fields', 'atshift-user-profile-fields' ); ?></label>
			</div>
			<div class="atshift-upf-display-options-row atshift-upf-display-options-layout">
				<strong><?php esc_html_e( 'Layout', 'atshift-user-profile-fields' ); ?></strong>
				<label><input type="radio" name="editor_layout" value="one" data-atshift-upf-screen-option="layout" <?php checked( $editor_layout, 'one' ); ?>> <?php esc_html_e( '1 column', 'atshift-user-profile-fields' ); ?></label>
				<label><input type="radio" name="editor_layout" value="two" data-atshift-upf-screen-option="layout" <?php checked( $editor_layout, 'two' ); ?>> <?php esc_html_e( '2 columns', 'atshift-user-profile-fields' ); ?></label>
			</div>
		</div>
		<?php

		return $settings_html . ob_get_clean();
	}

	/**
	 * Render field management.
	 *
	 * @return void
	 */
	private function render_fields_tab() {
			$fields     = Atshift_UPF_Plugin::get_fields();
			$edit_field = $this->get_edit_field( $fields );
			$settings   = Atshift_UPF_Plugin::get_settings();
			$layout     = isset( $settings['editor_layout'] ) && 'one' === $settings['editor_layout'] ? 'one' : 'two';
			$enabled    = ! empty( $settings['field_group_enabled'] );
			$field_tree = $this->build_admin_field_tree( $fields );
		?>
		<div class="atshift-upf-editor-columns is-<?php echo esc_attr( $layout ); ?>-column">
			<div class="atshift-upf-editor-main-column">
				<form method="post" class="atshift-upf-fields-form" id="atshift-upf-field-form">
					<section class="atshift-upf-panel atshift-upf-panel-main atshift-upf-cfs-shell">
						<div class="atshift-upf-panel-head">
							<h2><?php esc_html_e( 'Fields', 'atshift-user-profile-fields' ); ?></h2>
						</div>
						<?php wp_nonce_field( 'atshift_upf_save_fields' ); ?>
						<input type="hidden" name="atshift_upf_action" value="save_fields">
						<div class="atshift-upf-cfs-fields" id="atshift-upf-fields">
								<div class="atshift-upf-empty <?php echo empty( $fields ) ? '' : 'is-hidden'; ?>">
									<h3><?php esc_html_e( 'No fields yet', 'atshift-user-profile-fields' ); ?></h3>
									<p><?php esc_html_e( 'Use the buttons below to apply the plugin default field set, upload a field set, or add your first field.', 'atshift-user-profile-fields' ); ?></p>
									<div class="atshift-upf-empty-upload">
										<div class="atshift-upf-empty-actions">
											<button type="button" class="button button-primary" data-atshift-upf-empty-default-trigger>
												<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
												<?php esc_html_e( 'Set the atshift User Profile Fields default field set', 'atshift-user-profile-fields' ); ?>
											</button>
											<button type="button" class="button" data-atshift-upf-empty-upload-trigger>
												<span class="dashicons dashicons-upload" aria-hidden="true"></span>
												<?php esc_html_e( 'Upload Field Set', 'atshift-user-profile-fields' ); ?>
											</button>
										</div>
										<input type="file" accept=".json,application/json" hidden data-atshift-upf-empty-upload-file>
									<p class="atshift-upf-empty-upload-status" role="status" aria-live="polite" data-atshift-upf-empty-upload-status></p>
								</div>
							</div>
							<ul class="fields atshift-upf-sortable-fields" data-next-field-index="<?php echo esc_attr( count( $fields ) ); ?>">
								<?php foreach ( $field_tree['root'] as $field ) : ?>
									<?php $this->render_field_item( $field, $edit_field, 'fields[' . $field_tree['indexes'][ $field['id'] ] . ']', $field_tree ); ?>
								<?php endforeach; ?>
							</ul>
						</div>
						<div class="atshift-upf-add-field-footer">
							<button type="button" class="button button-primary atshift-upf-add-field-link"><?php esc_html_e( 'Add New Field', 'atshift-user-profile-fields' ); ?></button>
						</div>
						<template id="atshift-upf-field-template">
							<?php $this->render_new_field_item(); ?>
						</template>
					</section>
				</form>
			</div>
			<aside class="atshift-upf-editor-side">
					<div class="atshift-upf-publish-box">
						<div class="atshift-upf-publish-head">
							<h3><?php esc_html_e( 'Publish', 'atshift-user-profile-fields' ); ?></h3>
						</div>
						<div class="atshift-upf-publish-status">
							<strong><?php esc_html_e( 'Field Set Status', 'atshift-user-profile-fields' ); ?></strong>
							<div class="atshift-upf-publish-status-options">
								<label><input type="radio" name="field_group_enabled" value="1" form="atshift-upf-field-form" <?php checked( $enabled ); ?>> <?php esc_html_e( 'Enabled', 'atshift-user-profile-fields' ); ?></label>
								<label><input type="radio" name="field_group_enabled" value="0" form="atshift-upf-field-form" <?php checked( ! $enabled ); ?>> <?php esc_html_e( 'Disabled', 'atshift-user-profile-fields' ); ?></label>
							</div>
						</div>
						<div class="atshift-upf-publish-actions">
							<?php if ( ! empty( $fields ) ) : ?>
								<form method="post" class="atshift-upf-reset-form" data-atshift-upf-reset-form>
									<?php wp_nonce_field( 'atshift_upf_reset_fields' ); ?>
									<input type="hidden" name="atshift_upf_action" value="reset_fields">
									<button type="submit" class="atshift-upf-reset-link"><?php esc_html_e( 'Start Over', 'atshift-user-profile-fields' ); ?></button>
								</form>
							<?php endif; ?>
							<button type="submit" class="button button-primary button-large" form="atshift-upf-field-form"><?php esc_html_e( 'Save', 'atshift-user-profile-fields' ); ?></button>
						</div>
					</div>
			</aside>
		</div>
		<?php
	}

	/**
	 * Build a parent-child field tree for the editor.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields.
	 * @return array<string, mixed>
	 */
	private function build_admin_field_tree( $fields ) {
		$tree = array(
			'root'     => array(),
			'children' => array(),
			'indexes'  => array(),
		);
		$structure_ids = array();
		$type_by_id    = array();
		$child_ids     = array();

		foreach ( $fields as $index => $field ) {
			if ( empty( $field['id'] ) ) {
				continue;
			}

			$field_id                    = (string) $field['id'];
			$tree['indexes'][ $field_id ] = $index;

				if ( in_array( isset( $field['type'] ) ? (string) $field['type'] : '', $this->structure_field_types(), true ) ) {
					$structure_ids[ $field_id ] = true;
				}

				$type_by_id[ $field_id ] = isset( $field['type'] ) ? (string) $field['type'] : '';
			}

		foreach ( $fields as $field ) {
			$field_id  = isset( $field['id'] ) ? (string) $field['id'] : '';
			$parent_id = isset( $field['parent_id'] ) ? (string) $field['parent_id'] : '';
			$type      = isset( $field['type'] ) ? (string) $field['type'] : '';

			if (
					'' !== $field_id
					&& '' !== $parent_id
					&& ! empty( $structure_ids[ $parent_id ] )
					&& $this->can_field_type_use_parent( $type, isset( $type_by_id[ $parent_id ] ) ? $type_by_id[ $parent_id ] : '' )
				) {
				if ( empty( $tree['children'][ $parent_id ] ) ) {
					$tree['children'][ $parent_id ] = array();
				}
				$tree['children'][ $parent_id ][] = $field;
				$child_ids[ $field_id ]           = true;
			}
		}

		foreach ( $fields as $field ) {
			$field_id = isset( $field['id'] ) ? (string) $field['id'] : '';

			if ( '' !== $field_id && ! empty( $child_ids[ $field_id ] ) ) {
				continue;
			}

			$tree['root'][] = $field;
		}

		return $tree;
	}

	/**
	 * Render one field row in the group.
	 *
	 * @param array<string, mixed>      $field Field definition.
	 * @param array<string, mixed>|null $edit_field Field currently open.
	 * @return void
	 */
	private function render_field_item( $field, $edit_field, $name_prefix, $field_tree = null ) {
			/**
			 * Allows add-ons to render a custom field row in the field editor.
			 *
			 * Return true after printing the full <li> row.
			 *
			 * @param bool                    $handled Whether the row has been rendered.
			 * @param array<string, mixed>    $field Field definition.
			 * @param array<string, mixed>|null $edit_field Field currently open.
			 * @param string                  $name_prefix Input name prefix, such as fields[0].
			 * @param array<string, mixed>|null $field_tree Prepared field tree.
			 */
			if ( apply_filters( 'atshift_upf_admin_render_field_item', false, $field, $edit_field, $name_prefix, $field_tree ) ) {
				return;
			}

			$is_open = $edit_field && isset( $field['id'], $edit_field['id'] ) && $field['id'] === $edit_field['id'];
			$type    = isset( $field['type'] ) ? $field['type'] : 'text';
			$is_required = ! empty( $field['required'] ) || $this->is_system_required_field_type( $type );
			$is_structure = in_array( $type, $this->structure_field_types(), true );
			$structure_badges = array(
				'group'       => __( 'GROUP', 'atshift-user-profile-fields' ),
				'box'         => __( 'BOX', 'atshift-user-profile-fields' ),
				'conditional' => __( 'CONDITION', 'atshift-user-profile-fields' ),
				'accordion'   => __( 'ACCORDION', 'atshift-user-profile-fields' ),
			);
			$screen_context_label = $this->get_field_screen_context_label( $type );
			?>
			<li data-field-id="<?php echo esc_attr( $field['id'] ); ?>" data-field-type="<?php echo esc_attr( $type ); ?>">
				<div class="field <?php echo $is_open ? 'form_open' : ''; ?>">
					<div class="field_meta">
					<table class="widefat">
						<tbody>
							<tr>
								<td class="field_order">
									<span class="dashicons dashicons-menu" aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'atshift-user-profile-fields' ); ?>"></span>
								</td>
								<td class="field_label">
									<?php if ( $is_required ) : ?>
										<span class="atshift-upf-cfs-chip is-required"><?php esc_html_e( 'Required', 'atshift-user-profile-fields' ); ?></span>
									<?php endif; ?>
									<a class="row-title cfs_edit_field" href="#">
										<?php if ( $is_structure ) : ?>
											<span class="cfs-structure-badge cfs-structure-badge-<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $structure_badges[ $type ] ); ?></span>
										<?php endif; ?>
										<span class="cfs-field-label-text"><?php echo esc_html( $field['label'] ); ?></span>
									</a>
									<?php if ( $screen_context_label ) : ?>
										<span class="atshift-upf-cfs-chip is-context"><?php echo esc_html( $screen_context_label ); ?></span>
									<?php endif; ?>
								</td>
								<td class="field_name">
									<?php echo esc_html( $field['key'] ); ?>
								</td>
								<td class="field_type">
									<a href="#" class="cfs_edit_field cfs-field-type-toggle" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>" title="<?php echo esc_attr( $is_open ? __( 'Close field settings', 'atshift-user-profile-fields' ) : __( 'Open field settings', 'atshift-user-profile-fields' ) ); ?>">
										<span class="cfs-field-type-text"><?php echo esc_html( $this->get_field_type_toggle_label( $type ) ); ?></span>
										<span class="dashicons <?php echo $is_open ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'; ?> cfs-field-toggle-icon" aria-hidden="true"></span>
									</a>
								</td>
							</tr>
						</tbody>
					</table>
					</div>
					<?php $this->render_field_form( $field, $name_prefix, ! $is_open ); ?>
				</div>
				<?php if ( $is_structure ) : ?>
					<ul class="fields atshift-upf-child-fields" data-parent-field-id="<?php echo esc_attr( $field['id'] ); ?>">
						<?php
						if ( is_array( $field_tree ) && ! empty( $field_tree['children'][ $field['id'] ] ) ) :
							foreach ( $field_tree['children'][ $field['id'] ] as $child_field ) :
								$child_id = isset( $child_field['id'] ) ? (string) $child_field['id'] : '';
								if ( '' === $child_id || ! isset( $field_tree['indexes'][ $child_id ] ) ) {
									continue;
								}
								$this->render_field_item( $child_field, $edit_field, 'fields[' . $field_tree['indexes'][ $child_id ] . ']', $field_tree );
							endforeach;
						endif;
						?>
					</ul>
				<?php endif; ?>
			</li>
		<?php
	}

	/**
	 * Render the open new-field row.
	 *
	 * @return void
	 */
	private function render_new_field_item() {
		?>
			<li class="atshift-upf-new-field" data-field-id="new___INDEX__" data-field-type="text">
			<div class="field form_open">
				<div class="field_meta">
					<table class="widefat">
						<tbody>
							<tr>
								<td class="field_order">
									<span class="dashicons dashicons-menu" aria-hidden="true"></span>
								</td>
								<td class="field_label">
									<a class="row-title cfs_edit_field" href="#">
										<span class="cfs-field-label-text"><?php esc_html_e( 'New Field', 'atshift-user-profile-fields' ); ?></span>
									</a>
								</td>
								<td class="field_name"></td>
								<td class="field_type">
									<a href="#" class="cfs_edit_field cfs-field-type-toggle" aria-expanded="true" title="<?php esc_attr_e( 'Close field settings', 'atshift-user-profile-fields' ); ?>">
										<span class="cfs-field-type-text"><?php echo esc_html( $this->get_field_type_toggle_label( 'text' ) ); ?></span>
										<span class="dashicons dashicons-arrow-up-alt2 cfs-field-toggle-icon" aria-hidden="true"></span>
									</a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<?php $this->render_field_form( null, 'fields[__INDEX__]', false ); ?>
			</div>
		</li>
		<?php
	}

	/**
	 * Render a field form.
	 *
	 * @param array<string, mixed>|null $field Field to edit.
	 * @return void
	 */
	private function render_field_form( $field, $name_prefix, $is_hidden ) {
		$defaults = array(
			'id'          => '',
			'key'         => '',
			'label'       => '',
			'type'        => 'text',
			'description' => '',
			'placeholder' => '',
			'choices'     => array(),
			'parent_id'   => '',
			'conditional_value' => '',
				'group_columns' => 2,
				'conditional_input' => 'select',
				'accordion_open' => false,
				'role_control' => 'all',
			'role_control_roles' => array(),
			'required'    => false,
			'validation_enabled' => null,
			'initial_enabled' => null,
			'sort_order'  => 10,
		);
		$field    = wp_parse_args( $field ? $field : array(), $defaults );
		$current_type       = isset( $field['type'] ) ? $field['type'] : 'text';
		$generated_name     = $this->uses_generated_field_key( $current_type );
		$field_key          = $field['key'];
		$selected_parent    = isset( $field['parent_id'] ) ? (string) $field['parent_id'] : '';
		$validation_enabled = in_array( $current_type, array( 'email', 'url', 'phone' ), true ) && ( null === $field['validation_enabled'] || ! empty( $field['validation_enabled'] ) );
		$initial_enabled    = Atshift_UPF_Plugin::get_field_initial_enabled( $field );
		$field['description'] = $this->normalize_default_field_description( $current_type, (string) $field['description'] );
		?>
			<div class="atshift-upf-cfs-form field_form" <?php echo $is_hidden ? 'style="display:none;"' : ''; ?>>
				<input type="hidden" name="<?php echo esc_attr( $name_prefix . '[field_id]' ); ?>" value="<?php echo esc_attr( $field['id'] ); ?>">
				<input type="hidden" name="<?php echo esc_attr( $name_prefix . '[client_id]' ); ?>" class="atshift-upf-client-id-input" value="<?php echo esc_attr( $field['id'] ); ?>">
				<input type="hidden" name="<?php echo esc_attr( $name_prefix . '[parent_id]' ); ?>" class="atshift-upf-parent-select" value="<?php echo esc_attr( $selected_parent ); ?>">
				<input type="hidden" name="<?php echo esc_attr( $name_prefix . '[conditional_value]' ); ?>" class="atshift-upf-conditional-value-select" value="<?php echo esc_attr( $field['conditional_value'] ); ?>" data-current-value="<?php echo esc_attr( $field['conditional_value'] ); ?>">

			<div class="atshift-upf-editor-main">
				<table class="widefat">
					<tbody>
							<tr class="field_basics">
								<td colspan="2">
									<table>
										<tbody>
											<tr>
												<td class="field_label">
													<label>
														<?php esc_html_e( 'Label', 'atshift-user-profile-fields' ); ?>
														<span class="cfs_tooltip">?<span class="tooltip_inner"><?php esc_html_e( 'The field label shown on the user profile screen.', 'atshift-user-profile-fields' ); ?></span></span>
													</label>
													<input type="text" name="<?php echo esc_attr( $name_prefix . '[label]' ); ?>" value="<?php echo esc_attr( $field['label'] ); ?>" required>
												</td>
												<td class="field_name">
													<label>
														<?php esc_html_e( 'Name', 'atshift-user-profile-fields' ); ?>
														<span class="cfs_tooltip">?<span class="tooltip_inner"><?php esc_html_e( 'This field name is generated automatically and is used as the saved field key.', 'atshift-user-profile-fields' ); ?></span></span>
													</label>
													<input type="hidden" name="<?php echo esc_attr( $name_prefix . '[field_key]' ); ?>" class="atshift-upf-field-key-input" value="<?php echo esc_attr( $field_key ); ?>">
													<span class="atshift-upf-generated-field-key-label"><?php echo esc_html( $this->generated_field_key_label( $current_type, $field_key ) ); ?></span>
												</td>
												<td class="field_type">
													<label><?php esc_html_e( 'Field Type', 'atshift-user-profile-fields' ); ?></label>
													<?php $this->render_field_type_select( $current_type, $name_prefix . '[type]' ); ?>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
							<tr class="field_conditional_value atshift-upf-condition-assignment-row" hidden>
								<td class="label">
									<label><?php esc_html_e( 'Display for choice', 'atshift-user-profile-fields' ); ?></label>
									<p class="description"><?php esc_html_e( 'Choose the parent Conditional Group value that displays this field.', 'atshift-user-profile-fields' ); ?></p>
								</td>
								<td>
									<select class="atshift-upf-condition-assignment-select" aria-label="<?php esc_attr_e( 'Display for choice', 'atshift-user-profile-fields' ); ?>"></select>
								</td>
							</tr>
							<tr class="<?php echo in_array( $current_type, array( 'radio', 'select', 'conditional' ), true ) ? '' : 'atshift-upf-type-option-hidden'; ?>" data-atshift-upf-types="radio select conditional">
								<td class="label">
									<label><?php esc_html_e( 'Choices', 'atshift-user-profile-fields' ); ?></label>
									<p class="description"><?php esc_html_e( 'One choice per line.', 'atshift-user-profile-fields' ); ?></p>
								</td>
								<td>
									<textarea name="<?php echo esc_attr( $name_prefix . '[choices]' ); ?>" rows="4" placeholder="<?php esc_attr_e( 'One choice per line', 'atshift-user-profile-fields' ); ?>"><?php echo esc_textarea( implode( "\n", (array) $field['choices'] ) ); ?></textarea>
								</td>
							</tr>
							<tr class="<?php echo 'group' === $current_type ? '' : 'atshift-upf-type-option-hidden'; ?>" data-atshift-upf-types="group">
								<td class="label">
									<label><?php esc_html_e( 'Columns', 'atshift-user-profile-fields' ); ?></label>
								</td>
								<td>
									<select name="<?php echo esc_attr( $name_prefix . '[group_columns]' ); ?>">
										<option value="2" <?php selected( (int) $field['group_columns'], 2 ); ?>><?php esc_html_e( '2 columns', 'atshift-user-profile-fields' ); ?></option>
										<option value="3" <?php selected( (int) $field['group_columns'], 3 ); ?>><?php esc_html_e( '3 columns', 'atshift-user-profile-fields' ); ?></option>
									</select>
								</td>
							</tr>
							<tr class="<?php echo 'conditional' === $current_type ? '' : 'atshift-upf-type-option-hidden'; ?>" data-atshift-upf-types="conditional">
								<td class="label">
									<label><?php esc_html_e( 'Display Control', 'atshift-user-profile-fields' ); ?></label>
								</td>
								<td>
									<select name="<?php echo esc_attr( $name_prefix . '[conditional_input]' ); ?>">
										<option value="select" <?php selected( $field['conditional_input'], 'select' ); ?>><?php esc_html_e( 'Select', 'atshift-user-profile-fields' ); ?></option>
										<option value="radio" <?php selected( $field['conditional_input'], 'radio' ); ?>><?php esc_html_e( 'Radio', 'atshift-user-profile-fields' ); ?></option>
									</select>
								</td>
							</tr>
							<tr class="<?php echo 'accordion' === $current_type ? '' : 'atshift-upf-type-option-hidden'; ?>" data-atshift-upf-types="accordion">
								<td class="label">
									<label><?php esc_html_e( 'Initial State', 'atshift-user-profile-fields' ); ?></label>
								</td>
								<td class="atshift-upf-cfs-checks">
									<label><input type="checkbox" name="<?php echo esc_attr( $name_prefix . '[accordion_open]' ); ?>" value="1" <?php checked( ! empty( $field['accordion_open'] ) ); ?>> <?php esc_html_e( 'Open by default', 'atshift-user-profile-fields' ); ?></label>
								</td>
							</tr>
							<tr class="<?php echo Atshift_UPF_Plugin::supports_initial_state( $current_type ) ? '' : 'atshift-upf-type-option-hidden'; ?>" data-atshift-upf-types="<?php echo esc_attr( implode( ' ', array_keys( Atshift_UPF_Plugin::get_initial_state_defaults() ) ) ); ?>">
								<td class="label">
									<label>
										<?php esc_html_e( 'Initial State', 'atshift-user-profile-fields' ); ?>
										<span class="cfs_tooltip">?<span class="tooltip_inner"><?php esc_html_e( 'Applied only when creating a new user. Existing users keep their saved setting.', 'atshift-user-profile-fields' ); ?></span></span>
									</label>
								</td>
								<td>
									<div class="atshift-upf-initial-state-control">
										<strong class="atshift-upf-initial-state-subject">
											<?php echo esc_html( 'core_notification' === $current_type ? __( 'Send account email', 'atshift-user-profile-fields' ) : __( 'Enabled', 'atshift-user-profile-fields' ) ); ?>
										</strong>
										<label class="atshift-upf-switch-control">
											<input type="checkbox" name="<?php echo esc_attr( $name_prefix . '[initial_enabled]' ); ?>" value="1" <?php checked( $initial_enabled ); ?> aria-label="<?php esc_attr_e( 'Initial State', 'atshift-user-profile-fields' ); ?>">
											<span class="atshift-upf-switch-track" aria-hidden="true"></span>
											<span class="atshift-upf-switch-state" aria-hidden="true">
												<span class="is-on">ON</span>
												<span class="is-off">OFF</span>
											</span>
										</label>
									</div>
								</td>
							</tr>
							<tr class="<?php echo in_array( $current_type, array( 'text', 'textarea', 'email', 'url', 'phone', 'number', 'additional_name' ), true ) ? '' : 'atshift-upf-type-option-hidden'; ?>" data-atshift-upf-types="text textarea email url phone number additional_name">
								<td class="label">
									<label><?php esc_html_e( 'Placeholder', 'atshift-user-profile-fields' ); ?></label>
								</td>
								<td>
									<input type="text" name="<?php echo esc_attr( $name_prefix . '[placeholder]' ); ?>" value="<?php echo esc_attr( $field['placeholder'] ); ?>">
								</td>
							</tr>
							<tr class="field_notes">
								<td class="label">
									<label>
										<?php esc_html_e( 'Notes', 'atshift-user-profile-fields' ); ?>
										<span class="cfs_tooltip">?<span class="tooltip_inner"><?php esc_html_e( 'Notes for profile editors during data entry.', 'atshift-user-profile-fields' ); ?></span></span>
									</label>
								</td>
								<td>
									<textarea name="<?php echo esc_attr( $name_prefix . '[description]' ); ?>" rows="3"><?php echo esc_textarea( $field['description'] ); ?></textarea>
								</td>
							</tr>
							<tr class="<?php echo in_array( $current_type, $this->validatable_field_types(), true ) ? '' : 'atshift-upf-type-option-hidden'; ?>" data-atshift-upf-types="<?php echo esc_attr( implode( ' ', $this->validatable_field_types() ) ); ?>">
								<td class="label">
									<label><?php esc_html_e( 'Validation', 'atshift-user-profile-fields' ); ?></label>
								</td>
								<td class="atshift-upf-cfs-checks">
									<label><input type="checkbox" name="<?php echo esc_attr( $name_prefix . '[required]' ); ?>" value="1" <?php checked( ! empty( $field['required'] ) ); ?>> <?php esc_html_e( 'Required', 'atshift-user-profile-fields' ); ?></label>
								</td>
							</tr>
							<tr class="<?php echo in_array( $current_type, array( 'email', 'url', 'phone' ), true ) ? '' : 'atshift-upf-type-option-hidden'; ?>" data-atshift-upf-types="email url phone">
								<td class="label">
									<label><?php esc_html_e( 'Format Validation', 'atshift-user-profile-fields' ); ?></label>
								</td>
								<td class="atshift-upf-cfs-checks">
									<label><input type="checkbox" name="<?php echo esc_attr( $name_prefix . '[validation_enabled]' ); ?>" value="1" <?php checked( $validation_enabled ); ?>> <?php esc_html_e( 'Enabled', 'atshift-user-profile-fields' ); ?></label>
								</td>
							</tr>
							<tr class="<?php echo in_array( $current_type, $this->role_controlled_field_types(), true ) ? '' : 'atshift-upf-type-option-hidden'; ?>" data-atshift-upf-types="<?php echo esc_attr( implode( ' ', $this->role_controlled_field_types() ) ); ?>">
								<td class="label">
									<label>
										<?php esc_html_e( 'Allowed User Role Groups', 'atshift-user-profile-fields' ); ?>
										<span class="cfs_tooltip">?<span class="tooltip_inner"><?php esc_html_e( 'Leave blank to allow every user role.', 'atshift-user-profile-fields' ); ?></span></span>
									</label>
								</td>
								<td class="atshift-upf-role-control">
									<select name="<?php echo esc_attr( $name_prefix . '[role_control_roles][]' ); ?>" class="select2 multiple atshift-upf-role-control-select" multiple data-placeholder="<?php esc_attr_e( 'Leave blank to allow any role that can edit this post', 'atshift-user-profile-fields' ); ?>">
										<?php foreach ( $this->get_editable_roles() as $role_key => $role_label ) : ?>
											<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( in_array( $role_key, (array) $field['role_control_roles'], true ) ); ?>><?php echo esc_html( $role_label ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<?php
							/**
							 * Fires inside a field settings table before the action row.
							 *
							 * Add-ons can print additional <tr> settings using the provided
							 * field input name prefix.
							 *
							 * @param array<string, mixed> $field Current field definition with defaults.
							 * @param string               $name_prefix Input name prefix, such as fields[0].
							 * @param string               $current_type Current field type.
							 */
							do_action( 'atshift_upf_render_field_settings', $field, $name_prefix, $current_type );
							?>
							<tr class="field_actions">
								<td class="label"></td>
								<td>
									<div class="cfs-field-actions">
										<button type="button" class="button button-secondary cfs_edit_field"><?php esc_html_e( 'Close', 'atshift-user-profile-fields' ); ?></button>
										<button type="button" class="button button-primary atshift-upf-add-field-below"><?php esc_html_e( 'Add new field below', 'atshift-user-profile-fields' ); ?></button>
										<div class="cfs-field-action-menu">
											<button type="button" class="button button-secondary atshift-upf-field-actions-toggle" aria-haspopup="true" aria-expanded="false">
												<?php esc_html_e( 'Actions', 'atshift-user-profile-fields' ); ?>
												<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
											</button>
											<ul class="cfs-field-action-menu-list" hidden>
												<li><button type="button" class="button-link atshift-upf-duplicate-field"><?php esc_html_e( 'Duplicate', 'atshift-user-profile-fields' ); ?></button></li>
												<li><button type="button" class="button-link atshift-upf-delete-field"><?php esc_html_e( 'delete', 'atshift-user-profile-fields' ); ?></button></li>
											</ul>
										</div>
									</div>
								</td>
							</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render field types grouped like an authoring menu.
	 *
	 * @param string $current_type Current field type.
	 * @return void
	 */
	private function render_field_type_select( $current_type, $name ) {
		$groups = array(
			__( 'Profile Fields', 'atshift-user-profile-fields' ) => array(
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
			),
				__( 'Groups', 'atshift-user-profile-fields' ) => array(
					'group',
					'box',
					'conditional',
					'accordion',
				),
			__( 'Default Profile Fields', 'atshift-user-profile-fields' ) => array(
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
			),
		);
		/**
		 * Filters field type groups shown in the editor select menu.
		 *
		 * @param array<string, array<int, string>> $groups Field type groups.
		 */
		$groups = apply_filters( 'atshift_upf_admin_field_type_groups', $groups );
		?>
		<select name="<?php echo esc_attr( $name ); ?>" class="atshift-upf-field-type-select">
			<?php if ( ! isset( $this->field_types[ $current_type ] ) ) : ?>
				<option value="<?php echo esc_attr( $current_type ); ?>" selected><?php echo esc_html( $current_type ); ?></option>
			<?php endif; ?>
			<?php foreach ( $groups as $group_label => $types ) : ?>
				<optgroup label="<?php echo esc_attr( $group_label ); ?>">
					<?php foreach ( $types as $type ) : ?>
						<?php if ( ! isset( $this->field_types[ $type ] ) ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $current_type, $type ); ?>><?php echo esc_html( $this->field_types[ $type ] ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Field types that can be placed inside a group.
	 *
	 * @return array<int, string>
	 */
	private function child_field_types() {
		return array_values(
			array_diff(
				array_keys( $this->field_types ),
				$this->structure_field_types()
			)
		);
	}

	/**
	 * Field types that only organize child fields.
	 *
	 * @return array<int, string>
	 */
	private function structure_field_types() {
		return array( 'group', 'box', 'conditional', 'accordion' );
	}

	/**
	 * Check whether a field type can be placed under a structure parent type.
	 *
	 * Mirrors the atshift Fields editor rules: horizontal groups accept only
	 * input fields, box groups can contain one conditional layer, accordions
	 * can contain groups, and conditionals cannot contain another conditional.
	 *
	 * @param string $type        Child field type.
	 * @param string $parent_type Parent field type.
	 * @return bool
	 */
	private function can_field_type_use_parent( $type, $parent_type ) {
		if ( ! in_array( $parent_type, $this->structure_field_types(), true ) ) {
			return false;
		}

		if ( 'group' === $parent_type && in_array( $type, $this->structure_field_types(), true ) ) {
			return false;
		}

		if ( 'box' === $parent_type && in_array( $type, $this->structure_field_types(), true ) && 'conditional' !== $type ) {
			return false;
		}

		if ( 'conditional' === $parent_type && 'conditional' === $type ) {
			return false;
		}

		return true;
	}

	/**
	 * Field types that support the required setting.
	 *
	 * @return array<int, string>
	 */
	private function validatable_field_types() {
		return array_values(
			array_diff(
				array_merge( $this->child_field_types(), array( 'conditional' ) ),
				array_merge(
					$this->system_validated_field_types(),
					array(
						'core_visual_editor',
						'core_admin_color',
						'core_syntax_highlighting',
						'core_keyboard_shortcuts',
						'core_toolbar',
						'core_language',
						'core_profile_picture',
						'core_sessions',
						'core_application_passwords',
						'core_notification',
						'core_role',
						'core_submit_button',
					)
				)
			)
		);
	}

	/**
	 * Field types that are required by WordPress even without a required setting.
	 *
	 * @param string $type Field type.
	 * @return bool
	 */
	private function is_system_required_field_type( $type ) {
		return in_array( $type, array( 'core_username', 'core_email', 'core_password' ), true );
	}

	/**
	 * Field types that have fixed validation behavior.
	 *
	 * @return array<int, string>
	 */
	private function system_validated_field_types() {
		return array( 'core_username', 'core_email', 'core_password' );
	}

	/**
	 * Field types that support role-based display control.
	 *
	 * @return array<int, string>
	 */
	private function role_controlled_field_types() {
		return array(
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
	}

	/**
	 * Default profile field types that can be added only once.
	 *
	 * @return array<int, string>
	 */
	private function single_use_core_field_types() {
		return array_values(
			array_filter(
				array_keys( $this->field_types ),
				static function ( $type ) {
					return 0 === strpos( $type, 'core_' );
				}
			)
		);
	}

	/**
	 * Return a short label describing when a field appears.
	 *
	 * @param string $type Field type.
	 * @return string
	 */
	private function get_field_screen_context_label( $type ) {
		if ( in_array( $type, $this->structure_field_types(), true ) ) {
			return '';
		}

		if ( 'core_notification' === $type ) {
			return __( 'Registration only', 'atshift-user-profile-fields' );
		}

		if ( in_array( $type, array( 'core_visual_editor', 'core_admin_color', 'core_syntax_highlighting', 'core_keyboard_shortcuts', 'core_toolbar', 'core_nickname', 'core_display_name', 'core_bio', 'core_profile_picture', 'core_sessions', 'core_application_passwords' ), true ) ) {
			return __( 'Edit only', 'atshift-user-profile-fields' );
		}

		if ( isset( $this->field_types[ $type ] ) ) {
			return __( 'Registration / Edit', 'atshift-user-profile-fields' );
		}

		return '';
	}

	/**
	 * Return editable role labels.
	 *
	 * @return array<string, string>
	 */
	private function get_editable_roles() {
		$roles = wp_roles()->roles;
		$items = array();

		foreach ( $roles as $role_key => $role ) {
			$items[ $role_key ] = translate_user_role( $role['name'] );
		}

		return $items;
	}

	/**
	 * Sanitize role control settings for one field.
	 *
	 * @param array<string, mixed> $raw_field Raw submitted field.
	 * @param string               $type Field type.
	 * @return array{mode:string,roles:array<int,string>}
	 */
	private function sanitize_role_control_settings( $raw_field, $type ) {
		if ( ! in_array( $type, $this->role_controlled_field_types(), true ) ) {
			return array(
				'mode'  => 'all',
				'roles' => array(),
			);
		}

		$allowed_roles = array_keys( $this->get_editable_roles() );
		$roles         = isset( $raw_field['role_control_roles'] ) ? (array) $raw_field['role_control_roles'] : array();
		$roles         = array_values( array_intersect( array_map( 'sanitize_key', $roles ), $allowed_roles ) );
		$mode          = ! empty( $roles ) ? 'selected' : 'all';

		return array(
			'mode'  => $mode,
			'roles' => $roles,
		);
	}

	/**
	 * Return the field type labels shown on atshift Fields-style open/close buttons.
	 *
	 * @return array<string, string>
	 */
	private function get_field_type_toggle_labels() {
		return $this->field_types;
	}

	/**
	 * Return one atshift Fields-style open/close button label.
	 *
	 * @param string $type Field type.
	 * @return string
	 */
	private function get_field_type_toggle_label( $type ) {
		$labels = $this->get_field_type_toggle_labels();
		return isset( $labels[ $type ] ) ? $labels[ $type ] : $type;
	}

	/**
	 * Default editable notes for selected default profile fields.
	 *
	 * @param string $type Field type.
	 * @return string
	 */
	private function get_default_field_description( $type ) {
			$descriptions = array(
				'core_username' => __( 'Enter the username required for login using half-width letters, numbers, and allowed symbols (_ . - @). Spaces cannot be used. This cannot be changed later.', 'atshift-user-profile-fields' ),
				'core_email'    => __( 'This email address is used for password resets and other account notifications. It can be changed at any time and can also be used instead of the user ID when logging in.', 'atshift-user-profile-fields' ),
				'core_password' => __( 'Use a password that is difficult to guess, combines letters, numbers, and symbols, and is at least 8 characters long.', 'atshift-user-profile-fields' ),
				'core_submit_button' => __( 'Place the user creation and profile save button inside this field group.', 'atshift-user-profile-fields' ),
				'additional_name' => __( 'Use this field for middle, full, phonetic, preferred, or other name details that do not fit first and last name.', 'atshift-user-profile-fields' ),
			);

		return isset( $descriptions[ $type ] ) ? $descriptions[ $type ] : '';
	}

	/**
	 * Replace older generated note text with the current default note.
	 *
	 * @param string $type Field type.
	 * @param string $description Current description.
	 * @return string
	 */
	private function normalize_default_field_description( $type, $description ) {
		$current = $this->get_default_field_description( $type );
		if ( '' === $current || '' === trim( $description ) ) {
			return $description;
		}

		$legacy = $this->get_legacy_default_field_descriptions();

		if ( isset( $legacy[ $type ] ) && in_array( $description, $legacy[ $type ], true ) ) {
			return $current;
		}

		return $description;
	}

	/**
	 * Older generated note text kept only so it can be replaced safely.
	 *
	 * @return array<string, array<int, string>>
	 */
	private function get_legacy_default_field_descriptions() {
		return array(
			'core_email'    => array(
				__( 'This information is needed for password resets and similar account actions. The email address can be changed whenever needed. It can also be used instead of the user ID when logging in.', 'atshift-user-profile-fields' ),
			),
			'core_password' => array(
				__( 'The password must be difficult to guess, use a combination of letters, numbers, and symbols, and be at least 8 characters long.', 'atshift-user-profile-fields' ),
			),
		);
	}

	/**
	 * Return structure fields that can act as a parent.
	 *
	 * @param array<int, array<string, mixed>> $fields Current fields.
	 * @param string                          $current_id Current field ID.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_structure_parent_options( $fields, $current_id ) {
		return array_values(
			array_filter(
				$fields,
				static function ( $field ) use ( $current_id ) {
					return ! empty( $field['id'] )
						&& (string) $current_id !== (string) $field['id']
							&& in_array( isset( $field['type'] ) ? $field['type'] : '', array( 'group', 'box', 'conditional', 'accordion' ), true );
				}
			)
		);
	}

	/**
	 * Return choices for a selected conditional parent.
	 *
	 * @param array<int, array<string, mixed>> $parents Parent fields.
	 * @param string                          $parent_id Selected parent ID.
	 * @return array<int, string>
	 */
	private function get_parent_choices( $parents, $parent_id ) {
		foreach ( $parents as $parent ) {
			if ( isset( $parent['id'], $parent['type'] ) && (string) $parent_id === (string) $parent['id'] && 'conditional' === $parent['type'] ) {
				return isset( $parent['choices'] ) && is_array( $parent['choices'] ) ? array_values( $parent['choices'] ) : array();
			}
		}

		return array();
	}

	/**
	 * Whether a type uses an automatically managed field key.
	 *
	 * @param string $type Field type.
	 * @return bool
	 */
	private function uses_generated_field_key( $type ) {
		return isset( $this->field_types[ $type ] );
	}

	/**
	 * Build the label shown when a field key is managed automatically.
	 *
	 * @param string $type Field type.
	 * @param string $key Current field key.
	 * @return string
	 */
	private function generated_field_key_label( $type, $key ) {
		if ( ! empty( $key ) ) {
			return $key;
		}

		return __( 'Automatically named when saved.', 'atshift-user-profile-fields' );
	}

	/**
	 * Generate a stable field key for generated-key field types.
	 *
	 * @param string $type Field type.
	 * @param string                  $field_id Field ID.
	 * @param array<int|string,mixed> $reserved_keys Reserved field keys.
	 * @return string
	 */
	private function generate_field_key( $type, $field_id, $reserved_keys = array() ) {
		$core_map = array(
			'core_username'     => 'user_login',
			'core_email'        => 'user_email',
			'core_visual_editor' => 'rich_editing',
			'core_admin_color'  => 'admin_color',
			'core_syntax_highlighting' => 'syntax_highlighting',
			'core_keyboard_shortcuts' => 'comment_shortcuts',
			'core_toolbar'      => 'show_admin_bar_front',
			'core_first_name'   => 'first_name',
				'core_last_name'    => 'last_name',
				'core_nickname'     => 'nickname',
				'core_display_name' => 'display_name',
				'additional_name'   => 'additional_name',
				'core_language'     => 'locale',
			'core_website'      => 'user_url',
			'core_bio'          => 'description',
			'core_profile_picture' => 'profile_picture',
			'core_password'     => 'password',
			'core_sessions'     => 'sessions',
			'core_application_passwords' => 'application_passwords',
			'core_notification' => 'send_user_notification',
			'core_role'         => 'role',
			'core_submit_button' => 'submit_button',
		);

		if ( isset( $core_map[ $type ] ) ) {
			return $core_map[ $type ];
		}

		return $this->generate_numbered_field_key( $type, $reserved_keys );
	}

	/**
	 * Generate a readable numbered field key.
	 *
	 * @param string                  $type Field type.
	 * @param array<int|string,mixed> $reserved_keys Reserved field keys.
	 * @return string
	 */
	private function generate_numbered_field_key( $type, $reserved_keys = array() ) {
		$base     = sanitize_key( $type );
		$reserved = array();
		$number   = 1;

		if ( '' === $base ) {
			$base = 'field';
		}

		foreach ( $reserved_keys as $key => $value ) {
			$reserved_key = is_int( $key ) ? (string) $value : (string) $key;
			if ( '' === $reserved_key ) {
				continue;
			}

			$reserved[ sanitize_key( $reserved_key ) ] = true;
		}

		while ( isset( $reserved[ $base . '_' . $number ] ) ) {
			$number++;
		}

		return $base . '_' . $number;
	}

	/**
	 * Detect old generated keys that exposed internal UUID-style field IDs.
	 *
	 * @param string $type Field type.
	 * @param string $key Field key.
	 * @return bool
	 */
	private function is_legacy_generated_field_key( $type, $key ) {
		$type = sanitize_key( $type );
		$key  = sanitize_key( $key );

		if ( '' === $type || '' === $key ) {
			return false;
		}

		return 1 === preg_match( '/^' . preg_quote( $type, '/' ) . '_field_[a-f0-9_]{12,}$/', $key );
	}

	/**
	 * Get a saved field type by field ID.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields.
	 * @param string                          $field_id Field ID.
	 * @return string
	 */
	private function get_field_type_by_id( $fields, $field_id ) {
		foreach ( $fields as $field ) {
			if ( ! empty( $field['id'] ) && (string) $field_id === (string) $field['id'] ) {
				return isset( $field['type'] ) ? (string) $field['type'] : '';
			}
		}

		return '';
	}

	/**
	 * Render extra profile settings.
	 *
	 * @param bool $force_visible Whether to render the panel visible regardless of screen option state.
	 * @return void
	 */
	private function render_extras_panel( $force_visible = false ) {
		$settings     = Atshift_UPF_Plugin::get_settings();
		$show_extras  = $force_visible || ! empty( $settings['show_extras'] );
		$hidden       = (array) $settings['hidden_core_fields'];
		$disabled     = (array) $settings['disabled_hidden_core_fields'];
		$core_options = Atshift_UPF_Profile::get_core_field_options();
		$groups       = $this->get_extra_field_groups();
		$locked       = $this->get_used_core_extra_fields();
		?>
		<section class="atshift-upf-panel atshift-upf-extras-panel <?php echo $show_extras ? '' : 'is-hidden'; ?>" <?php echo $show_extras ? '' : 'hidden'; ?>>
			<div class="atshift-upf-panel-head">
				<h2><?php esc_html_e( 'Display Settings', 'atshift-user-profile-fields' ); ?></h2>
			</div>
			<div class="atshift-upf-extras-form">
				<p class="description"><?php esc_html_e( 'Hide WordPress and supported plugin profile items that are not needed for this site.', 'atshift-user-profile-fields' ); ?></p>
				<p class="description"><?php esc_html_e( 'Hiding an item does not change its current setting. For supported checkbox features, use the additional option to turn the feature off.', 'atshift-user-profile-fields' ); ?></p>
				<p class="description"><?php esc_html_e( 'Items with locked checkboxes are managed in Fields. Remove the field before configuring the item here.', 'atshift-user-profile-fields' ); ?></p>
				<div class="atshift-upf-extras-groups">
					<?php foreach ( $groups as $group ) : ?>
						<div class="atshift-upf-extras-group">
							<h3><?php echo esc_html( $group['label'] ); ?></h3>
							<?php if ( ! empty( $group['description'] ) ) : ?>
								<p class="atshift-upf-extras-group-description"><?php echo esc_html( $group['description'] ); ?></p>
							<?php endif; ?>
							<div class="atshift-upf-extras-options">
								<?php foreach ( $group['fields'] as $key ) : ?>
									<?php if ( empty( $core_options[ $key ] ) ) : ?>
										<?php continue; ?>
									<?php endif; ?>
									<?php
									$is_locked          = in_array( $key, $locked, true );
									$is_checked         = $is_locked || in_array( $key, $hidden, true );
									$can_disable        = ! empty( $core_options[ $key ]['off_label'] );
									$is_disabled_hidden = ! $is_locked && in_array( $key, $disabled, true );
									?>
									<div class="atshift-upf-extras-option" data-atshift-upf-extra-key="<?php echo esc_attr( $key ); ?>">
										<label class="atshift-upf-extras-main">
											<?php if ( $is_locked ) : ?>
												<input type="hidden" name="hidden_core_fields[]" value="<?php echo esc_attr( $key ); ?>">
											<?php endif; ?>
											<input type="checkbox" name="hidden_core_fields[]" value="<?php echo esc_attr( $key ); ?>" data-atshift-upf-extra-hide <?php checked( $is_checked ); ?> <?php disabled( $is_locked ); ?>>
											<span>
												<strong>
													<?php
													printf(
														/* translators: %s: WordPress profile item label. */
														esc_html__( 'Hide %s', 'atshift-user-profile-fields' ),
														esc_html( $core_options[ $key ]['label'] )
													);
													?>
												</strong>
												<small><?php echo esc_html( $core_options[ $key ]['description'] ); ?></small>
											</span>
										</label>
										<?php if ( $can_disable && ! $is_locked ) : ?>
											<div class="atshift-upf-extras-off-option <?php echo $is_checked ? '' : 'is-disabled'; ?>">
												<label>
													<input type="checkbox" name="disabled_hidden_core_fields[]" value="<?php echo esc_attr( $key ); ?>" data-atshift-upf-disable-hidden-feature <?php checked( $is_disabled_hidden ); ?> <?php disabled( ! $is_checked ); ?>>
													<span>
														<strong><?php echo esc_html( $core_options[ $key ]['off_label'] ); ?></strong>
														<small>
															<?php
															echo esc_html(
																'notification' === $key
																	? __( 'Applied when a new user is added.', 'atshift-user-profile-fields' )
																	: __( 'Applied when the profile is next saved.', 'atshift-user-profile-fields' )
															);
															?>
														</small>
													</span>
												</label>
											</div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<label class="atshift-upf-inline-check">
					<input type="checkbox" name="apply_to_own_profile" value="1" <?php checked( ! empty( $settings['apply_to_own_profile'] ) ); ?>>
					<?php esc_html_e( 'Apply these display settings to my own profile screen too.', 'atshift-user-profile-fields' ); ?>
				</label>
			</div>
		</section>
		<?php
	}

	/**
	 * Return grouped default profile fields for the Extras panel.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_extra_field_groups() {
		$groups = array(
			array(
				'label'       => __( 'Required fields during account creation', 'atshift-user-profile-fields' ),
				'description' => __( 'Fields needed to create a WordPress account.', 'atshift-user-profile-fields' ),
				'fields'      => array(
					'username',
					'email',
					'password',
					'submit_button',
				),
			),
			array(
				'label'       => __( 'Feature items during account creation', 'atshift-user-profile-fields' ),
				'description' => __( 'Settings often checked when adding or inviting a user.', 'atshift-user-profile-fields' ),
				'fields'      => array(
					'role',
					'notification',
					'language',
				),
			),
			array(
				'label'       => __( 'Field items after account creation', 'atshift-user-profile-fields' ),
				'description' => __( 'Profile fields used after the account exists.', 'atshift-user-profile-fields' ),
				'fields'      => array(
					'first_name',
					'last_name',
					'nickname',
					'display_name',
					'website',
					'bio',
					'profile_picture',
				),
			),
			array(
				'label'       => __( 'Feature items after account creation', 'atshift-user-profile-fields' ),
				'description' => __( 'Profile screen features and account management items used after the account exists.', 'atshift-user-profile-fields' ),
				'fields'      => array(
					'visual_editor',
					'admin_color',
					'syntax_highlighting',
					'keyboard_shortcuts',
					'toolbar',
					'sessions',
					'application_passwords',
				),
			),
		);

		if ( defined( 'URE_VERSION' ) ) {
			$groups[] = array(
				'label'       => __( 'Other plugin profile items', 'atshift-user-profile-fields' ),
				'description' => __( 'Profile items added by supported plugins.', 'atshift-user-profile-fields' ),
				'fields'      => array(
					'ure_additional_capabilities',
				),
			);
		}

		return $groups;
	}

	/**
	 * Return extra option keys currently represented by default profile fields.
	 *
	 * @return array<int, string>
	 */
	private function get_used_core_extra_fields() {
		$settings = Atshift_UPF_Plugin::get_settings();

		if ( empty( $settings['field_group_enabled'] ) ) {
			return array();
		}

		$map  = $this->get_core_extra_field_type_map();
		$used = array();

		foreach ( Atshift_UPF_Plugin::get_enabled_fields() as $field ) {
			$type = isset( $field['type'] ) ? (string) $field['type'] : '';
			if ( isset( $map[ $type ] ) ) {
				$used[] = $map[ $type ];
			}
		}

		return array_values( array_unique( $used ) );
	}

	/**
	 * Return core field types mapped to Extras hidden option keys.
	 *
	 * @return array<string, string>
	 */
	private function get_core_extra_field_type_map() {
		return array(
			'core_username'     => 'username',
			'core_email'        => 'email',
			'core_visual_editor' => 'visual_editor',
			'core_admin_color'  => 'admin_color',
			'core_syntax_highlighting' => 'syntax_highlighting',
			'core_keyboard_shortcuts' => 'keyboard_shortcuts',
			'core_toolbar'      => 'toolbar',
			'core_first_name'   => 'first_name',
			'core_last_name'    => 'last_name',
			'core_nickname'     => 'nickname',
			'core_display_name' => 'display_name',
			'core_language'     => 'language',
			'core_website'      => 'website',
			'core_bio'          => 'bio',
			'core_profile_picture' => 'profile_picture',
			'core_password'     => 'password',
			'core_sessions'     => 'sessions',
			'core_application_passwords' => 'application_passwords',
			'core_notification' => 'notification',
			'core_role'         => 'role',
			'core_submit_button' => 'submit_button',
		);
	}

	/**
	 * Remove all field definitions and settings, then return to the initial editor.
	 *
	 * @return void
	 */
	private function reset_fields() {
		check_admin_referer( 'atshift_upf_reset_fields' );

		delete_option( 'atshift_upf_fields' );
		delete_option( 'atshift_upf_settings' );

		wp_safe_redirect( $this->admin_url( array( 'atshift_upf_reset' => '1' ) ) );
		exit;
	}

	/**
	 * Save all field definitions from the atshift Fields-style editor.
	 *
	 * @return void
	 */
	private function save_fields() {
		check_admin_referer( 'atshift_upf_save_fields' );

		// Values are sanitized by field type below before they are stored.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_fields = isset( $_POST['fields'] ) ? (array) wp_unslash( $_POST['fields'] ) : array();
		$prepared   = array();
		$id_map     = array();
		$type_by_id = array();
		$seen_keys  = array();
		$used_ids   = array();
		$stored_by_id = array();

		foreach ( Atshift_UPF_Plugin::get_fields() as $stored_field ) {
			$stored_id = isset( $stored_field['id'] ) ? sanitize_key( $stored_field['id'] ) : '';
			if ( '' !== $stored_id ) {
				$stored_by_id[ $stored_id ] = $stored_field;
			}
		}

		foreach ( $raw_fields as $index => $raw_field ) {
			if ( ! is_array( $raw_field ) ) {
				continue;
			}

			$submitted_field_id = isset( $raw_field['field_id'] ) ? sanitize_key( $raw_field['field_id'] ) : '';
			$client_id          = isset( $raw_field['client_id'] ) ? sanitize_key( $raw_field['client_id'] ) : '';
			$field_id           = $submitted_field_id;
			$type               = isset( $raw_field['type'] ) ? sanitize_key( $raw_field['type'] ) : 'text';
			$stored_type        = isset( $stored_by_id[ $submitted_field_id ]['type'] ) ? sanitize_key( $stored_by_id[ $submitted_field_id ]['type'] ) : '';

			if ( ! isset( $this->field_types[ $type ] ) && $stored_type !== $type ) {
				$type = 'text';
			}

			if ( empty( $field_id ) || isset( $used_ids[ $field_id ] ) ) {
				$field_id = $this->generate_unique_field_id( $used_ids );
			}
			$used_ids[ $field_id ] = true;

			if ( '' !== $client_id ) {
				$id_map[ $client_id ] = $field_id;
			}

			if ( '' !== $submitted_field_id ) {
				$id_map[ $submitted_field_id ] = $field_id;
			}

			$prepared[ $index ] = array(
				'raw'      => $raw_field,
				'id'       => $field_id,
				'type'     => $type,
				'position' => count( $prepared ) + 1,
			);
			$type_by_id[ $field_id ] = $type;
		}

		$fields = array();

		foreach ( $prepared as $prepared_field ) {
			$raw_field = $prepared_field['raw'];
			$field_id  = $prepared_field['id'];
			$type      = $prepared_field['type'];
			$key       = isset( $raw_field['field_key'] ) ? sanitize_key( $raw_field['field_key'] ) : '';

			if ( $this->uses_generated_field_key( $type ) && ( 0 === strpos( $type, 'core_' ) || empty( $key ) || $this->is_legacy_generated_field_key( $type, $key ) ) ) {
				$key = $this->generate_field_key( $type, $field_id, $seen_keys );
			}

			if ( empty( $key ) ) {
				wp_safe_redirect( $this->admin_url( array( 'error' => 'missing_key' ) ) );
				exit;
			}

			if ( isset( $seen_keys[ $key ] ) ) {
				wp_safe_redirect( $this->admin_url( array( 'error' => 'duplicate_key' ) ) );
				exit;
			}
			$seen_keys[ $key ] = true;

			$choices = isset( $raw_field['choices'] ) ? sanitize_textarea_field( $raw_field['choices'] ) : '';
			$choices = array_values(
				array_filter(
					array_map( 'trim', preg_split( '/\r\n|\r|\n/', $choices ) )
				)
			);

			$parent_id         = isset( $raw_field['parent_id'] ) ? sanitize_key( $raw_field['parent_id'] ) : '';
			$parent_id         = isset( $id_map[ $parent_id ] ) ? $id_map[ $parent_id ] : $parent_id;
			$conditional_value = isset( $raw_field['conditional_value'] ) ? sanitize_text_field( $raw_field['conditional_value'] ) : '';
			$parent_type       = isset( $type_by_id[ $parent_id ] ) ? $type_by_id[ $parent_id ] : '';

			if ( ! $this->can_field_type_use_parent( $type, $parent_type ) ) {
				$parent_id         = '';
				$conditional_value = '';
			}

			if ( 'conditional' !== $parent_type ) {
				$conditional_value = '';
			}

			$label = isset( $raw_field['label'] ) ? sanitize_text_field( $raw_field['label'] ) : '';
			if ( '' === $label && isset( $this->field_types[ $type ] ) ) {
				$label = $this->field_types[ $type ];
			}
			$description = isset( $raw_field['description'] ) ? sanitize_textarea_field( $raw_field['description'] ) : '';
			$description = $this->normalize_default_field_description( $type, $description );
			if ( '' === $description ) {
				$description = $this->get_default_field_description( $type );
			}
			$role_control = $this->sanitize_role_control_settings( $raw_field, $type );

			$field = array(
				'id'          => $field_id,
				'key'         => $key,
				'label'       => $label,
				'type'        => $type,
				'description' => $description,
				'placeholder' => isset( $raw_field['placeholder'] ) ? sanitize_text_field( $raw_field['placeholder'] ) : '',
				'choices'     => $choices,
				'parent_id'   => $parent_id,
				'conditional_value' => $conditional_value,
				'group_columns' => isset( $raw_field['group_columns'] ) ? min( 3, max( 2, absint( $raw_field['group_columns'] ) ) ) : 2,
				'conditional_input' => isset( $raw_field['conditional_input'] ) && 'radio' === sanitize_key( $raw_field['conditional_input'] ) ? 'radio' : 'select',
				'accordion_open' => ! empty( $raw_field['accordion_open'] ),
				'role_control' => $role_control['mode'],
				'role_control_roles' => $role_control['roles'],
				'required'    => ! in_array( $type, array( 'core_username', 'core_email', 'core_password', 'core_language', 'core_notification', 'core_role' ), true ) && ! empty( $raw_field['required'] ),
				'validation_enabled' => in_array( $type, array( 'email', 'url', 'phone' ), true ) && ! empty( $raw_field['validation_enabled'] ),
				'initial_enabled' => Atshift_UPF_Plugin::supports_initial_state( $type ) && ! empty( $raw_field['initial_enabled'] ),
				'sort_order'  => $prepared_field['position'] * 10,
			);

			if ( isset( $stored_by_id[ $field_id ] ) ) {
				$extension_data = array_diff_key( $stored_by_id[ $field_id ], array_fill_keys( array_keys( $field ), true ) );
				$field          = array_merge( $extension_data, $field );
			}
			/**
			 * Filters one field definition before it is saved from the editor.
			 *
			 * @param array<string, mixed> $field Field definition sanitized by the base plugin.
			 * @param array<string, mixed> $raw_field Raw submitted field data.
			 * @param string               $type Field type.
			 */
			$fields[] = apply_filters( 'atshift_upf_admin_sanitize_field', $field, $raw_field, $type );
			}

			update_option( 'atshift_upf_fields', $fields, false );
			$this->save_extra_settings_from_fields_form( $fields );
			wp_safe_redirect( $this->admin_url( array( 'atshift_upf_updated' => '1' ) ) );
			exit;
		}

	/**
	 * Save a field.
	 *
	 * @return void
	 */
	private function save_field() {
		check_admin_referer( 'atshift_upf_save_field' );

		$fields   = Atshift_UPF_Plugin::get_fields();
		$field_id = isset( $_POST['field_id'] ) ? sanitize_key( wp_unslash( $_POST['field_id'] ) ) : '';
		$key      = isset( $_POST['field_key'] ) ? sanitize_key( wp_unslash( $_POST['field_key'] ) ) : '';
		$type     = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'text';
		$is_new   = empty( $field_id );

		if ( empty( $field_id ) ) {
			$field_id = $this->generate_unique_field_id( wp_list_pluck( $fields, 'id' ) );
		}

		if ( ! isset( $this->field_types[ $type ] ) ) {
			$type = 'text';
		}

		if ( $this->uses_generated_field_key( $type ) && ( 0 === strpos( $type, 'core_' ) || empty( $key ) || $this->is_legacy_generated_field_key( $type, $key ) ) ) {
			$key = $this->generate_field_key( $type, $field_id, wp_list_pluck( $fields, 'key' ) );
		}

		if ( empty( $key ) ) {
			wp_safe_redirect( $this->field_error_url( $is_new, $field_id, 'missing_key' ) );
			exit;
		}

		foreach ( $fields as $existing_field ) {
			if (
				isset( $existing_field['id'], $existing_field['key'] )
				&& $field_id !== $existing_field['id']
				&& $key === $existing_field['key']
			) {
				wp_safe_redirect( $this->field_error_url( $is_new, $field_id, 'duplicate_key' ) );
				exit;
			}
		}

		$choices = isset( $_POST['choices'] ) ? sanitize_textarea_field( wp_unslash( $_POST['choices'] ) ) : '';
		$choices = array_values(
			array_filter(
				array_map( 'trim', preg_split( '/\r\n|\r|\n/', $choices ) )
			)
		);
		$parent_id         = isset( $_POST['parent_id'] ) ? sanitize_key( wp_unslash( $_POST['parent_id'] ) ) : '';
		$conditional_value = isset( $_POST['conditional_value'] ) ? sanitize_text_field( wp_unslash( $_POST['conditional_value'] ) ) : '';
		$parent_type       = $this->get_field_type_by_id( $fields, $parent_id );

		if ( ! $this->can_field_type_use_parent( $type, $parent_type ) ) {
			$parent_id         = '';
			$conditional_value = '';
		}

		if ( 'conditional' !== $parent_type ) {
			$conditional_value = '';
		}

		$order = isset( $_POST['field_order'] ) ? sanitize_text_field( wp_unslash( $_POST['field_order'] ) ) : '';
		$ids   = array_values( array_filter( array_map( 'sanitize_key', explode( ',', $order ) ) ) );

		if ( ! empty( $ids ) && ! empty( $fields ) ) {
			$positions = array_flip( $ids );

			foreach ( $fields as $index => $field ) {
				if ( empty( $field['id'] ) || ! isset( $positions[ $field['id'] ] ) ) {
					continue;
				}

				$fields[ $index ]['sort_order'] = ( $positions[ $field['id'] ] + 1 ) * 10;
			}
		}

		$sort_order = $this->get_existing_sort_order( $fields, $field_id );

		if ( null === $sort_order ) {
			$sort_order = $this->get_next_sort_order( $fields );
		}

		$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
		if ( '' === $label && isset( $this->field_types[ $type ] ) ) {
			$label = $this->field_types[ $type ];
		}
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$description = $this->normalize_default_field_description( $type, $description );
		if ( '' === $description ) {
			$description = $this->get_default_field_description( $type );
		}
		$role_control = $this->sanitize_role_control_settings( wp_unslash( $_POST ), $type );

		$new_field = array(
			'id'          => $field_id,
			'key'         => $key,
			'label'       => $label,
			'type'        => $type,
			'description' => $description,
			'placeholder' => isset( $_POST['placeholder'] ) ? sanitize_text_field( wp_unslash( $_POST['placeholder'] ) ) : '',
			'choices'     => $choices,
			'parent_id'   => $parent_id,
			'conditional_value' => $conditional_value,
			'group_columns' => isset( $_POST['group_columns'] ) ? min( 3, max( 2, absint( $_POST['group_columns'] ) ) ) : 2,
			'conditional_input' => isset( $_POST['conditional_input'] ) && 'radio' === sanitize_key( wp_unslash( $_POST['conditional_input'] ) ) ? 'radio' : 'select',
			'accordion_open' => ! empty( $_POST['accordion_open'] ),
			'role_control' => $role_control['mode'],
			'role_control_roles' => $role_control['roles'],
			'required'    => ! in_array( $type, array( 'core_username', 'core_email', 'core_password', 'core_language', 'core_notification', 'core_role' ), true ) && ! empty( $_POST['required'] ),
			'validation_enabled' => in_array( $type, array( 'email', 'url', 'phone' ), true ) && ! empty( $_POST['validation_enabled'] ),
			'initial_enabled' => Atshift_UPF_Plugin::supports_initial_state( $type ) && ! empty( $_POST['initial_enabled'] ),
			'sort_order'  => $sort_order,
		);
		/**
		 * Filters one field definition before it is saved from the single-field route.
		 *
		 * @param array<string, mixed> $new_field Field definition sanitized by the base plugin.
		 * @param array<string, mixed> $raw_field Raw submitted field data.
		 * @param string               $type Field type.
		 */
		$new_field = apply_filters( 'atshift_upf_admin_sanitize_field', $new_field, wp_unslash( $_POST ), $type );

		$updated = false;
		foreach ( $fields as $index => $field ) {
			if ( isset( $field['id'] ) && $field_id === $field['id'] ) {
				$fields[ $index ] = $new_field;
				$updated          = true;
				break;
			}
		}

		if ( ! $updated ) {
			$fields[] = $new_field;
		}

		update_option( 'atshift_upf_fields', $fields, false );
		wp_safe_redirect( $this->admin_url( array( 'atshift_upf_updated' => '1' ) ) );
		exit;
	}

	/**
	 * Save drag-and-drop field ordering.
	 *
	 * @return void
	 */
	private function save_order() {
		check_admin_referer( 'atshift_upf_save_order' );

		$order  = isset( $_POST['field_order'] ) ? sanitize_text_field( wp_unslash( $_POST['field_order'] ) ) : '';
		$ids    = array_values( array_filter( array_map( 'sanitize_key', explode( ',', $order ) ) ) );
		$fields = Atshift_UPF_Plugin::get_fields();

		if ( empty( $ids ) || empty( $fields ) ) {
			wp_safe_redirect( $this->admin_url() );
			exit;
		}

		$positions = array_flip( $ids );

		foreach ( $fields as $index => $field ) {
			if ( empty( $field['id'] ) || ! isset( $positions[ $field['id'] ] ) ) {
				continue;
			}

			$fields[ $index ]['sort_order'] = ( $positions[ $field['id'] ] + 1 ) * 10;
		}

		update_option( 'atshift_upf_fields', $fields, false );
		wp_safe_redirect( $this->admin_url( array( 'atshift_upf_updated' => '1' ) ) );
		exit;
	}

	/**
	 * Delete a field definition.
	 *
	 * @return void
	 */
	private function delete_field() {
		check_admin_referer( 'atshift_upf_delete_field' );

		$field_id = isset( $_POST['field_id'] ) ? sanitize_key( wp_unslash( $_POST['field_id'] ) ) : '';
		$fields   = array_values(
			array_filter(
				Atshift_UPF_Plugin::get_fields(),
				static function ( $field ) use ( $field_id ) {
					return empty( $field['id'] ) || $field_id !== $field['id'];
				}
			)
		);

		update_option( 'atshift_upf_fields', $fields, false );
		wp_safe_redirect( $this->admin_url( array( 'deleted' => '1' ) ) );
		exit;
	}

	/**
	 * Save display options.
	 *
	 * @return void
	 */
	private function save_display_options() {
		check_admin_referer( 'atshift_upf_save_display_options' );

		$current       = Atshift_UPF_Plugin::get_settings();
		$context       = isset( $_POST['atshift_upf_options_context'] ) ? sanitize_key( wp_unslash( $_POST['atshift_upf_options_context'] ) ) : 'extras';
		$allowed       = array_keys( Atshift_UPF_Profile::get_core_field_options() );
		$hidden        = (array) $current['hidden_core_fields'];
		$disabled      = (array) $current['disabled_hidden_core_fields'];
		$apply_to_own  = ! empty( $current['apply_to_own_profile'] );
		$editor_layout = isset( $current['editor_layout'] ) && 'one' === $current['editor_layout'] ? 'one' : 'two';
		$show_extras   = ! empty( $current['show_extras'] );
		$field_group_enabled = ! empty( $current['field_group_enabled'] );

		if ( isset( $_POST['editor_layout'] ) ) {
			$editor_layout = 'one' === sanitize_key( wp_unslash( $_POST['editor_layout'] ) ) ? 'one' : 'two';
		}

		if ( 'screen' === $context ) {
			$show_extras = isset( $_POST['show_extras'] ) ? ! empty( $_POST['show_extras'] ) : $show_extras;
		} else {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized with sanitize_key immediately below.
			$hidden       = isset( $_POST['hidden_core_fields'] ) ? (array) wp_unslash( $_POST['hidden_core_fields'] ) : array();
			$hidden       = array_values( array_intersect( array_map( 'sanitize_key', $hidden ), $allowed ) );
			$disabled     = $this->sanitize_disabled_hidden_core_fields(
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by sanitize_disabled_hidden_core_fields().
				isset( $_POST['disabled_hidden_core_fields'] ) ? (array) wp_unslash( $_POST['disabled_hidden_core_fields'] ) : array(),
				$hidden
			);
			$apply_to_own = ! empty( $_POST['apply_to_own_profile'] );
			$show_extras  = ! empty( $_POST['show_extras'] );
		}

		update_option(
			'atshift_upf_settings',
			array(
				'hidden_core_fields'          => $hidden,
				'disabled_hidden_core_fields' => $disabled,
				'apply_to_own_profile'        => $apply_to_own,
				'editor_layout'               => $editor_layout,
				'show_extras'                 => $show_extras,
				'field_group_enabled'         => $field_group_enabled,
			),
			false
		);

		wp_safe_redirect( $this->admin_url( array( 'atshift_upf_updated' => '1' ), 'extras' === $context ? self::EXTRAS_PAGE_SLUG : self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Save Extras settings when the main Fields form is saved.
	 *
	 * @return void
	 */
	private function save_extra_settings_from_fields_form( $fields = null ) {
		$current       = Atshift_UPF_Plugin::get_settings();
		$allowed       = array_keys( Atshift_UPF_Profile::get_core_field_options() );
		$hidden        = (array) $current['hidden_core_fields'];
		$disabled      = (array) $current['disabled_hidden_core_fields'];
		$apply_to_own  = ! empty( $current['apply_to_own_profile'] );
		$editor_layout = isset( $current['editor_layout'] ) && 'one' === $current['editor_layout'] ? 'one' : 'two';
		$show_extras   = ! empty( $current['show_extras'] );
		// The caller verifies the field-editor nonce before reaching this helper.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$field_group_enabled = isset( $_POST['field_group_enabled'] ) ? '1' === sanitize_key( wp_unslash( $_POST['field_group_enabled'] ) ) : ! empty( $current['field_group_enabled'] );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['hidden_core_fields'] ) || isset( $_POST['disabled_hidden_core_fields'] ) || isset( $_POST['apply_to_own_profile'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized with sanitize_key immediately below.
			$hidden       = isset( $_POST['hidden_core_fields'] ) ? (array) wp_unslash( $_POST['hidden_core_fields'] ) : array();
			$hidden       = array_values( array_intersect( array_map( 'sanitize_key', $hidden ), $allowed ) );
			$disabled     = $this->sanitize_disabled_hidden_core_fields(
				// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by sanitize_disabled_hidden_core_fields().
				isset( $_POST['disabled_hidden_core_fields'] ) ? (array) wp_unslash( $_POST['disabled_hidden_core_fields'] ) : array(),
				$hidden
			);
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$apply_to_own = ! empty( $_POST['apply_to_own_profile'] );
		}

		if ( is_array( $fields ) ) {
			$hidden = $this->add_active_managed_core_fields_to_hidden( $hidden, $fields, $field_group_enabled );
			$hidden = $this->remove_inactive_managed_core_fields_from_hidden( $hidden, $fields, $field_group_enabled );
			$disabled = $this->remove_managed_core_fields_from_disabled( $disabled, $fields, $field_group_enabled );
		}

		update_option(
			'atshift_upf_settings',
			array(
				'hidden_core_fields'          => $hidden,
				'disabled_hidden_core_fields' => $disabled,
				'apply_to_own_profile'        => $apply_to_own,
				'editor_layout'               => $editor_layout,
				'show_extras'                 => $show_extras,
				'field_group_enabled'         => $field_group_enabled,
			),
			false
		);
	}

	/**
	 * Add hidden core flags for active standard fields managed in the field set.
	 *
	 * @param array<int, string>              $hidden Hidden core option keys.
	 * @param array<int, array<string,mixed>> $fields Saved field definitions.
	 * @param bool                            $field_group_enabled Whether the profile field group is enabled.
	 * @return array<int, string>
	 */
	private function add_active_managed_core_fields_to_hidden( $hidden, $fields, $field_group_enabled ) {
		if ( ! $field_group_enabled ) {
			return $hidden;
		}

		$map = $this->get_core_extra_field_type_map();

		foreach ( $fields as $field ) {
			$type = isset( $field['type'] ) ? (string) $field['type'] : '';
			if ( isset( $map[ $type ] ) ) {
				$hidden[] = $map[ $type ];
			}
		}

		return array_values( array_unique( array_map( 'sanitize_key', $hidden ) ) );
	}

	/**
	 * Sanitize feature options that should be turned off while hidden.
	 *
	 * @param array<int, string> $raw Raw disabled option keys.
	 * @param array<int, string> $hidden Hidden core option keys.
	 * @return array<int, string>
	 */
	private function sanitize_disabled_hidden_core_fields( $raw, $hidden ) {
		$allowed = array();

		foreach ( Atshift_UPF_Profile::get_core_field_options() as $key => $option ) {
			if ( ! empty( $option['off_label'] ) ) {
				$allowed[] = $key;
			}
		}

		return array_values(
			array_intersect(
				array_map( 'sanitize_key', (array) $raw ),
				$allowed,
				array_map( 'sanitize_key', (array) $hidden )
			)
		);
	}

	/**
	 * Remove hidden core flags for managed core fields that are no longer active.
	 *
	 * @param array<int, string>              $hidden Hidden core option keys.
	 * @param array<int, array<string,mixed>> $fields Saved field definitions.
	 * @param bool                           $field_group_enabled Whether the profile field group is enabled.
	 * @return array<int, string>
	 */
	private function remove_inactive_managed_core_fields_from_hidden( $hidden, $fields, $field_group_enabled ) {
		$map         = $this->get_core_extra_field_type_map();
		$represented = array();
		$active      = array();

		foreach ( $fields as $field ) {
			$type = isset( $field['type'] ) ? (string) $field['type'] : '';
			if ( empty( $map[ $type ] ) ) {
				continue;
			}

			$key                 = $map[ $type ];
			$represented[ $key ] = true;

			if ( $field_group_enabled ) {
				$active[ $key ] = true;
			}
		}

		return array_values(
			array_filter(
				$hidden,
				static function ( $key ) use ( $represented, $active ) {
					return empty( $represented[ $key ] ) || ! empty( $active[ $key ] );
				}
			)
		);
	}

	/**
	 * Remove feature-off flags for active standard fields managed by this plugin.
	 *
	 * @param array<int, string>              $disabled Disabled hidden option keys.
	 * @param array<int, array<string,mixed>> $fields Saved field definitions.
	 * @param bool                            $field_group_enabled Whether the profile field group is enabled.
	 * @return array<int, string>
	 */
	private function remove_managed_core_fields_from_disabled( $disabled, $fields, $field_group_enabled ) {
		if ( ! $field_group_enabled ) {
			return $disabled;
		}

		$map     = $this->get_core_extra_field_type_map();
		$managed = array();

		foreach ( $fields as $field ) {
			$type = isset( $field['type'] ) ? (string) $field['type'] : '';
			if ( isset( $map[ $type ] ) ) {
				$managed[] = $map[ $type ];
			}
		}

		return array_values( array_diff( $disabled, array_unique( $managed ) ) );
	}

	/**
	 * Save screen display options without requiring an Apply button.
	 *
	 * @return void
	 */
	public function ajax_save_screen_options() {
		if ( ! current_user_can( $this->get_capability() ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to save these settings.', 'atshift-user-profile-fields' ) ), 403 );
		}

		check_ajax_referer( 'atshift_upf_screen_options', 'nonce' );

		$current       = Atshift_UPF_Plugin::get_settings();
		$editor_layout = isset( $_POST['editor_layout'] ) && 'one' === sanitize_key( wp_unslash( $_POST['editor_layout'] ) ) ? 'one' : 'two';
		$show_extras   = isset( $_POST['show_extras'] ) ? ! empty( $_POST['show_extras'] ) : ! empty( $current['show_extras'] );

		$settings = array(
			'hidden_core_fields'          => (array) $current['hidden_core_fields'],
			'disabled_hidden_core_fields' => (array) $current['disabled_hidden_core_fields'],
			'apply_to_own_profile'        => ! empty( $current['apply_to_own_profile'] ),
			'editor_layout'               => $editor_layout,
			'show_extras'                 => $show_extras,
			'field_group_enabled'         => ! empty( $current['field_group_enabled'] ),
		);

		update_option( 'atshift_upf_settings', $settings, false );

		wp_send_json_success( $settings );
	}

	/**
	 * Find a field currently being edited.
	 *
	 * @param array<int, array<string, mixed>> $fields Existing fields.
	 * @return array<string, mixed>|null
	 */
	private function get_edit_field( $fields ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only editor selection.
		if ( empty( $_GET['edit'] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only editor selection.
		$edit = sanitize_key( wp_unslash( $_GET['edit'] ) );

		foreach ( $fields as $field ) {
			if ( isset( $field['id'] ) && $edit === $field['id'] ) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * Get the existing sort order for a field.
	 *
	 * @param array<int, array<string, mixed>> $fields Existing fields.
	 * @param string                          $field_id Field ID.
	 * @return int|null
	 */
	private function get_existing_sort_order( $fields, $field_id ) {
		foreach ( $fields as $field ) {
			if ( isset( $field['id'] ) && $field_id === $field['id'] ) {
				return isset( $field['sort_order'] ) ? (int) $field['sort_order'] : 10;
			}
		}

		return null;
	}

	/**
	 * Generate a field ID that does not collide with current field IDs.
	 *
	 * @param array<int|string, mixed> $reserved_ids Reserved field IDs.
	 * @return string
	 */
	private function generate_unique_field_id( $reserved_ids = array() ) {
		$reserved = array();

		foreach ( $reserved_ids as $key => $value ) {
			if ( is_int( $key ) ) {
				$reserved[ (string) $value ] = true;
			} else {
				$reserved[ (string) $key ] = true;
			}
		}

		do {
			$field_id = sanitize_key( 'field_' . str_replace( '-', '_', wp_generate_uuid4() ) );
		} while ( isset( $reserved[ $field_id ] ) );

		return $field_id;
	}

	/**
	 * Get the next sort order for a new field.
	 *
	 * @param array<int, array<string, mixed>> $fields Existing fields.
	 * @return int
	 */
	private function get_next_sort_order( $fields ) {
		$max = 0;

		foreach ( $fields as $field ) {
			$max = max( $max, isset( $field['sort_order'] ) ? (int) $field['sort_order'] : 0 );
		}

		return $max + 10;
	}

	/**
	 * Build the URL used after a field form validation error.
	 *
	 * @param bool   $is_new Whether the field is new.
	 * @param string $field_id Field ID.
	 * @param string $error Error code.
	 * @return string
	 */
	private function field_error_url( $is_new, $field_id, $error ) {
		$args = array(
			'error' => $error,
		);

		if ( $is_new ) {
			$args['add'] = '1';
		} else {
			$args['edit'] = $field_id;
		}

		return $this->admin_url( $args );
	}

	/**
	 * Build the plugin admin URL.
	 *
	 * @param array<string, string> $args Query args.
	 * @param string                $page Page slug.
	 * @return string
	 */
	private function admin_url( $args = array(), $page = self::PAGE_SLUG ) {
		return add_query_arg(
			array_merge(
				array(
					'page' => $page,
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}
}
