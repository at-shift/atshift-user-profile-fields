<?php
/**
 * User profile integration.
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders custom fields and hides selected core rows.
 */
class Atshift_UPF_Profile {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'render_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'render_fields' ) );
		add_action( 'user_new_form', array( $this, 'render_new_user_fields' ) );
		add_action( 'user_profile_update_errors', array( $this, 'validate_fields' ), 10, 3 );
		add_action( 'personal_options_update', array( $this, 'save_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_fields' ) );
		add_action( 'user_register', array( $this, 'save_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_profile_assets' ) );
		add_filter( 'ure_show_additional_capabilities_section', array( $this, 'filter_user_role_editor_profile_section' ) );
		add_filter( 'additional_capabilities_display', array( $this, 'filter_additional_capabilities_display' ), 99 );
	}

	/**
	 * Core profile fields that can be hidden.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_core_field_options() {
		return array(
			'visual_editor'          => array(
				'label'       => __( 'Visual editor', 'atshift-user-profile-fields' ),
				'description' => __( 'Rich text editor preference.', 'atshift-user-profile-fields' ),
				'off_label'   => __( 'Disable the visual editor', 'atshift-user-profile-fields' ),
			),
			'syntax_highlighting'    => array(
				'label'       => __( 'Syntax highlighting', 'atshift-user-profile-fields' ),
				'description' => __( 'Code editor syntax highlighting preference.', 'atshift-user-profile-fields' ),
				'off_label'   => __( 'Disable syntax highlighting', 'atshift-user-profile-fields' ),
			),
			'admin_color'           => array(
				'label'       => __( 'Admin color scheme', 'atshift-user-profile-fields' ),
				'description' => __( 'Color palette selector.', 'atshift-user-profile-fields' ),
			),
			'keyboard_shortcuts'    => array(
				'label'       => __( 'Keyboard shortcuts', 'atshift-user-profile-fields' ),
				'description' => __( 'Comment moderation shortcut preference.', 'atshift-user-profile-fields' ),
				'off_label'   => __( 'Disable keyboard shortcuts', 'atshift-user-profile-fields' ),
			),
			'toolbar'               => array(
				'label'       => __( 'Toolbar', 'atshift-user-profile-fields' ),
				'description' => __( 'Frontend toolbar preference.', 'atshift-user-profile-fields' ),
				'off_label'   => __( 'Do not show the Toolbar while viewing the site', 'atshift-user-profile-fields' ),
			),
			'language'              => array(
				'label'       => __( 'Language', 'atshift-user-profile-fields' ),
				'description' => __( 'Admin language selector.', 'atshift-user-profile-fields' ),
			),
			'username'              => array(
				'label'       => __( 'Username', 'atshift-user-profile-fields' ),
				'description' => __( 'Required login name on the add user screen.', 'atshift-user-profile-fields' ),
			),
			'email'                 => array(
				'label'       => __( 'Email', 'atshift-user-profile-fields' ),
				'description' => __( 'Default WordPress account email row.', 'atshift-user-profile-fields' ),
			),
			'first_name'            => array(
				'label'       => __( 'First name', 'atshift-user-profile-fields' ),
				'description' => __( 'Default first name row.', 'atshift-user-profile-fields' ),
			),
			'last_name'             => array(
				'label'       => __( 'Last name', 'atshift-user-profile-fields' ),
				'description' => __( 'Default last name row.', 'atshift-user-profile-fields' ),
			),
			'nickname'              => array(
				'label'       => __( 'Nickname', 'atshift-user-profile-fields' ),
				'description' => __( 'Default nickname row.', 'atshift-user-profile-fields' ),
			),
			'display_name'          => array(
				'label'       => __( 'Display name', 'atshift-user-profile-fields' ),
				'description' => __( 'Public display name selector.', 'atshift-user-profile-fields' ),
			),
			'website'               => array(
				'label'       => __( 'Website', 'atshift-user-profile-fields' ),
				'description' => __( 'Default website URL row.', 'atshift-user-profile-fields' ),
			),
			'bio'                   => array(
				'label'       => __( 'Biographical info', 'atshift-user-profile-fields' ),
				'description' => __( 'Default description textarea.', 'atshift-user-profile-fields' ),
			),
			'password'              => array(
				'label'       => __( 'Password', 'atshift-user-profile-fields' ),
				'description' => __( 'Password controls on user screens.', 'atshift-user-profile-fields' ),
			),
			'sessions'              => array(
				'label'       => __( 'Sessions', 'atshift-user-profile-fields' ),
				'description' => __( 'Log out everywhere else controls.', 'atshift-user-profile-fields' ),
			),
			'notification'          => array(
				'label'       => __( 'Email Notification', 'atshift-user-profile-fields' ),
				'description' => __( 'New user notification checkbox.', 'atshift-user-profile-fields' ),
				'off_label'   => __( 'Do not send an account email to the new user', 'atshift-user-profile-fields' ),
			),
			'role'                  => array(
				'label'       => __( 'Role', 'atshift-user-profile-fields' ),
				'description' => __( 'WordPress user role selector.', 'atshift-user-profile-fields' ),
			),
			'profile_picture'       => array(
				'label'       => __( 'Profile picture', 'atshift-user-profile-fields' ),
				'description' => __( 'WordPress Gravatar profile picture row.', 'atshift-user-profile-fields' ),
			),
			'application_passwords' => array(
				'label'       => __( 'Application passwords', 'atshift-user-profile-fields' ),
				'description' => __( 'Application password management section.', 'atshift-user-profile-fields' ),
			),
			'submit_button'         => array(
				'label'       => __( 'Add / Save User button', 'atshift-user-profile-fields' ),
				'description' => __( 'Default user creation and profile save button.', 'atshift-user-profile-fields' ),
			),
			'ure_additional_capabilities' => array(
				'label'       => __( 'User Role Editor: Additional Capabilities', 'atshift-user-profile-fields' ),
				'description' => __( 'Hides the Other Roles and Capabilities section added by User Role Editor.', 'atshift-user-profile-fields' ),
			),
		);
	}

	/**
	 * Hide the profile section added by User Role Editor when selected in Extras.
	 *
	 * @param bool $show Whether User Role Editor should render its section.
	 * @return bool
	 */
	public function filter_user_role_editor_profile_section( $show ) {
		if ( $this->is_extra_profile_item_hidden( 'ure_additional_capabilities' ) ) {
			return false;
		}

		return $show;
	}

	/**
	 * Keep WordPress from restoring its raw capabilities section in place of URE.
	 *
	 * @param bool $display Whether WordPress should display additional capabilities.
	 * @return bool
	 */
	public function filter_additional_capabilities_display( $display ) {
		if ( $this->is_extra_profile_item_hidden( 'ure_additional_capabilities' ) ) {
			return false;
		}

		return $display;
	}

	/**
	 * Check whether an Extras profile item is enabled for hiding.
	 *
	 * @param string $key Extras option key.
	 * @return bool
	 */
	private function is_extra_profile_item_hidden( $key ) {
		$settings = Atshift_UPF_Plugin::get_settings();

		if ( empty( $settings['field_group_enabled'] ) ) {
			return false;
		}

		return in_array( sanitize_key( $key ), (array) $settings['hidden_core_fields'], true );
	}

	/**
	 * Enqueue CSS/JS on WordPress profile screens.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_profile_assets( $hook ) {
		if ( ! in_array( $hook, array( 'profile.php', 'user-edit.php', 'user-new.php' ), true ) ) {
			return;
		}

		$settings = Atshift_UPF_Plugin::get_settings();
		$screen   = 'user-new.php' === $hook ? 'new' : 'edit';
		$profile_user_id = $this->get_profile_screen_user_id( $hook );

		if ( empty( $settings['field_group_enabled'] ) ) {
			return;
		}

		if ( 'profile.php' === $hook && empty( $settings['apply_to_own_profile'] ) ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'atshift-upf-profile',
			ATSHIFT_UPF_URL . 'assets/profile.css',
			array(),
			ATSHIFT_UPF_VERSION
		);
		wp_add_inline_style(
			'atshift-upf-profile',
			Atshift_UPF_Plugin::get_admin_color_scheme_css( 'body.wp-admin' )
		);

		wp_enqueue_script(
			'atshift-upf-profile',
			ATSHIFT_UPF_URL . 'assets/profile.js',
			array(),
			ATSHIFT_UPF_VERSION,
			true
		);

		wp_localize_script(
			'atshift-upf-profile',
			'atshiftUPFProfile',
			array(
				'hiddenFields'         => $this->get_hidden_core_fields( $settings, $screen ),
				'disabledHiddenFields' => $this->get_disabled_hidden_core_fields( $settings, $screen ),
				'replacementFields'    => $this->get_managed_core_replacement_keys( $screen ),
				'roleRestrictedFields' => $this->get_role_restricted_core_replacement_keys( $screen ),
				'adminColorSchemes'    => $this->get_admin_color_schemes_for_script(),
				'currentUserId'        => get_current_user_id(),
				'profileUserId'        => $profile_user_id,
				'languagePreview'      => $this->get_language_preview_translations(),
				'strings'              => array(
					'selectImage'  => __( 'Select Image', 'atshift-user-profile-fields' ),
					'useThisImage' => __( 'Use this image', 'atshift-user-profile-fields' ),
					'generatePassword' => __( 'Generate Password', 'atshift-user-profile-fields' ),
					'showPassword' => __( 'Show', 'atshift-user-profile-fields' ),
					'hidePassword' => __( 'Hide', 'atshift-user-profile-fields' ),
					'passwordStrengthStrong' => __( 'Strong', 'atshift-user-profile-fields' ),
					'passwordStrengthWeak' => __( 'Weak', 'atshift-user-profile-fields' ),
					'required' => __( 'Required', 'atshift-user-profile-fields' ),
					'usernameInvalid' => __( 'Use only letters, numbers, and these symbols: _ . - @', 'atshift-user-profile-fields' ),
					'passwordWeak' => __( 'Use at least 8 characters and combine letters, numbers, or symbols.', 'atshift-user-profile-fields' ),
				),
			)
		);
	}

	/**
	 * Return registered admin color schemes in a browser-friendly format.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_admin_color_schemes_for_script() {
		global $_wp_admin_css_colors;

		$schemes = array();
		foreach ( (array) $_wp_admin_css_colors as $key => $scheme ) {
			if ( ! is_object( $scheme ) ) {
				continue;
			}

			$schemes[ sanitize_key( $key ) ] = array(
				'url'        => isset( $scheme->url ) ? esc_url_raw( $scheme->url ) : '',
				'colors'     => isset( $scheme->colors ) && is_array( $scheme->colors ) ? array_values( $scheme->colors ) : array(),
				'iconColors' => isset( $scheme->icon_colors ) && is_array( $scheme->icon_colors ) ? $scheme->icon_colors : array(),
			);
		}

		return $schemes;
	}

	/**
	 * Return lightweight translations for live language previews on profile screens.
	 *
	 * @return array<string, mixed>
	 */
	private function get_language_preview_translations() {
		$translations = array(
			'en' => array(
				'required'     => 'Required',
				'label'        => array(),
				'description'  => array(),
			),
			'ja' => array(
				'required'     => '必須',
				'label'        => array(),
				'description'  => array(),
			),
		);

		foreach ( $this->get_language_preview_label_pairs() as $english => $japanese ) {
			$translations['en']['label'][ $english ]   = $english;
			$translations['en']['label'][ $japanese ]  = $english;
			$translations['ja']['label'][ $english ]   = $japanese;
			$translations['ja']['label'][ $japanese ]  = $japanese;
		}

		foreach ( $this->get_language_preview_description_pairs() as $english => $japanese ) {
			$translations['en']['description'][ $english ]  = $english;
			$translations['en']['description'][ $japanese ] = $english;
			$translations['ja']['description'][ $english ]  = $japanese;
			$translations['ja']['description'][ $japanese ] = $japanese;
		}

		return array(
			'currentLanguage' => substr( determine_locale(), 0, 2 ),
			'siteLanguage'    => substr( get_locale(), 0, 2 ),
			'translations'    => $translations,
		);
	}

	/**
	 * Return English/Japanese pairs for bundled default labels.
	 *
	 * @return array<string, string>
	 */
	private function get_language_preview_label_pairs() {
		return array(
			'Personal Options'              => '個人設定',
			'Visual Editor'                 => 'ビジュアルエディター',
			'Visual editor'                 => 'ビジュアルエディター',
			'Syntax Highlighting'           => 'シンタックスハイライト',
			'Syntax highlighting'           => 'シンタックスハイライト',
			'Admin Color Scheme'            => '管理画面の配色',
			'Admin color scheme'            => '管理画面の配色',
			'Keyboard Shortcuts'            => 'キーボードショートカット',
			'Keyboard shortcuts'            => 'キーボードショートカット',
			'Toolbar'                       => 'ツールバー',
			'Language'                      => '言語',
			'Username'                      => 'ユーザー名',
			'Name'                          => '名前',
			'First Name'                    => '名',
			'First name'                    => '名',
			'Last Name'                     => '姓',
			'Last name'                     => '姓',
			'Nickname'                      => 'ニックネーム',
			'Display name'                  => '表示名',
			'Name Display Format'           => 'サイトに表示する名前',
			'Name Shown on the Site'        => 'サイトに表示する名前',
			'Contact Info'                  => '連絡先情報',
			'Email'                         => 'メールアドレス',
			'Website'                       => 'サイト',
			'About Yourself'                => 'プロフィール',
			'Biographical Info'             => 'プロフィール情報',
			'Biographical info'             => 'プロフィール情報',
			'Profile Picture'               => 'プロフィール写真',
			'Profile picture'               => 'プロフィール写真',
			'Security'                      => 'セキュリティ',
			'Password'                      => 'パスワード',
			'Sessions'                      => 'セッション',
			'Application Passwords'         => 'アプリケーションパスワード',
			'Application passwords'         => 'アプリケーションパスワード',
			'Permissions and Notifications' => '権限・通知',
			'Role'                          => 'ユーザー権限グループ',
			'User Notification'             => 'メール通知',
			'Add User / Save'               => 'ユーザー追加・保存',
		);
	}

	/**
	 * Return English/Japanese pairs for bundled default notes.
	 *
	 * @return array<string, string>
	 */
	private function get_language_preview_description_pairs() {
		return array(
			'Enter the username required for login using letters, numbers, and the supported symbols (_ . - @). Spaces are not allowed. The username cannot be changed later.' => 'ログインに必要なユーザー名を、半角英数字と使用可能な記号 (_ . - @) で入力してください。スペースは使用できません。ユーザー名は後で変更できません。',
			'Enter the username required for login using half-width letters, numbers, and allowed symbols (_ . - @). Spaces cannot be used. This cannot be changed later.' => 'ログインに必要なユーザー名を、半角英数字と使用可能な記号 (_ . - @) で入力してください。スペースは使用できません。ユーザー名は後で変更できません。',
			'Used for password resets and account notifications. It can be changed later and can also be used instead of the username when logging in.' => 'パスワードリセットとアカウント通知に使用します。メールアドレスはいつでも変更でき、ログイン時にユーザー名の代わりにも使用できます。',
			'This email address is used for password resets and other account notifications. It can be changed at any time and can also be used instead of the user ID when logging in.' => 'パスワードリセットとアカウント通知に使用します。メールアドレスはいつでも変更でき、ログイン時にユーザー名の代わりにも使用できます。',
			'Use a hard-to-guess password of at least 8 characters that combines letters, numbers, and symbols.' => '推測されにくい、英字・数字・記号を組み合わせた8文字以上のパスワードを使用してください。',
			'Use a password that is difficult to guess, combines letters, numbers, and symbols, and is at least 8 characters long.' => '推測されにくい、英字・数字・記号を組み合わせた8文字以上のパスワードを使用してください。',
			'Used to add users and save profiles.' => 'ユーザーの追加とプロフィールの保存に使用します。',
		);
	}

	/**
	 * Return the user represented by the current profile screen.
	 *
	 * @param string $hook Current admin hook.
	 * @return int
	 */
	private function get_profile_screen_user_id( $hook ) {
		if ( 'profile.php' === $hook ) {
			return get_current_user_id();
		}

		if ( 'user-edit.php' === $hook && isset( $_GET['user_id'] ) ) {
			return absint( $_GET['user_id'] );
		}

		return 0;
	}

	/**
	 * Render custom fields in the user profile screen.
	 *
	 * @param WP_User $user User being edited.
	 * @return void
	 */
	public function render_fields( $user ) {
		if ( ! $this->is_field_group_enabled() ) {
			return;
		}

		$fields = $this->filter_fields_for_screen( Atshift_UPF_Plugin::get_enabled_fields(), 'edit' );

		if ( empty( $fields ) ) {
			return;
		}
		$tree   = $this->build_field_tree( $fields );
		$values = $this->get_user_field_values( $user, $fields );
		$heading = $this->get_profile_section_heading( $fields );
		?>
		<div class="atshift-upf-profile-card <?php echo $heading ? 'has-heading' : 'is-headingless'; ?>">
			<?php if ( $heading ) : ?>
				<h2 class="atshift-upf-profile-title"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<table class="form-table atshift-upf-profile-fields" role="presentation">
				<colgroup>
					<col class="atshift-upf-profile-label-column">
					<col>
				</colgroup>
				<tbody>
					<?php $this->render_field_nodes_grouped( $tree['root'], $tree, $user, $values ); ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render managed fields on the Add New User screen.
	 *
	 * @return void
	 */
	public function render_new_user_fields() {
		if ( ! $this->is_field_group_enabled() ) {
			return;
		}

		$fields = $this->filter_fields_for_screen( Atshift_UPF_Plugin::get_enabled_fields(), 'new' );

		if ( empty( $fields ) ) {
			return;
		}

		$tree   = $this->build_field_tree( $fields );
		$values = array();
		$heading = $this->get_profile_section_heading( $fields );
		?>
		<div class="atshift-upf-profile-card <?php echo $heading ? 'has-heading' : 'is-headingless'; ?>">
			<?php if ( $heading ) : ?>
				<h2 class="atshift-upf-profile-title"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<table class="form-table atshift-upf-profile-fields" role="presentation">
				<colgroup>
					<col class="atshift-upf-profile-label-column">
					<col>
				</colgroup>
				<tbody>
					<?php $this->render_field_nodes_grouped( $tree['root'], $tree, null, $values ); ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Validate required custom fields.
	 *
	 * @param WP_Error $errors Error object.
	 * @param bool     $update Whether this is an update.
	 * @param WP_User|stdClass $user User being saved.
	 * @return void
	 */
	public function validate_fields( $errors, $update, $user ) {
		if ( ! $this->is_field_group_enabled() ) {
			return;
		}

		$this->apply_disabled_hidden_core_fields( $update ? 'edit' : 'new', $user );

		$values = isset( $_POST['atshift_upf_fields'] ) ? (array) wp_unslash( $_POST['atshift_upf_fields'] ) : array();

		$fields = $this->filter_fields_for_screen( Atshift_UPF_Plugin::get_enabled_fields(), $update ? 'edit' : 'new' );
		$screen = $update ? 'edit' : 'new';

		foreach ( $fields as $field ) {
			if ( $this->is_horizontal_group( $field ) || $this->is_box_group( $field ) || $this->is_accordion_group( $field ) ) {
				continue;
			}

			if ( ! $this->field_matches_profile_context( $field, 'admin_profile_validate', $screen, $user ) ) {
				continue;
			}

			if ( empty( $field['key'] ) ) {
				continue;
			}

				$value = $this->get_submitted_value( $field, $values );
				$trimmed_value = trim( (string) $value );

				if ( 'core_nickname' === ( $field['type'] ?? '' ) && $update && '' === $trimmed_value ) {
					$errors->remove( 'nickname' );
					$this->add_field_error( $errors, $field, __( 'This field cannot be left blank.', 'atshift-user-profile-fields' ), 'nickname_empty' );
					continue;
				}

				if ( 'core_password' === ( $field['type'] ?? '' ) ) {
				if ( ! $update && '' === $trimmed_value ) {
					$this->add_field_error( $errors, $field, __( 'This field is required.', 'atshift-user-profile-fields' ), 'required' );
					continue;
				}

				if ( '' === $trimmed_value ) {
					continue;
				}

				if ( preg_match( '/\s/', (string) $value ) ) {
					$this->add_field_error( $errors, $field, __( 'Spaces cannot be used.', 'atshift-user-profile-fields' ), 'password_space' );
					continue;
				}

				if ( ! $this->is_valid_password( (string) $value ) ) {
					$this->add_field_error( $errors, $field, __( 'Use at least 8 characters and combine letters, numbers, or symbols.', 'atshift-user-profile-fields' ), 'password_strength' );
				}
				continue;
			}

			if ( $this->is_required_field( $field ) && 'checkbox' === $field['type'] ) {
				if ( empty( $value ) ) {
					$this->add_field_error( $errors, $field, __( 'This field is required.', 'atshift-user-profile-fields' ), 'required' );
				}
				continue;
			}

			if ( $this->is_required_field( $field ) && '' === $trimmed_value ) {
				$this->add_field_error( $errors, $field, __( 'This field is required.', 'atshift-user-profile-fields' ), 'required' );
				continue;
			}

			if ( '' !== $trimmed_value && $this->should_validate_format( $field ) && ! $this->is_valid_format( $value, $field ) ) {
				$this->add_field_error( $errors, $field, $this->get_format_error_message( $field ), 'format' );
			}

			/**
			 * Fires after the base plugin validates one submitted profile field.
			 *
			 * Add-ons can add errors to the provided WP_Error object.
			 *
			 * @param WP_Error             $errors Error object.
			 * @param array<string, mixed> $field Field definition.
			 * @param mixed                $value Submitted value.
			 * @param bool                 $update Whether this is an existing user update.
			 * @param WP_User              $user User being saved.
			 */
			do_action( 'atshift_upf_validate_profile_field', $errors, $field, $value, $update, $user );
		}
	}

	/**
	 * Add a profile validation error with the field location in the message.
	 *
	 * @param WP_Error             $errors Error object.
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $message Error message.
	 * @param string               $code Error code suffix.
	 * @return void
	 */
	private function add_field_error( $errors, $field, $message, $code ) {
		$key   = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : 'field';
		$label = isset( $field['label'] ) && '' !== (string) $field['label'] ? (string) $field['label'] : $key;

		$errors->add(
			'atshift_upf_' . sanitize_key( $code ) . '_' . $key,
			/* translators: 1: Field label, 2: Validation error message. */
			sprintf( __( '%1$s: %2$s', 'atshift-user-profile-fields' ), $label, $message )
		);
	}

	/**
	 * Save custom profile field values.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function save_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( ! $this->is_field_group_enabled() ) {
			return;
		}

		$values = isset( $_POST['atshift_upf_fields'] ) ? (array) wp_unslash( $_POST['atshift_upf_fields'] ) : array();
		$core_updates = array(
			'ID' => $user_id,
		);

		$screen     = 'user_register' === current_filter() ? 'new' : 'edit';
		$all_fields = Atshift_UPF_Plugin::get_enabled_fields();

		if ( 'new' === $screen ) {
			$this->apply_initial_states_to_new_user( $user_id, $all_fields );
		}

		$fields = $this->filter_fields_for_screen( $all_fields, $screen );

		foreach ( $fields as $field ) {
			if ( $this->is_horizontal_group( $field ) || $this->is_box_group( $field ) || $this->is_accordion_group( $field ) ) {
				continue;
			}

			if ( ! $this->field_matches_profile_context( $field, 'admin_profile_save', $screen, $user_id ) ) {
				continue;
			}

			if ( empty( $field['key'] ) ) {
				continue;
			}

			$value = $this->get_submitted_value( $field, $values );
			$value = $this->sanitize_value( $value, $field );

			if ( $this->is_core_field( $field ) ) {
				$this->queue_core_field_update( $core_updates, $field, $value );
				/**
				 * Fires after the base plugin prepares one WordPress core profile field update.
				 *
				 * @param int                  $user_id User ID.
				 * @param array<string, mixed> $field Field definition.
				 * @param mixed                $value Sanitized submitted value.
				 * @param string               $screen Screen context: new or edit.
				 */
				do_action( 'atshift_upf_save_profile_field', $user_id, $field, $value, $screen );
				continue;
			}

			update_user_meta( $user_id, $this->meta_key( $field['key'] ), $value );

			if ( 'additional_name' === ( $field['type'] ?? '' ) ) {
				update_user_meta( $user_id, $this->meta_key( $field['key'] . '_type' ), $this->get_submitted_additional_name_type( $field ) );
			}

			/**
			 * Fires after the base plugin saves one custom profile field.
			 *
			 * @param int                  $user_id User ID.
			 * @param array<string, mixed> $field Field definition.
			 * @param mixed                $value Sanitized saved value.
			 * @param string               $screen Screen context: new or edit.
			 */
			do_action( 'atshift_upf_save_profile_field', $user_id, $field, $value, $screen );
		}

		if ( count( $core_updates ) > 1 ) {
			wp_update_user( $core_updates );
		}
	}

	/**
	 * Apply configured initial preferences after a new account is created.
	 *
	 * @param int                               $user_id New user ID.
	 * @param array<int, array<string, mixed>> $fields Field definitions.
	 * @return void
	 */
	private function apply_initial_states_to_new_user( $user_id, $fields ) {
		$updates = array(
			'ID' => $user_id,
		);

		foreach ( $fields as $field ) {
			$type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : '';

			if ( ! Atshift_UPF_Plugin::supports_initial_state( $type ) || 'core_notification' === $type ) {
				continue;
			}

			$value = Atshift_UPF_Plugin::get_field_initial_enabled( $field ) ? '1' : '0';
			$this->queue_core_field_update( $updates, $field, $value );
		}
	}

	/**
	 * Render a field or structure node.
	 *
	 * @param array<string, mixed>              $field Field definition.
	 * @param array<string, array<int, array<string, mixed>>> $tree Field tree.
	 * @param WP_User                          $user User being edited.
	 * @param array<string, mixed>             $values Current values.
	 * @return void
	 */
	private function render_field_node( $field, $tree, $user, $values ) {
		$screen = $user instanceof WP_User ? 'edit' : 'new';

		if ( ! $this->field_matches_profile_context( $field, 'admin_profile_render', $screen, $user ) ) {
			return;
		}

		if ( $this->is_horizontal_group( $field ) ) {
			$this->render_horizontal_group( $field, $tree, $user, $values );
			return;
		}

		if ( $this->is_box_group( $field ) ) {
			$this->render_box_group( $field, $tree, $user, $values );
			return;
		}

		if ( $this->is_conditional_group( $field ) ) {
			if ( $this->has_multiple_conditional_choices( $field ) ) {
				$this->render_field_row( $field, $user, $values );
			} else {
				$this->render_single_conditional_value_row( $field );
			}

			foreach ( $this->get_children( $tree, $field['id'] ) as $child ) {
				$this->render_field_node( $child, $tree, $user, $values );
			}
			return;
		}

		if ( $this->is_accordion_group( $field ) ) {
			$this->render_accordion_group( $field, $tree, $user, $values );
			return;
		}

		$this->render_field_row( $field, $user, $values );
	}

	/**
	 * Render root fields while grouping adjacent feature controls into one panel.
	 *
	 * @param array<int, array<string, mixed>>             $fields Field definitions.
	 * @param array<string, array<int, array<string, mixed>>> $tree Field tree.
	 * @param WP_User|null                                $user User being edited.
	 * @param array<string, mixed>                        $values Current values.
	 * @return void
	 */
	private function render_field_nodes_grouped( $fields, $tree, $user, $values ) {
		$feature_fields = array();

		foreach ( $fields as $field ) {
			if ( $this->is_profile_feature_field( $field ) ) {
				$screen = $user instanceof WP_User ? 'edit' : 'new';
				if ( $this->field_matches_profile_context( $field, 'admin_profile_render', $screen, $user ) ) {
					$feature_fields[] = $field;
				}
				continue;
			}

			if ( ! empty( $feature_fields ) ) {
				$this->render_feature_fields_row( $feature_fields, $user, $values );
				$feature_fields = array();
			}

			$this->render_field_node( $field, $tree, $user, $values );
		}

		if ( ! empty( $feature_fields ) ) {
			$this->render_feature_fields_row( $feature_fields, $user, $values );
		}
	}

	/**
	 * Render profile operation fields in one grouped panel.
	 *
	 * @param array<int, array<string, mixed>> $fields Feature field definitions.
	 * @param WP_User|null                    $user User being edited.
	 * @param array<string, mixed>            $values Current values.
	 * @return void
	 */
	private function render_feature_fields_row( $fields, $user, $values ) {
		$is_submit_section = 1 === count( $fields ) && 'core_submit_button' === ( $fields[0]['type'] ?? '' );
		?>
		<tr class="atshift-upf-profile-feature-group-row<?php echo $is_submit_section ? ' atshift-upf-profile-submit-section-row' : ''; ?>">
			<td colspan="2">
				<div class="atshift-upf-feature-group">
					<?php foreach ( $fields as $field ) : ?>
						<?php $this->render_feature_field_item( $field, $user, $values ); ?>
					<?php endforeach; ?>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render an accordion group.
	 *
	 * @param array<string, mixed>                         $field Field definition.
	 * @param array<string, array<int, array<string, mixed>>> $tree Field tree.
	 * @param WP_User|null                                $user User being edited.
	 * @param array<string, mixed>                        $values Current values.
	 * @return void
	 */
	private function render_accordion_group( $field, $tree, $user, $values ) {
		$children = $this->get_children( $tree, $field['id'] );
		$is_open  = ! empty( $field['accordion_open'] );

		if ( empty( $children ) ) {
			return;
		}
		?>
		<tr class="atshift-upf-profile-accordion-row" data-atshift-upf-field="<?php echo esc_attr( $field['key'] ); ?>" <?php $this->render_condition_attributes( $field ); ?>>
			<th colspan="2">
				<div class="atshift-upf-profile-accordion <?php echo $is_open ? 'is-open' : ''; ?>">
						<button type="button" class="atshift-upf-profile-accordion-toggle" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>">
							<span class="atshift-upf-profile-accordion-title"><?php echo esc_html( $field['label'] ); ?></span>
							<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
					</button>
					<div class="atshift-upf-profile-accordion-body" <?php echo $is_open ? '' : 'hidden'; ?>>
						<?php if ( ! empty( $field['description'] ) ) : ?>
							<p class="description atshift-upf-profile-accordion-notes"><?php echo esc_html( $field['description'] ); ?></p>
						<?php endif; ?>
						<div class="atshift-upf-profile-accordion-fields">
							<?php foreach ( $children as $child ) : ?>
								<?php $this->render_field_block_node( $child, $tree, $user, $values ); ?>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</th>
		</tr>
		<?php
	}

	/**
	 * Render a horizontal group as a compact grid.
	 *
	 * @param array<string, mixed>              $field Field definition.
	 * @param array<string, array<int, array<string, mixed>>> $tree Field tree.
	 * @param WP_User                          $user User being edited.
	 * @param array<string, mixed>             $values Current values.
	 * @return void
	 */
	private function render_horizontal_group( $field, $tree, $user, $values ) {
		$children = $this->get_children( $tree, $field['id'] );
		$columns  = isset( $field['group_columns'] ) ? min( 3, max( 2, (int) $field['group_columns'] ) ) : 2;

		if ( empty( $children ) ) {
			return;
		}
		?>
		<tr class="atshift-upf-profile-group" data-atshift-upf-field="<?php echo esc_attr( $field['key'] ); ?>" <?php $this->render_condition_attributes( $field ); ?>>
			<th>
				<h3><?php echo esc_html( $field['label'] ); ?></h3>
			</th>
			<td>
				<div class="atshift-upf-profile-grid atshift-upf-profile-grid-<?php echo esc_attr( $columns ); ?>">
					<?php $this->render_horizontal_group_children( $children, $tree, $user, $values ); ?>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a boxed group as a bordered field set.
	 *
	 * @param array<string, mixed>              $field Field definition.
	 * @param array<string, array<int, array<string, mixed>>> $tree Field tree.
	 * @param WP_User|null                     $user User being edited.
	 * @param array<string, mixed>             $values Current values.
	 * @return void
	 */
	private function render_box_group( $field, $tree, $user, $values ) {
		$children           = $this->get_children( $tree, $field['id'] );
		$description        = isset( $field['description'] ) ? (string) $field['description'] : '';
		$has_feature_fields = $this->contains_visible_profile_feature_field( $children, $tree, $user );

		if ( empty( $children ) ) {
			return;
		}
		?>
		<tr class="atshift-upf-profile-box-row" data-atshift-upf-field="<?php echo esc_attr( $field['key'] ); ?>" <?php $this->render_condition_attributes( $field ); ?>>
			<td colspan="2">
				<div class="atshift-upf-profile-box<?php echo $has_feature_fields ? ' atshift-upf-profile-box-has-features' : ''; ?>">
					<div class="atshift-upf-profile-box-head">
						<h3><?php echo esc_html( $field['label'] ); ?></h3>
					</div>
					<?php if ( '' !== $description ) : ?>
						<p class="description atshift-upf-profile-box-notes"><?php echo esc_html( $description ); ?></p>
					<?php endif; ?>
					<div class="atshift-upf-profile-box-fields">
						<?php foreach ( $children as $child ) : ?>
							<?php $this->render_field_block_node( $child, $tree, $user, $values ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a field or structure node inside a block layout.
	 *
	 * @param array<string, mixed>                         $field Field definition.
	 * @param array<string, array<int, array<string, mixed>>> $tree Field tree.
	 * @param WP_User|null                                $user User being edited.
	 * @param array<string, mixed>                        $values Current values.
	 * @return void
	 */
	private function render_field_block_node( $field, $tree, $user, $values ) {
		$screen = $user instanceof WP_User ? 'edit' : 'new';

		if ( ! $this->field_matches_profile_context( $field, 'admin_profile_render', $screen, $user ) ) {
			return;
		}

		if ( $this->is_horizontal_group( $field ) ) {
			$this->render_horizontal_group_block( $field, $tree, $user, $values );
			return;
		}

		if ( $this->is_box_group( $field ) ) {
			$this->render_box_group_block( $field, $tree, $user, $values );
			return;
		}

		if ( $this->is_accordion_group( $field ) ) {
			$this->render_accordion_group_block( $field, $tree, $user, $values );
			return;
		}

		if ( $this->is_conditional_group( $field ) ) {
			if ( $this->has_multiple_conditional_choices( $field ) ) {
				$this->render_field_block( $field, $user, $values );
			} else {
				$this->render_single_conditional_value_input( $field );
			}
			foreach ( $this->get_children( $tree, $field['id'] ) as $child ) {
				$this->render_field_block_node( $child, $tree, $user, $values );
			}
			return;
		}

		$this->render_field_block( $field, $user, $values );
	}

	/**
	 * Render a nested accordion group in a block layout.
	 *
	 * @param array<string, mixed>                         $field Field definition.
	 * @param array<string, array<int, array<string, mixed>>> $tree Field tree.
	 * @param WP_User|null                                $user User being edited.
	 * @param array<string, mixed>                        $values Current values.
	 * @return void
	 */
	private function render_accordion_group_block( $field, $tree, $user, $values ) {
		$children = $this->get_children( $tree, $field['id'] );
		$is_open  = ! empty( $field['accordion_open'] );

		if ( empty( $children ) ) {
			return;
		}
		?>
			<div class="atshift-upf-profile-accordion <?php echo $is_open ? 'is-open' : ''; ?>" <?php $this->render_condition_attributes( $field ); ?>>
				<button type="button" class="atshift-upf-profile-accordion-toggle" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>">
					<span class="atshift-upf-profile-accordion-title"><?php echo esc_html( $field['label'] ); ?></span>
					<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
			</button>
			<div class="atshift-upf-profile-accordion-body" <?php echo $is_open ? '' : 'hidden'; ?>>
				<?php if ( ! empty( $field['description'] ) ) : ?>
					<p class="description atshift-upf-profile-accordion-notes"><?php echo esc_html( $field['description'] ); ?></p>
				<?php endif; ?>
				<div class="atshift-upf-profile-accordion-fields">
					<?php foreach ( $children as $child ) : ?>
						<?php $this->render_field_block_node( $child, $tree, $user, $values ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a nested boxed group in a block layout.
	 *
	 * @param array<string, mixed>                         $field Field definition.
	 * @param array<string, array<int, array<string, mixed>>> $tree Field tree.
	 * @param WP_User|null                                $user User being edited.
	 * @param array<string, mixed>                        $values Current values.
	 * @return void
	 */
	private function render_box_group_block( $field, $tree, $user, $values ) {
		$children           = $this->get_children( $tree, $field['id'] );
		$description        = isset( $field['description'] ) ? (string) $field['description'] : '';
		$has_feature_fields = $this->contains_visible_profile_feature_field( $children, $tree, $user );

		if ( empty( $children ) ) {
			return;
		}
		?>
			<div class="atshift-upf-profile-box atshift-upf-profile-box-block<?php echo $has_feature_fields ? ' atshift-upf-profile-box-has-features' : ''; ?>" <?php $this->render_condition_attributes( $field ); ?>>
				<div class="atshift-upf-profile-box-head">
					<h3><?php echo esc_html( $field['label'] ); ?></h3>
				</div>
			<?php if ( '' !== $description ) : ?>
				<p class="description atshift-upf-profile-box-notes"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
			<div class="atshift-upf-profile-box-fields">
				<?php foreach ( $children as $child ) : ?>
					<?php $this->render_field_block_node( $child, $tree, $user, $values ); ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Check whether a structure contains a feature field visible for this user.
	 *
	 * @param array<int, array<string, mixed>>             $fields Child fields.
	 * @param array<string, array<int, array<string, mixed>>> $tree Field tree.
	 * @param WP_User|null                                $user User being edited.
	 * @return bool
	 */
	private function contains_visible_profile_feature_field( $fields, $tree, $user ) {
		foreach ( $fields as $field ) {
			if ( ! $this->field_matches_role_control( $field ) ) {
				continue;
			}

			if ( $this->is_profile_feature_field( $field ) ) {
				return true;
			}

			if ( $this->is_structure_field( $field ) ) {
				$children = $this->get_children( $tree, $field['id'] );
				if ( $this->contains_visible_profile_feature_field( $children, $tree, $user ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Render a nested horizontal group in a block layout.
	 *
	 * @param array<string, mixed>                         $field Field definition.
	 * @param array<string, array<int, array<string, mixed>>> $tree Field tree.
	 * @param WP_User|null                                $user User being edited.
	 * @param array<string, mixed>                        $values Current values.
	 * @return void
	 */
	private function render_horizontal_group_block( $field, $tree, $user, $values ) {
		$children = $this->get_children( $tree, $field['id'] );
		$columns  = isset( $field['group_columns'] ) ? min( 3, max( 2, (int) $field['group_columns'] ) ) : 2;

		if ( empty( $children ) ) {
			return;
		}
		?>
		<div class="atshift-upf-profile-group-block" <?php $this->render_condition_attributes( $field ); ?>>
			<h3><?php echo esc_html( $field['label'] ); ?></h3>
			<div class="atshift-upf-profile-grid atshift-upf-profile-grid-<?php echo esc_attr( $columns ); ?>">
				<?php $this->render_horizontal_group_children( $children, $tree, $user, $values ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render horizontal-group children while sharing one frame for feature fields.
	 *
	 * @param array<int, array<string, mixed>>             $children Child fields.
	 * @param array<string, array<int, array<string, mixed>>> $tree Field tree.
	 * @param WP_User|null                                $user User being edited.
	 * @param array<string, mixed>                        $values Current values.
	 * @return void
	 */
	private function render_horizontal_group_children( $children, $tree, $user, $values ) {
		$feature_fields = array();

		foreach ( $children as $child ) {
			if ( $this->is_profile_feature_field( $child ) ) {
				if ( $this->field_matches_role_control( $child ) ) {
					$feature_fields[] = $child;
				}
				continue;
			}

			if ( ! empty( $feature_fields ) ) {
				$this->render_horizontal_feature_group( $feature_fields, $user, $values );
				$feature_fields = array();
			}

			$this->render_field_block_node( $child, $tree, $user, $values );
		}

		if ( ! empty( $feature_fields ) ) {
			$this->render_horizontal_feature_group( $feature_fields, $user, $values );
		}
	}

	/**
	 * Render feature fields in one full-width horizontal-group frame.
	 *
	 * @param array<int, array<string, mixed>> $fields Feature fields.
	 * @param WP_User|null                    $user User being edited.
	 * @param array<string, mixed>            $values Current values.
	 * @return void
	 */
	private function render_horizontal_feature_group( $fields, $user, $values ) {
		?>
		<div class="atshift-upf-profile-field-block atshift-upf-profile-feature-field atshift-upf-profile-feature-cluster">
			<div class="atshift-upf-feature-group">
				<?php foreach ( $fields as $field ) : ?>
					<?php $this->render_feature_field_item( $field, $user, $values ); ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a field inside a horizontal group.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param WP_User             $user User being edited.
	 * @param array<string, mixed> $values Current values.
	 * @return void
	 */
	private function render_field_block( $field, $user, $values ) {
		if ( ! $this->field_matches_role_control( $field ) ) {
			return;
		}

		$key         = sanitize_key( $field['key'] );
		$value       = isset( $values[ $key ] ) ? $values[ $key ] : $this->get_field_value( $user, $field );
		$field_name  = $this->get_input_name( $field, $key );
		$description = isset( $field['description'] ) ? $field['description'] : '';
		$classes     = $this->get_profile_field_classes( $field, 'atshift-upf-profile-field-block' );
		if ( $this->is_profile_feature_field( $field ) ) {
			?>
			<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php $this->render_core_replacement_attribute( $field ); ?><?php $this->render_condition_attributes( $field ); ?>>
				<div class="atshift-upf-feature-group atshift-upf-feature-group-inline">
					<?php $this->render_feature_field_item( $field, $user, $values ); ?>
				</div>
			</div>
			<?php
			return;
		}
		?>
			<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php $this->render_core_replacement_attribute( $field ); ?><?php $this->render_condition_attributes( $field ); ?>>
				<label for="<?php echo esc_attr( 'atshift_upf_' . $key ); ?>">
					<?php if ( $this->is_required_field( $field ) ) : ?>
						<?php $this->render_required_badge(); ?>
					<?php endif; ?>
					<?php echo esc_html( $field['label'] ); ?>
				</label>
			<?php $this->render_input( $field, $field_name, $value, $user ); ?>
			<?php if ( $description ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render one field row.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param WP_User             $user User being edited.
	 * @param array<string, mixed> $values Current field values.
	 * @return void
	 */
	private function render_field_row( $field, $user, $values ) {
		if ( ! $this->field_matches_role_control( $field ) ) {
			return;
		}

		$key         = sanitize_key( $field['key'] );
		$value       = isset( $values[ $key ] ) ? $values[ $key ] : $this->get_field_value( $user, $field );
		$field_name  = $this->get_input_name( $field, $key );
		$description = isset( $field['description'] ) ? $field['description'] : '';
		$row_classes = $this->get_profile_field_classes( $field, 'atshift-upf-profile-field-row' );
		if ( $this->is_profile_feature_field( $field ) ) {
			?>
			<tr
				class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>"
				data-atshift-upf-field="<?php echo esc_attr( $key ); ?>"
				<?php $this->render_core_replacement_attribute( $field ); ?>
				<?php $this->render_condition_attributes( $field ); ?>
			>
				<td colspan="2">
					<div class="atshift-upf-feature-group">
						<?php $this->render_feature_field_item( $field, $user, $values ); ?>
					</div>
				</td>
			</tr>
			<?php
			return;
		}
		?>
		<tr
			class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>"
			data-atshift-upf-field="<?php echo esc_attr( $key ); ?>"
			<?php $this->render_core_replacement_attribute( $field ); ?>
			<?php $this->render_condition_attributes( $field ); ?>
		>
					<th>
						<label for="<?php echo esc_attr( 'atshift_upf_' . $key ); ?>">
							<?php if ( $this->is_required_field( $field ) ) : ?>
								<?php $this->render_required_badge(); ?>
							<?php endif; ?>
							<?php echo esc_html( $field['label'] ); ?>
						</label>
			</th>
			<td>
				<?php $this->render_input( $field, $field_name, $value, $user ); ?>
				<?php if ( $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Preserve the only available condition without showing a redundant control.
	 *
	 * @param array<string, mixed> $field Conditional field definition.
	 * @return void
	 */
	private function render_single_conditional_value_row( $field ) {
		$choice = $this->get_single_conditional_choice( $field );

		if ( null === $choice ) {
			return;
		}
		?>
		<tr hidden>
			<td colspan="2"><?php $this->render_single_conditional_value_input( $field ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Output the only available conditional value as a hidden field.
	 *
	 * @param array<string, mixed> $field Conditional field definition.
	 * @return void
	 */
	private function render_single_conditional_value_input( $field ) {
		$choice = $this->get_single_conditional_choice( $field );
		$key    = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : '';

		if ( null === $choice || '' === $key ) {
			return;
		}

		printf(
			'<input type="hidden" name="%1$s" value="%2$s">',
			esc_attr( $this->get_input_name( $field, $key ) ),
			esc_attr( $choice )
		);
	}

	/**
	 * Check whether a conditional field still has a meaningful choice.
	 *
	 * @param array<string, mixed> $field Conditional field definition.
	 * @return bool
	 */
	private function has_multiple_conditional_choices( $field ) {
		$choices = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : array();

		return count( $choices ) > 1;
	}

	/**
	 * Return the sole available conditional choice.
	 *
	 * @param array<string, mixed> $field Conditional field definition.
	 * @return string|null
	 */
	private function get_single_conditional_choice( $field ) {
		$choices = isset( $field['choices'] ) && is_array( $field['choices'] ) ? array_values( $field['choices'] ) : array();

		return 1 === count( $choices ) ? (string) $choices[0] : null;
	}

	/**
	 * Render one field inside a feature field group panel.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param WP_User|null        $user User being edited.
	 * @param array<string, mixed> $values Current values.
	 * @return void
	 */
	private function render_feature_field_item( $field, $user, $values ) {
		$key         = sanitize_key( $field['key'] );
		$value       = isset( $values[ $key ] ) ? $values[ $key ] : $this->get_field_value( $user, $field );
		$field_name  = $this->get_input_name( $field, $key );
		$description = isset( $field['description'] ) ? $field['description'] : '';
		?>
		<div class="atshift-upf-feature-group-item" data-atshift-upf-field="<?php echo esc_attr( $key ); ?>"<?php $this->render_core_replacement_attribute( $field ); ?><?php $this->render_condition_attributes( $field ); ?>>
			<label for="<?php echo esc_attr( 'atshift_upf_' . $key ); ?>">
				<?php if ( $this->is_required_field( $field ) ) : ?>
					<?php $this->render_required_badge(); ?>
				<?php endif; ?>
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<div class="atshift-upf-feature-control-main">
				<?php $this->render_input( $field, $field_name, $value, $user ); ?>
				<?php if ( $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render an input control.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string              $name Input name.
	 * @param mixed               $value Current value.
	 * @return void
	 */
	private function render_input( $field, $name, $value, $user = null ) {
		$key         = sanitize_key( $field['key'] );
		$id          = 'atshift_upf_' . $key;
			$type        = isset( $field['type'] ) ? $field['type'] : 'text';
			$stored_type = $type;
			$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
			$choices     = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : array();
			if ( '' === $placeholder ) {
				$placeholder = $this->get_default_placeholder( $field );
			}

			if ( 'core_username' === $type ) {
				if ( $user instanceof WP_User ) {
					printf(
						'<span id="%1$s" class="atshift-upf-readonly-value" aria-label="%2$s">%3$s</span><input type="hidden" name="%4$s" value="%5$s">',
						esc_attr( $id ),
						esc_attr( $field['label'] ),
						esc_html( $value ),
						esc_attr( $name ),
						esc_attr( $value )
					);
					return;
				}

				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text" placeholder="%4$s" pattern="[A-Za-z0-9_.@-]+" title="%5$s" required>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $placeholder ),
					esc_attr__( 'Use only letters, numbers, and these symbols: _ . - @. Spaces cannot be used.', 'atshift-user-profile-fields' )
				);
				return;
			}

			if ( 'core_email' === $type ) {
				printf(
					'<input type="email" id="%1$s" name="%2$s" value="%3$s" class="regular-text" placeholder="%4$s" required>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $placeholder )
				);
				return;
			}

			if ( 'core_visual_editor' === $type ) {
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="false">';
				echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( $value, '1', false ) . '> ' . esc_html__( 'Enable the visual editor when writing.', 'atshift-user-profile-fields' ) . '</label>';
				return;
			}

			if ( 'core_admin_color' === $type ) {
				global $_wp_admin_css_colors;
				$schemes = is_array( $_wp_admin_css_colors ) ? $_wp_admin_css_colors : array();
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" data-atshift-upf-admin-color>';
				foreach ( $schemes as $scheme_key => $scheme ) {
					$label = is_object( $scheme ) && ! empty( $scheme->name ) ? $scheme->name : $scheme_key;
					echo '<option value="' . esc_attr( $scheme_key ) . '" ' . selected( $value, $scheme_key, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
				return;
			}

			if ( 'core_syntax_highlighting' === $type ) {
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="false">';
				echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( $value, '1', false ) . '> ' . esc_html__( 'Enable syntax highlighting when editing code.', 'atshift-user-profile-fields' ) . '</label>';
				return;
			}

			if ( 'core_keyboard_shortcuts' === $type ) {
				echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="true" ' . checked( $value, '1', false ) . '> ' . esc_html__( 'Enable keyboard shortcuts for comment moderation.', 'atshift-user-profile-fields' ) . '</label>';
				return;
			}

			if ( 'core_toolbar' === $type ) {
				echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( $value, '1', false ) . '> ' . esc_html__( 'Show Toolbar when viewing site.', 'atshift-user-profile-fields' ) . '</label>';
				return;
			}

			if ( 'core_language' === $type ) {
				$locale = is_scalar( $value ) ? (string) $value : '';
				if ( '' === $locale && ! isset( $_POST['locale'] ) ) {
					$locale = 'site-default';
				}
				if ( function_exists( 'wp_dropdown_languages' ) ) {
					wp_dropdown_languages(
						array(
							'id'                        => $id,
							'name'                      => $name,
							'selected'                  => $locale,
							'languages'                 => get_available_languages(),
							'show_available_translations' => false,
							'show_option_site_default'  => true,
						)
					);
				}
				return;
			}

			if ( 'core_password' === $type ) {
				$is_edit_screen = $user instanceof WP_User;

				echo '<div class="atshift-upf-password-field" data-atshift-upf-password-field>';
				if ( $is_edit_screen ) {
					echo '<button type="button" class="button atshift-upf-set-password" aria-expanded="false">' . esc_html__( 'Set New Password', 'atshift-user-profile-fields' ) . '</button>';
				} else {
					echo '<button type="button" class="button atshift-upf-generate-password">' . esc_html__( 'Generate Password', 'atshift-user-profile-fields' ) . '</button>';
				}
				echo '<div class="atshift-upf-password-editor"' . ( $is_edit_screen ? ' hidden' : '' ) . ' data-atshift-upf-password-editor>';
				echo '<div class="atshift-upf-password-input-row">';
				printf(
					'<input type="password" id="%1$s" name="%2$s" value="" class="regular-text" placeholder="%3$s" autocomplete="new-password" data-atshift-upf-password-source data-atshift-upf-password-input>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $placeholder )
				);
				echo '<button type="button" class="button atshift-upf-toggle-password">' . esc_html__( 'Show', 'atshift-user-profile-fields' ) . '</button>';
				if ( $is_edit_screen ) {
					echo '<button type="button" class="button atshift-upf-cancel-password">' . esc_html__( 'Cancel', 'atshift-user-profile-fields' ) . '</button>';
				}
				echo '</div>';
				echo '<div class="atshift-upf-password-strength" data-atshift-upf-password-strength hidden></div>';
				echo '<input type="hidden" name="pass2" value="" data-atshift-upf-password-confirm>';
				echo '</div>';
				echo '</div>';
				return;
			}

			if ( 'core_notification' === $type ) {
				echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( $value, '1', false ) . '> ' . esc_html__( 'Send the new user an email about their account.', 'atshift-user-profile-fields' ) . '</label>';
				return;
			}

			if ( 'core_role' === $type ) {
				$roles = wp_roles()->roles;
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
				foreach ( $roles as $role_key => $role ) {
					echo '<option value="' . esc_attr( $role_key ) . '" ' . selected( $value, $role_key, false ) . '>' . esc_html( translate_user_role( $role['name'] ) ) . '</option>';
				}
				echo '</select>';
				return;
			}

			if ( 'core_profile_picture' === $type ) {
				$avatar_user = $user instanceof WP_User ? $user : wp_get_current_user();
				$description = '';

				if ( get_current_user_id() === (int) $avatar_user->ID ) {
					$description = sprintf(
						/* translators: %s: Gravatar URL. */
						__( '<a href="%s">You can change your profile picture on Gravatar</a>.', 'atshift-user-profile-fields' ),
						/* translators: The localized Gravatar URL. */
						__( 'https://gravatar.com/', 'atshift-user-profile-fields' )
					);
				}

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Matches the WordPress core profile picture filter.
				$description = apply_filters( 'user_profile_picture_description', $description, $avatar_user );

				echo '<div class="atshift-upf-core-profile-picture">';
				echo get_avatar( $avatar_user->ID, 96 );
				if ( '' !== $description ) {
					echo '<p class="description">' . wp_kses_post( $description ) . '</p>';
				}
				echo '</div>';
				return;
			}

			if ( 'core_sessions' === $type ) {
				if ( ! $user instanceof WP_User || ! class_exists( 'WP_Session_Tokens' ) ) {
					echo '<span class="atshift-upf-readonly-value">' . esc_html__( 'Session controls are managed by WordPress after the user is saved.', 'atshift-user-profile-fields' ) . '</span>';
					return;
				}

				$sessions       = WP_Session_Tokens::get_instance( $user->ID );
				$session_count  = count( $sessions->get_all() );
				$is_own_profile = get_current_user_id() === (int) $user->ID;

				echo '<div class="atshift-upf-session-control" aria-live="polite">';
				if ( $is_own_profile ) {
					printf(
						'<button type="button" class="button" data-atshift-upf-destroy-sessions%1$s>%2$s</button>',
						$session_count <= 1 ? ' disabled' : '',
						esc_html__( 'Log Out Everywhere Else', 'atshift-user-profile-fields' )
					);

					if ( $session_count <= 1 ) {
						echo '<p class="description">' . esc_html__( 'You are only logged in at this location.', 'atshift-user-profile-fields' ) . '</p>';
					} else {
						echo '<p class="description">' . esc_html__( 'Did you lose your phone or leave your account logged in at a public computer? You can log out everywhere else, and stay logged in here.', 'atshift-user-profile-fields' ) . '</p>';
					}
				} elseif ( $session_count > 0 ) {
					echo '<button type="button" class="button" data-atshift-upf-destroy-sessions>' . esc_html__( 'Log Out Everywhere', 'atshift-user-profile-fields' ) . '</button>';
					printf(
						'<p class="description">%s</p>',
						esc_html(
							sprintf(
								/* translators: %s: User's display name. */
								__( 'Log %s out of all locations.', 'atshift-user-profile-fields' ),
								$user->display_name
							)
						)
					);
				} else {
					echo '<p class="description">' . esc_html__( 'There are no active sessions for this user.', 'atshift-user-profile-fields' ) . '</p>';
				}
				echo '</div>';
				return;
			}

			if ( 'core_application_passwords' === $type ) {
				echo '<div class="atshift-upf-native-section-target" data-atshift-upf-native-section-target="application_passwords">';
				echo '<p class="description">' . esc_html__( 'Application password controls are managed by WordPress after the user is saved.', 'atshift-user-profile-fields' ) . '</p>';
				echo '</div>';
				return;
			}

			if ( 'core_submit_button' === $type ) {
				$label = $user instanceof WP_User ? __( 'Update User', 'atshift-user-profile-fields' ) : __( 'Add New User', 'atshift-user-profile-fields' );
				submit_button( $label, 'primary large', 'submit', false );
				return;
			}

			if ( 'image' === $type ) {
				$image_url = esc_url( $value );
				echo '<div class="atshift-upf-image-field" data-atshift-upf-image-field>';
				echo '<div class="atshift-upf-image-preview" data-atshift-upf-image-preview>';
				if ( $image_url ) {
					echo '<img src="' . esc_url( $image_url ) . '" alt="">';
				}
				echo '</div>';
				echo '<input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $image_url ) . '" data-atshift-upf-image-input>';
				echo '<button type="button" class="button atshift-upf-select-image">' . esc_html__( 'Select Image', 'atshift-user-profile-fields' ) . '</button> ';
				echo '<button type="button" class="button atshift-upf-remove-image" ' . ( $image_url ? '' : 'hidden' ) . '>' . esc_html__( 'Remove', 'atshift-user-profile-fields' ) . '</button>';
				echo '</div>';
				return;
			}

			if ( 'conditional' === $type ) {
			$controller_id = isset( $field['id'] ) ? (string) $field['id'] : $key;

			if ( 'radio' === ( $field['conditional_input'] ?? 'select' ) ) {
				$selected_value = in_array( (string) $value, array_map( 'strval', $choices ), true ) ? (string) $value : ( isset( $choices[0] ) ? (string) $choices[0] : '' );
				echo '<fieldset class="atshift-upf-choice-list" data-atshift-upf-conditional-controller="' . esc_attr( $controller_id ) . '">';
				foreach ( $choices as $choice ) {
					echo '<label><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $choice ) . '" ' . checked( $selected_value, $choice, false ) . '> ' . esc_html( $choice ) . '</label>';
				}
				echo '</fieldset>';
				return;
			}

			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" data-atshift-upf-conditional-controller="' . esc_attr( $controller_id ) . '">';
			echo '<option value="">' . esc_html__( 'Please select...', 'atshift-user-profile-fields' ) . '</option>';
			foreach ( $choices as $choice ) {
				echo '<option value="' . esc_attr( $choice ) . '" ' . selected( $value, $choice, false ) . '>' . esc_html( $choice ) . '</option>';
			}
			echo '</select>';
			return;
		}

		if ( 'core_bio' === $type ) {
			$type = 'textarea';
		}

		if ( 'core_website' === $type ) {
			$type = 'url';
		}

		if ( in_array( $type, array( 'core_first_name', 'core_last_name', 'core_nickname' ), true ) ) {
			$type = 'text';
		}

			if ( 'core_display_name' === $type && $user instanceof WP_User ) {
				$options = $this->get_display_name_options( $user );
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
				foreach ( $options as $option ) {
					echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( $option ) . '</option>';
				}
				echo '</select>';
				return;
			}

			if ( 'additional_name' === $type ) {
				$type_name  = 'atshift_upf_additional_name_types[' . $key . ']';
				$type_value = $this->get_additional_name_type_value( $user, $field );

				echo '<div class="atshift-upf-additional-name-field">';
				echo '<select class="atshift-upf-additional-name-type" name="' . esc_attr( $type_name ) . '" aria-label="' . esc_attr__( 'Name Type', 'atshift-user-profile-fields' ) . '">';
				foreach ( $this->get_additional_name_type_options() as $option_value => $option_label ) {
					echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $type_value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
				}
				echo '</select>';
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text" placeholder="%4$s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $placeholder )
				);
				echo '</div>';
				return;
			}

			if ( 'textarea' === $type ) {
			printf(
				'<textarea id="%1$s" name="%2$s" rows="4" class="regular-text" placeholder="%3$s">%4$s</textarea>',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $placeholder ),
				esc_textarea( $value )
			);
			return;
		}

		if ( 'select' === $type ) {
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
			echo '<option value="">' . esc_html__( 'Please select...', 'atshift-user-profile-fields' ) . '</option>';
			foreach ( $choices as $choice ) {
				echo '<option value="' . esc_attr( $choice ) . '" ' . selected( $value, $choice, false ) . '>' . esc_html( $choice ) . '</option>';
			}
			echo '</select>';
			return;
		}

		if ( 'radio' === $type ) {
			echo '<fieldset class="atshift-upf-choice-list">';
			foreach ( $choices as $choice ) {
				echo '<label><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $choice ) . '" ' . checked( $value, $choice, false ) . '> ' . esc_html( $choice ) . '</label>';
			}
			echo '</fieldset>';
			return;
		}

		if ( 'checkbox' === $type ) {
			echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( $value, '1', false ) . '> ' . esc_html__( 'Enabled', 'atshift-user-profile-fields' ) . '</label>';
			return;
		}

		$input_type = 'text';
		if ( $this->is_format_validation_enabled( $field ) && in_array( $type, array( 'email', 'url' ), true ) ) {
			$input_type = $type;
		}
		if ( 'core_website' === $stored_type ) {
			$input_type = 'url';
		}
		if ( $this->is_format_validation_enabled( $field ) && 'phone' === $type ) {
			$input_type = 'tel';
		}
		if ( 'number' === $type ) {
			$input_type = 'number';
		}
		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" placeholder="%5$s">',
			esc_attr( $input_type ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value ),
			esc_attr( $placeholder )
		);
	}

	/**
	 * Return a helpful placeholder when a field has none configured.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return string
	 */
	private function get_default_placeholder( $field ) {
		$type = isset( $field['type'] ) ? (string) $field['type'] : 'text';

		$placeholders = array(
			'core_username' => __( 'example_user', 'atshift-user-profile-fields' ),
			'core_email'    => __( 'name@example.com', 'atshift-user-profile-fields' ),
			'core_password' => __( '8+ characters, not letters-only or numbers-only', 'atshift-user-profile-fields' ),
			'email'         => __( 'name@example.com', 'atshift-user-profile-fields' ),
			'phone'         => __( '090-1234-5678', 'atshift-user-profile-fields' ),
			'url'           => __( 'https://example.com/', 'atshift-user-profile-fields' ),
			'core_website'  => __( 'https://example.com/', 'atshift-user-profile-fields' ),
			'number'        => __( '123', 'atshift-user-profile-fields' ),
		);

		return isset( $placeholders[ $type ] ) ? $placeholders[ $type ] : '';
	}

	/**
	 * Sanitize a submitted value for a field.
	 *
	 * @param mixed               $value Raw value.
	 * @param array<string, mixed> $field Field definition.
	 * @return string
	 */
	private function sanitize_value( $value, $field ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		if ( 'core_bio' === $type ) {
			return sanitize_textarea_field( $value );
		}

		if ( 'core_website' === $type ) {
			return esc_url_raw( $value );
		}

		if ( 'image' === $type ) {
			return esc_url_raw( $value );
		}

		if ( 'core_email' === $type ) {
			return sanitize_email( $value );
		}

		if ( 'core_admin_color' === $type ) {
			$scheme = sanitize_key( $value );
			global $_wp_admin_css_colors;
			return isset( $_wp_admin_css_colors[ $scheme ] ) ? $scheme : 'fresh';
		}

		if ( in_array( $type, array( 'core_visual_editor', 'core_syntax_highlighting', 'core_keyboard_shortcuts', 'core_toolbar' ), true ) ) {
			return empty( $value ) ? '0' : '1';
		}

		if ( 'core_language' === $type ) {
			$locale = sanitize_text_field( $value );
			return 'site-default' === $locale ? '' : $locale;
		}

		if ( 'core_password' === $type ) {
			return (string) $value;
		}

		if ( 'core_notification' === $type ) {
			return empty( $value ) ? '0' : '1';
		}

		if ( 'core_role' === $type ) {
			$role = sanitize_key( $value );
			return isset( wp_roles()->roles[ $role ] ) ? $role : get_option( 'default_role', 'subscriber' );
		}

		if ( in_array( $type, array( 'core_profile_picture', 'core_sessions', 'core_application_passwords', 'core_submit_button' ), true ) ) {
			return '';
		}

		if ( 'conditional' === $type ) {
			$choices = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : array();
			return in_array( $value, $choices, true ) ? sanitize_text_field( $value ) : '';
		}

		if ( 'email' === $type && $this->is_format_validation_enabled( $field ) ) {
			return sanitize_email( $value );
		}

		if ( 'url' === $type && $this->is_format_validation_enabled( $field ) ) {
			return esc_url_raw( $value );
		}

		if ( 'phone' === $type ) {
			return sanitize_text_field( $value );
		}

		if ( 'number' === $type ) {
			return is_numeric( $value ) ? (string) $value : '';
		}

		if ( 'textarea' === $type ) {
			return sanitize_textarea_field( $value );
		}

		if ( 'checkbox' === $type ) {
			return empty( $value ) ? '0' : '1';
		}

		if ( in_array( $type, array( 'radio', 'select' ), true ) ) {
			$choices = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : array();
			return in_array( $value, $choices, true ) ? sanitize_text_field( $value ) : '';
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Return current values for all plugin fields.
	 *
	 * @param int                         $user_id User ID.
	 * @param array<int, array<string, mixed>> $fields Fields.
	 * @return array<string, mixed>
	 */
	private function get_user_field_values( $user, $fields ) {
		$values = array();

		foreach ( $fields as $field ) {
			if ( empty( $field['key'] ) ) {
				continue;
			}

			$key            = sanitize_key( $field['key'] );
			$values[ $key ] = $this->get_field_value( $user, $field );
		}

		return $values;
	}

	/**
	 * Return the native or plugin input name for a field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string              $key Sanitized field key.
	 * @return string
	 */
	private function get_input_name( $field, $key ) {
		$map = array(
			'core_username'     => 'user_login',
			'core_email'        => 'email',
			'core_visual_editor' => 'rich_editing',
			'core_admin_color'  => 'admin_color',
			'core_syntax_highlighting' => 'syntax_highlighting',
			'core_keyboard_shortcuts' => 'comment_shortcuts',
			'core_toolbar'      => 'admin_bar_front',
			'core_first_name'   => 'first_name',
			'core_last_name'    => 'last_name',
			'core_nickname'     => 'nickname',
			'core_display_name' => 'display_name',
			'core_language'     => 'locale',
			'core_website'      => 'url',
			'core_bio'          => 'description',
			'core_password'     => 'pass1',
			'core_notification' => 'send_user_notification',
			'core_role'         => 'role',
		);
		$type = isset( $field['type'] ) ? $field['type'] : '';

		if ( isset( $map[ $type ] ) ) {
			return $map[ $type ];
		}

		return 'atshift_upf_fields[' . $key . ']';
	}

	/**
	 * Get a submitted value from native WordPress fields or plugin fields.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param array<string, mixed> $values Submitted plugin values.
	 * @return mixed
	 */
	private function get_submitted_value( $field, $values ) {
		$key  = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : '';
		$name = $this->get_input_name( $field, $key );

		if ( 0 === strpos( $name, 'atshift_upf_fields[' ) ) {
			return isset( $values[ $key ] ) ? $values[ $key ] : '';
		}

		if ( in_array( $field['type'] ?? '', array( 'core_visual_editor', 'core_syntax_highlighting', 'core_keyboard_shortcuts', 'core_toolbar', 'core_notification' ), true ) ) {
			$has_value = isset( $_POST[ $name ] );
			$value     = $has_value ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';

			return $this->normalize_core_checkbox_value( $field['type'], $value, $has_value );
		}

		return isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
	}

	/**
	 * Normalize WordPress checkbox conventions to the plugin's enabled state.
	 *
	 * @param string $type Field type.
	 * @param string $value Submitted value.
	 * @param bool   $has_value Whether the input was submitted.
	 * @return string
	 */
	private function normalize_core_checkbox_value( $type, $value, $has_value ) {
		if ( ! $has_value ) {
			return '0';
		}

		if ( 'core_syntax_highlighting' === $type ) {
			return 'false' === (string) $value ? '0' : '1';
		}

		if ( 'core_visual_editor' === $type ) {
			return 'false' === (string) $value ? '0' : '1';
		}

		if ( 'core_keyboard_shortcuts' === $type ) {
			return 'true' === (string) $value ? '1' : '0';
		}

		return empty( $value ) ? '0' : '1';
	}

	/**
	 * Get the submitted additional-name type.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return string
	 */
	private function get_submitted_additional_name_type( $field ) {
		$key   = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : '';
		$types = isset( $_POST['atshift_upf_additional_name_types'] ) ? (array) wp_unslash( $_POST['atshift_upf_additional_name_types'] ) : array();

		return $this->sanitize_additional_name_type( isset( $types[ $key ] ) ? $types[ $key ] : '' );
	}

	/**
	 * Return the saved additional-name type.
	 *
	 * @param WP_User|null         $user User being edited.
	 * @param array<string, mixed> $field Field definition.
	 * @return string
	 */
	private function get_additional_name_type_value( $user, $field ) {
		$key = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : '';

		if ( ! $user instanceof WP_User ) {
			return $this->get_submitted_additional_name_type( $field );
		}

		return $this->sanitize_additional_name_type( get_user_meta( $user->ID, $this->meta_key( $key . '_type' ), true ) );
	}

	/**
	 * Return fixed additional-name type options.
	 *
	 * @return array<string, string>
	 */
	private function get_additional_name_type_options() {
		return array(
			'middle'    => __( 'Middle Name', 'atshift-user-profile-fields' ),
			'full'      => __( 'Full Name', 'atshift-user-profile-fields' ),
			'phonetic'  => __( 'Phonetic Name', 'atshift-user-profile-fields' ),
			'preferred' => __( 'Preferred Name', 'atshift-user-profile-fields' ),
			'other'     => __( 'Other Name', 'atshift-user-profile-fields' ),
		);
	}

	/**
	 * Sanitize the additional-name type.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_additional_name_type( $value ) {
		$value   = sanitize_key( $value );
		$options = $this->get_additional_name_type_options();

		return isset( $options[ $value ] ) ? $value : 'middle';
	}

	/**
	 * Return a field value from either custom meta or WordPress core fields.
	 *
	 * @param WP_User             $user User being edited.
	 * @param array<string, mixed> $field Field definition.
	 * @return mixed
	 */
	private function get_field_value( $user, $field ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';
		$key  = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : '';

		if ( ! $user instanceof WP_User ) {
			$post_map = array(
			'core_username'     => 'user_login',
			'core_email'        => 'email',
			'core_visual_editor' => 'rich_editing',
			'core_admin_color'  => 'admin_color',
			'core_syntax_highlighting' => 'syntax_highlighting',
			'core_keyboard_shortcuts' => 'comment_shortcuts',
			'core_toolbar'      => 'admin_bar_front',
			'core_first_name'   => 'first_name',
				'core_last_name'    => 'last_name',
				'core_nickname'     => 'nickname',
				'core_display_name' => 'display_name',
				'core_language'     => 'locale',
				'core_website'      => 'url',
				'core_bio'          => 'description',
				'core_notification' => 'send_user_notification',
				'core_role'         => 'role',
			);

			if ( isset( $post_map[ $type ], $_POST[ $post_map[ $type ] ] ) ) {
				$posted_value = sanitize_text_field( wp_unslash( $_POST[ $post_map[ $type ] ] ) );

				if ( Atshift_UPF_Plugin::supports_initial_state( $type ) ) {
					return $this->normalize_core_checkbox_value( $type, $posted_value, true );
				}

				return $posted_value;
			}

			if (
				Atshift_UPF_Plugin::supports_initial_state( $type )
				&& isset( $_SERVER['REQUEST_METHOD'] )
				&& 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) )
			) {
				return '0';
			}

			if ( Atshift_UPF_Plugin::supports_initial_state( $type ) ) {
				return Atshift_UPF_Plugin::get_field_initial_enabled( $field ) ? '1' : '0';
			}

			if ( 'core_role' === $type ) {
				return get_option( 'default_role', 'subscriber' );
			}

			return '';
		}

		switch ( $type ) {
			case 'core_username':
				return $user->user_login;
			case 'core_email':
				return $user->user_email;
			case 'core_visual_editor':
				return 'false' === (string) get_user_option( 'rich_editing', $user->ID ) ? '0' : '1';
			case 'core_admin_color':
				return get_user_option( 'admin_color', $user->ID );
			case 'core_syntax_highlighting':
				return 'false' === (string) get_user_option( 'syntax_highlighting', $user->ID ) ? '0' : '1';
			case 'core_keyboard_shortcuts':
				return 'true' === (string) get_user_option( 'comment_shortcuts', $user->ID ) ? '1' : '0';
			case 'core_toolbar':
				return 'false' === (string) get_user_option( 'show_admin_bar_front', $user->ID ) ? '0' : '1';
			case 'core_first_name':
				return get_user_meta( $user->ID, 'first_name', true );
			case 'core_last_name':
				return get_user_meta( $user->ID, 'last_name', true );
			case 'core_nickname':
				return get_user_meta( $user->ID, 'nickname', true );
			case 'core_display_name':
				return $user->display_name;
			case 'core_language':
				return get_user_meta( $user->ID, 'locale', true );
			case 'core_website':
				return $user->user_url;
			case 'core_bio':
				return get_user_meta( $user->ID, 'description', true );
			case 'core_role':
				return isset( $user->roles[0] ) ? $user->roles[0] : get_option( 'default_role', 'subscriber' );
			case 'core_profile_picture':
			case 'core_sessions':
			case 'core_application_passwords':
			case 'core_submit_button':
				return '';
		}

		return get_user_meta( $user->ID, $this->meta_key( $key ), true );
	}

	/**
	 * Queue a WordPress core profile field update.
	 *
	 * @param array<string, mixed> $updates User update payload.
	 * @param array<string, mixed> $field Field definition.
	 * @param string              $value Sanitized value.
	 * @return void
	 */
	private function queue_core_field_update( &$updates, $field, $value ) {
		switch ( $field['type'] ?? '' ) {
			case 'core_visual_editor':
				update_user_option( $updates['ID'], 'rich_editing', '1' === $value ? 'true' : 'false', true );
				break;
			case 'core_admin_color':
				update_user_option( $updates['ID'], 'admin_color', $value, true );
				break;
			case 'core_syntax_highlighting':
				update_user_option( $updates['ID'], 'syntax_highlighting', '1' === $value ? 'true' : 'false', true );
				break;
			case 'core_keyboard_shortcuts':
				update_user_option( $updates['ID'], 'comment_shortcuts', '1' === $value ? 'true' : 'false', true );
				break;
			case 'core_toolbar':
				update_user_option( $updates['ID'], 'show_admin_bar_front', '1' === $value ? 'true' : 'false', true );
				break;
			case 'core_first_name':
				update_user_meta( $updates['ID'], 'first_name', $value );
				break;
			case 'core_last_name':
				update_user_meta( $updates['ID'], 'last_name', $value );
				break;
			case 'core_nickname':
				update_user_meta( $updates['ID'], 'nickname', $value );
				break;
			case 'core_bio':
				update_user_meta( $updates['ID'], 'description', $value );
				break;
			case 'core_display_name':
				$updates['display_name'] = $value;
				break;
				case 'core_website':
					$updates['user_url'] = $value;
					break;
				case 'core_email':
					$updates['user_email'] = $value;
					break;
				case 'core_language':
					update_user_meta( $updates['ID'], 'locale', $value );
					break;
				case 'core_password':
					if ( '' !== (string) $value ) {
						$updates['user_pass'] = $value;
					}
					break;
				case 'core_role':
					if ( current_user_can( 'promote_user', $updates['ID'] ) ) {
						$updates['role'] = $value;
					}
					break;
				case 'core_profile_picture':
				case 'core_sessions':
				case 'core_application_passwords':
				case 'core_submit_button':
					break;
			}
		}

	/**
	 * Build a tree keyed by parent ID.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields.
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private function build_field_tree( $fields ) {
		$tree      = array( 'root' => array() );
		$available = array();

		foreach ( $fields as $field ) {
			if ( ! empty( $field['id'] ) ) {
				$available[ $field['id'] ] = true;
			}
		}

		foreach ( $fields as $field ) {
			$parent_id = isset( $field['parent_id'] ) ? (string) $field['parent_id'] : '';

			if ( '' === $parent_id || empty( $available[ $parent_id ] ) ) {
				$tree['root'][] = $field;
				continue;
			}

			if ( empty( $tree[ $parent_id ] ) ) {
				$tree[ $parent_id ] = array();
			}

			$tree[ $parent_id ][] = $field;
		}

		return $tree;
	}

	/**
	 * Return child fields for a parent.
	 *
	 * @param array<string, array<int, array<string, mixed>>> $tree Field tree.
	 * @param string                                         $parent_id Parent field ID.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_children( $tree, $parent_id ) {
		return isset( $tree[ $parent_id ] ) ? $tree[ $parent_id ] : array();
	}

	/**
	 * Print conditional display attributes for a child field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return void
	 */
	private function render_condition_attributes( $field ) {
		if ( empty( $field['parent_id'] ) || empty( $field['conditional_value'] ) ) {
			return;
		}

		echo ' data-atshift-upf-parent="' . esc_attr( $field['parent_id'] ) . '"';
		echo ' data-atshift-upf-choice="' . esc_attr( $field['conditional_value'] ) . '"';
	}

	/**
	 * Get display name choices similar to WordPress core.
	 *
	 * @param WP_User $user User.
	 * @return array<int, string>
	 */
	private function get_display_name_options( $user ) {
		$options = array(
			$user->display_name,
			$user->user_login,
			$user->nickname,
			$user->first_name,
			$user->last_name,
			trim( $user->first_name . ' ' . $user->last_name ),
			trim( $user->last_name . ' ' . $user->first_name ),
		);

		return array_values( array_unique( array_filter( array_map( 'trim', $options ) ) ) );
	}

	/**
	 * Render the required badge used on profile screens.
	 *
	 * @return void
	 */
	private function render_required_badge() {
		echo '<span class="atshift-upf-required-badge">' . esc_html__( 'Required', 'atshift-user-profile-fields' ) . '</span>';
	}

	/**
	 * Return the profile field section heading.
	 *
	 * @param array<int, array<string, mixed>> $fields Field definitions.
	 * @return string
	 */
	private function get_profile_section_heading( $fields ) {
		foreach ( $fields as $field ) {
			if ( in_array( $field['type'] ?? '', array( 'core_username', 'core_email', 'core_password' ), true ) ) {
				return '';
			}
		}

		return __( 'Additional Settings', 'atshift-user-profile-fields' );
	}

	/**
	 * Return fields appropriate for a profile screen context.
	 *
	 * @param array<int, array<string, mixed>> $fields Field definitions.
	 * @param string                          $screen Screen context: new or edit.
	 * @return array<int, array<string, mixed>>
	 */
	private function filter_fields_for_screen( $fields, $screen ) {
		$availability_callback = 'new' === $screen
			? array( $this, 'is_available_on_new_user_screen' )
			: array( $this, 'is_available_on_edit_user_screen' );
		$filtered              = array_values(
			array_filter(
				$fields,
				function ( $field ) use ( $availability_callback, $screen ) {
					if ( ! call_user_func( $availability_callback, $field ) ) {
						return false;
					}

					return ! $this->is_core_field( $field ) || $this->is_core_replacement_supported( $field, $screen );
				}
			)
		);

		/**
		 * Filters fields available for a WordPress profile screen before empty
		 * structure groups and conditional branches are pruned.
		 *
		 * @param array<int, array<string, mixed>> $filtered Field definitions.
		 * @param string                          $screen Screen context: new or edit.
		 */
		$filtered = apply_filters( 'atshift_upf_profile_fields_for_screen', $filtered, $screen );

		do {
			$child_counts = array();

			foreach ( $filtered as $field ) {
				$parent_id = isset( $field['parent_id'] ) ? (string) $field['parent_id'] : '';

				if ( '' !== $parent_id ) {
					$child_counts[ $parent_id ] = isset( $child_counts[ $parent_id ] )
						? $child_counts[ $parent_id ] + 1
						: 1;
				}
			}

			$before_count = count( $filtered );
			$filtered     = array_values(
				array_filter(
					$filtered,
					function ( $field ) use ( $child_counts ) {
						if ( ! $this->is_structure_field( $field ) ) {
							return true;
						}

						$field_id = isset( $field['id'] ) ? (string) $field['id'] : '';

						return '' !== $field_id && ! empty( $child_counts[ $field_id ] );
					}
				)
			);
		} while ( count( $filtered ) < $before_count );

		return $this->localize_default_field_texts(
			$this->filter_conditional_choices_for_available_children( $filtered )
		);
	}

	/**
	 * Translate bundled default field labels and notes without changing custom text.
	 *
	 * @param array<int, array<string, mixed>> $fields Field definitions.
	 * @return array<int, array<string, mixed>>
	 */
	private function localize_default_field_texts( $fields ) {
		foreach ( $fields as &$field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			if ( ! $this->is_localizable_default_field_text( $field ) ) {
				continue;
			}

			if ( isset( $field['label'] ) && is_scalar( $field['label'] ) ) {
				$field['label'] = $this->localize_default_field_text( (string) $field['label'], 'label' );
			}

			if ( isset( $field['description'] ) && is_scalar( $field['description'] ) ) {
				$field['description'] = $this->localize_default_field_text( (string) $field['description'], 'description' );
			}
		}
		unset( $field );

		return $fields;
	}

	/**
	 * Check whether a field belongs to the bundled default profile set.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_localizable_default_field_text( $field ) {
		$type = isset( $field['type'] ) ? (string) $field['type'] : '';

		if ( 0 === strpos( $type, 'core_' ) ) {
			return true;
		}

		$id  = isset( $field['id'] ) ? sanitize_key( $field['id'] ) : '';
		$key = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : '';

		return in_array(
			$id,
			$this->get_default_structure_field_keys(),
			true
		) || in_array(
			$key,
			$this->get_default_structure_field_keys(),
			true
		);
	}

	/**
	 * Return structure keys used by the bundled default profile sets.
	 *
	 * @return array<int, string>
	 */
	private function get_default_structure_field_keys() {
		return array(
			'section_personal_settings',
			'section_name',
			'section_contact',
			'section_about',
			'section_account_management',
			'section_permissions',
		);
	}

	/**
	 * Translate one bundled default field text value.
	 *
	 * @param string $text Text saved in the field definition.
	 * @param string $kind Text kind: label or description.
	 * @return string
	 */
	private function localize_default_field_text( $text, $kind ) {
		$map = 'description' === $kind
			? $this->get_default_description_translation_map()
			: $this->get_default_label_translation_map();

		return isset( $map[ $text ] ) ? $map[ $text ] : $text;
	}

	/**
	 * Return bundled default label translations keyed by stored English text.
	 *
	 * @return array<string, string>
	 */
	private function get_default_label_translation_map() {
		return array(
			'Personal Options'              => __( 'Personal Options', 'atshift-user-profile-fields' ),
			'Syntax Highlighting'           => __( 'Syntax highlighting', 'atshift-user-profile-fields' ),
			'Admin Color Scheme'            => __( 'Admin color scheme', 'atshift-user-profile-fields' ),
			'Keyboard Shortcuts'            => __( 'Keyboard shortcuts', 'atshift-user-profile-fields' ),
			'Toolbar'                       => __( 'Toolbar', 'atshift-user-profile-fields' ),
			'Language'                      => __( 'Language', 'atshift-user-profile-fields' ),
			'Username'                      => __( 'Username', 'atshift-user-profile-fields' ),
			'Name'                          => __( 'Name', 'atshift-user-profile-fields' ),
			'First Name'                    => __( 'First name', 'atshift-user-profile-fields' ),
			'Last Name'                     => __( 'Last name', 'atshift-user-profile-fields' ),
			'Nickname'                      => __( 'Nickname', 'atshift-user-profile-fields' ),
			'Name Display Format'           => __( 'Name Display Format', 'atshift-user-profile-fields' ),
			'Name Shown on the Site'        => __( 'Name Shown on the Site', 'atshift-user-profile-fields' ),
			'Contact Info'                  => __( 'Contact Info', 'atshift-user-profile-fields' ),
			'Email'                         => __( 'Email', 'atshift-user-profile-fields' ),
			'Website'                       => __( 'Website', 'atshift-user-profile-fields' ),
			'About Yourself'                => __( 'About Yourself', 'atshift-user-profile-fields' ),
			'Biographical Info'             => __( 'Biographical info', 'atshift-user-profile-fields' ),
			'Profile Picture'               => __( 'Profile picture', 'atshift-user-profile-fields' ),
			'Security'                      => __( 'Security', 'atshift-user-profile-fields' ),
			'Password'                      => __( 'Password', 'atshift-user-profile-fields' ),
			'Sessions'                      => __( 'Sessions', 'atshift-user-profile-fields' ),
			'Application Passwords'         => __( 'Application passwords', 'atshift-user-profile-fields' ),
			'Permissions and Notifications' => __( 'Permissions and Notifications', 'atshift-user-profile-fields' ),
			'Role'                          => __( 'Role', 'atshift-user-profile-fields' ),
			'User Notification'             => __( 'User Notification', 'atshift-user-profile-fields' ),
			'Add User / Save'               => __( 'Add User / Save', 'atshift-user-profile-fields' ),
			'個人設定'                      => __( 'Personal Options', 'atshift-user-profile-fields' ),
			'シンタックスハイライト'        => __( 'Syntax highlighting', 'atshift-user-profile-fields' ),
			'管理画面の配色'                => __( 'Admin color scheme', 'atshift-user-profile-fields' ),
			'キーボードショートカット'      => __( 'Keyboard shortcuts', 'atshift-user-profile-fields' ),
			'ツールバー'                    => __( 'Toolbar', 'atshift-user-profile-fields' ),
			'言語'                          => __( 'Language', 'atshift-user-profile-fields' ),
			'ユーザー名'                    => __( 'Username', 'atshift-user-profile-fields' ),
			'名前'                          => __( 'Name', 'atshift-user-profile-fields' ),
			'名'                            => __( 'First name', 'atshift-user-profile-fields' ),
			'姓'                            => __( 'Last name', 'atshift-user-profile-fields' ),
			'ニックネーム'                  => __( 'Nickname', 'atshift-user-profile-fields' ),
			'サイトに表示する名前'          => __( 'Name Shown on the Site', 'atshift-user-profile-fields' ),
			'連絡先情報'                    => __( 'Contact Info', 'atshift-user-profile-fields' ),
			'メールアドレス'                => __( 'Email', 'atshift-user-profile-fields' ),
			'サイト'                        => __( 'Website', 'atshift-user-profile-fields' ),
			'あなたについて'                => __( 'About Yourself', 'atshift-user-profile-fields' ),
			'プロフィール'                  => __( 'About Yourself', 'atshift-user-profile-fields' ),
			'プロフィール情報'              => __( 'Biographical info', 'atshift-user-profile-fields' ),
			'プロフィール写真'              => __( 'Profile picture', 'atshift-user-profile-fields' ),
			'セキュリティ'                  => __( 'Security', 'atshift-user-profile-fields' ),
			'パスワード'                    => __( 'Password', 'atshift-user-profile-fields' ),
			'セッション'                    => __( 'Sessions', 'atshift-user-profile-fields' ),
			'アプリケーションパスワード'    => __( 'Application passwords', 'atshift-user-profile-fields' ),
			'権限・通知'                    => __( 'Permissions and Notifications', 'atshift-user-profile-fields' ),
			'権限グループ'                  => __( 'Role', 'atshift-user-profile-fields' ),
			'ユーザー権限グループ'          => __( 'Role', 'atshift-user-profile-fields' ),
			'メール通知'                    => __( 'User Notification', 'atshift-user-profile-fields' ),
			'ユーザーを追加／保存'          => __( 'Add User / Save', 'atshift-user-profile-fields' ),
			'ユーザー追加・保存'            => __( 'Add User / Save', 'atshift-user-profile-fields' ),
		);
	}

	/**
	 * Return bundled default note translations keyed by stored English text.
	 *
	 * @return array<string, string>
	 */
	private function get_default_description_translation_map() {
		return array(
			'Enter the username required for login using letters, numbers, and the supported symbols (_ . - @). Spaces are not allowed. The username cannot be changed later.' => __( 'Enter the username required for login using half-width letters, numbers, and allowed symbols (_ . - @). Spaces cannot be used. This cannot be changed later.', 'atshift-user-profile-fields' ),
			'Used for password resets and account notifications. It can be changed later and can also be used instead of the username when logging in.' => __( 'This email address is used for password resets and other account notifications. It can be changed at any time and can also be used instead of the user ID when logging in.', 'atshift-user-profile-fields' ),
			'Use a hard-to-guess password of at least 8 characters that combines letters, numbers, and symbols.' => __( 'Use a password that is difficult to guess, combines letters, numbers, and symbols, and is at least 8 characters long.', 'atshift-user-profile-fields' ),
			'Used to add users and save profiles.' => __( 'Used to add users and save profiles.', 'atshift-user-profile-fields' ),
			'ログインに必要なユーザー名を、半角英数字と使用可能な記号（_ . - @）で入力してください。スペースは使用できません。後から変更することはできません。' => __( 'Enter the username required for login using half-width letters, numbers, and allowed symbols (_ . - @). Spaces cannot be used. This cannot be changed later.', 'atshift-user-profile-fields' ),
			'ログインに必要なユーザー名を、半角英数字と使用可能な記号 (_ . - @) で入力してください。スペースは使用できません。ユーザー名は後で変更できません。' => __( 'Enter the username required for login using half-width letters, numbers, and allowed symbols (_ . - @). Spaces cannot be used. This cannot be changed later.', 'atshift-user-profile-fields' ),
			'パスワードの再設定やアカウント通知に使用します。後から変更でき、ログイン時にユーザー名の代わりとしても使用できます。' => __( 'This email address is used for password resets and other account notifications. It can be changed at any time and can also be used instead of the user ID when logging in.', 'atshift-user-profile-fields' ),
			'パスワードリセットとアカウント通知に使用します。メールアドレスはいつでも変更でき、ログイン時にユーザー名の代わりにも使用できます。' => __( 'This email address is used for password resets and other account notifications. It can be changed at any time and can also be used instead of the user ID when logging in.', 'atshift-user-profile-fields' ),
			'推測されにくい、英字・数字・記号を組み合わせた8文字以上のパスワードを使用してください。' => __( 'Use a password that is difficult to guess, combines letters, numbers, and symbols, and is at least 8 characters long.', 'atshift-user-profile-fields' ),
			'ユーザーの追加とプロフィールの保存に使用します。' => __( 'Used to add users and save profiles.', 'atshift-user-profile-fields' ),
		);
	}

	/**
	 * Remove conditional choices whose branch has no fields on the current screen.
	 *
	 * @param array<int, array<string, mixed>> $fields Screen-filtered fields.
	 * @return array<int, array<string, mixed>>
	 */
	private function filter_conditional_choices_for_available_children( $fields ) {
		$available_choices = array();

		foreach ( $fields as $field ) {
			$parent_id        = isset( $field['parent_id'] ) ? (string) $field['parent_id'] : '';
			$conditional_value = isset( $field['conditional_value'] ) ? (string) $field['conditional_value'] : '';

			if ( '' !== $parent_id && '' !== $conditional_value ) {
				$available_choices[ $parent_id ][ $conditional_value ] = true;
			}
		}

		foreach ( $fields as &$field ) {
			if ( ! $this->is_conditional_group( $field ) ) {
				continue;
			}

			$field_id = isset( $field['id'] ) ? (string) $field['id'] : '';
			$choices  = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : array();

			$field['choices'] = array_values(
				array_filter(
					$choices,
					static function ( $choice ) use ( $available_choices, $field_id ) {
						return isset( $available_choices[ $field_id ][ (string) $choice ] );
					}
				)
			);
		}
		unset( $field );

		return $fields;
	}

	/**
	 * Check whether a field should appear on the Add New User screen.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_available_on_new_user_screen( $field ) {
		return ! in_array( $field['type'] ?? '', $this->edit_only_core_field_types(), true );
	}

	/**
	 * Check whether a field should appear on saved user edit screens.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_available_on_edit_user_screen( $field ) {
		return ! in_array( $field['type'] ?? '', $this->new_only_core_field_types(), true );
	}

	/**
	 * Return WordPress native fields that only exist after a user is saved.
	 *
	 * @return array<int, string>
	 */
	private function edit_only_core_field_types() {
		return array(
			'core_visual_editor',
			'core_admin_color',
			'core_syntax_highlighting',
			'core_keyboard_shortcuts',
			'core_toolbar',
			'core_nickname',
			'core_display_name',
			'core_bio',
			'core_profile_picture',
			'core_sessions',
			'core_application_passwords',
		);
	}

	/**
	 * Return WordPress native fields that only exist during user registration.
	 *
	 * @return array<int, string>
	 */
	private function new_only_core_field_types() {
		return array(
			'core_notification',
		);
	}

	/**
	 * Return CSS classes for a profile field row or block.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string              $base Base class name.
	 * @return array<int, string>
	 */
	private function get_profile_field_classes( $field, $base ) {
		$type    = isset( $field['type'] ) ? sanitize_html_class( (string) $field['type'] ) : 'text';
		$classes = array(
			$base,
			'atshift-upf-profile-field-type-' . $type,
		);

		if ( $this->is_profile_feature_field( $field ) ) {
			$classes[] = 'atshift-upf-profile-feature-field';
		}

		return $classes;
	}

	/**
	 * Check whether a field is closer to a profile operation setting than plain profile data.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_profile_feature_field( $field ) {
		return in_array(
			$field['type'] ?? '',
			array(
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
				'core_submit_button',
			),
			true
		);
	}

	/**
	 * Check whether the field is required by settings or by WordPress account rules.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_required_field( $field ) {
		return ! empty( $field['required'] ) || in_array( $field['type'] ?? '', array( 'core_username', 'core_email', 'core_password' ), true );
	}

	/**
	 * Check whether the profile field group is enabled.
	 *
	 * @return bool
	 */
	private function is_field_group_enabled() {
		$settings = Atshift_UPF_Plugin::get_settings();

		return ! empty( $settings['field_group_enabled'] );
	}

	/**
	 * Determine hidden core rows, including fields migrated into this editor.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return array<int, string>
	 */
	private function get_hidden_core_fields( $settings, $screen ) {
		if ( empty( $settings['field_group_enabled'] ) ) {
			return array();
		}

		$hidden      = isset( $settings['hidden_core_fields'] ) ? (array) $settings['hidden_core_fields'] : array();
		$map         = $this->get_core_field_option_map();
		$represented = array();
		$active      = array_fill_keys( $this->get_managed_core_replacement_keys( $screen ), true );

		foreach ( Atshift_UPF_Plugin::get_fields() as $field ) {
			$type = isset( $field['type'] ) ? $field['type'] : '';
			if ( isset( $map[ $type ] ) ) {
				$represented[ $map[ $type ] ] = true;
			}
		}

		$hidden = array_values(
			array_filter(
				array_map( 'sanitize_key', $hidden ),
				static function ( $key ) use ( $represented, $active ) {
					return empty( $represented[ $key ] ) || ! empty( $active[ $key ] );
				}
			)
		);

		foreach ( array_keys( $active ) as $key ) {
			$hidden[] = $key;
		}

		return array_values( array_unique( array_map( 'sanitize_key', $hidden ) ) );
	}

	/**
	 * Return hidden checkbox-driven features that should also be turned off.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @param string               $screen Screen context: new or edit.
	 * @return array<int, string>
	 */
	private function get_disabled_hidden_core_fields( $settings, $screen ) {
		if ( empty( $settings['field_group_enabled'] ) ) {
			return array();
		}

		$disabled = isset( $settings['disabled_hidden_core_fields'] ) ? (array) $settings['disabled_hidden_core_fields'] : array();
		$hidden   = $this->get_hidden_core_fields( $settings, $screen );
		$managed  = $this->get_managed_core_replacement_keys( $screen );

		return array_values(
			array_diff(
				array_intersect(
					array_map( 'sanitize_key', $disabled ),
					$hidden
				),
				$managed
			)
		);
	}

	/**
	 * Turn off selected WordPress profile features when their rows are hidden.
	 *
	 * @param string           $screen Screen context: new or edit.
	 * @param WP_User|stdClass $user User data being saved.
	 * @return void
	 */
	private function apply_disabled_hidden_core_fields( $screen, $user ) {
		global $pagenow;

		$settings = Atshift_UPF_Plugin::get_settings();

		if ( 'profile.php' === $pagenow && empty( $settings['apply_to_own_profile'] ) ) {
			return;
		}

		$disabled = $this->get_disabled_hidden_core_fields( $settings, $screen );

		if ( in_array( 'notification', $disabled, true ) ) {
			unset( $_POST['send_user_notification'] );
		}

		if ( ! is_object( $user ) ) {
			return;
		}

		if ( in_array( 'visual_editor', $disabled, true ) ) {
			$user->rich_editing = 'false';
		}

		if ( in_array( 'syntax_highlighting', $disabled, true ) ) {
			$user->syntax_highlighting = 'false';
		}

		if ( in_array( 'keyboard_shortcuts', $disabled, true ) ) {
			$user->comment_shortcuts = '';
		}

		if ( in_array( 'toolbar', $disabled, true ) ) {
			$user->show_admin_bar_front = 'false';
		}
	}

	/**
	 * Return the default profile option keys with a compatible managed replacement.
	 *
	 * @param string $screen Screen context: new or edit.
	 * @return array<int, string>
	 */
	private function get_managed_core_replacement_keys( $screen ) {
		if ( ! $this->is_field_group_enabled() ) {
			return array();
		}

		$map  = $this->get_core_field_option_map();
		$used = array();

		foreach ( $this->filter_fields_for_screen( Atshift_UPF_Plugin::get_enabled_fields(), $screen ) as $field ) {
			$type = isset( $field['type'] ) ? (string) $field['type'] : '';
			if ( isset( $map[ $type ] ) ) {
				$used[] = $map[ $type ];
			}
		}

		return array_values( array_unique( $used ) );
	}

	/**
	 * Return managed core fields intentionally hidden by role control.
	 *
	 * These fields must not fall back to their native WordPress rows when the
	 * plugin replacement is omitted for the current editor's role.
	 *
	 * @param string $screen Screen context: new or edit.
	 * @return array<int, string>
	 */
	private function get_role_restricted_core_replacement_keys( $screen ) {
		$map        = $this->get_core_field_option_map();
		$restricted = array();

		foreach ( $this->filter_fields_for_screen( Atshift_UPF_Plugin::get_enabled_fields(), $screen ) as $field ) {
			$type = isset( $field['type'] ) ? (string) $field['type'] : '';

			if ( isset( $map[ $type ] ) && ! $this->field_matches_role_control( $field ) ) {
				$restricted[] = $map[ $type ];
			}
		}

		return array_values( array_unique( $restricted ) );
	}

	/**
	 * Return core field types mapped to WordPress profile option keys.
	 *
	 * @return array<string, string>
	 */
	private function get_core_field_option_map() {
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
	 * Check that the WordPress API needed by a core replacement is available.
	 *
	 * The filter lets site code disable one replacement without disabling custom
	 * fields. A disabled replacement falls back to the native WordPress row.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $screen Screen context: new or edit.
	 * @return bool
	 */
	private function is_core_replacement_supported( $field, $screen ) {
		$type      = isset( $field['type'] ) ? (string) $field['type'] : '';
		$supported = true;

		switch ( $type ) {
			case 'core_admin_color':
				global $_wp_admin_css_colors;
				$supported = is_array( $_wp_admin_css_colors ) && ! empty( $_wp_admin_css_colors );
				break;
			case 'core_language':
				$supported = function_exists( 'wp_dropdown_languages' );
				break;
			case 'core_profile_picture':
				$supported = function_exists( 'get_avatar' ) && (bool) get_option( 'show_avatars' );
				break;
			case 'core_role':
				$supported = function_exists( 'wp_roles' ) && ( ! isset( $GLOBALS['pagenow'] ) || 'profile.php' !== $GLOBALS['pagenow'] );
				break;
			case 'core_sessions':
				$supported = 'edit' === $screen && class_exists( 'WP_Session_Tokens' );
				break;
			case 'core_application_passwords':
				$supported = 'edit' === $screen
					&& function_exists( 'wp_is_application_passwords_supported' )
					&& function_exists( 'wp_is_application_passwords_available_for_user' );
				break;
			case 'core_submit_button':
				$supported = function_exists( 'submit_button' );
				break;
		}

		/**
		 * Filters whether a WordPress core profile field can be replaced safely.
		 *
		 * Returning false keeps the native WordPress field visible and removes
		 * the managed replacement from rendering, validation, and saving.
		 *
		 * @param bool                 $supported Whether the replacement is supported.
		 * @param string               $type Field type.
		 * @param string               $screen Screen context: new or edit.
		 * @param array<string, mixed> $field Field definition.
		 */
		return (bool) apply_filters( 'atshift_upf_core_replacement_supported', $supported, $type, $screen, $field );
	}

	/**
	 * Print a marker proving that a managed core replacement was rendered.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return void
	 */
	private function render_core_replacement_attribute( $field ) {
		$map  = $this->get_core_field_option_map();
		$type = isset( $field['type'] ) ? (string) $field['type'] : '';

		if ( isset( $map[ $type ] ) ) {
			echo ' data-atshift-upf-core-replacement="' . esc_attr( $map[ $type ] ) . '"';
		}
	}

	/**
	 * Check whether a field is a structure field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_structure_field( $field ) {
		return in_array( $field['type'] ?? '', array( 'group', 'box', 'conditional', 'accordion' ), true );
	}

	/**
	 * Check whether the current editor's role can use a field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function field_matches_role_control( $field ) {
		$allowed_roles = isset( $field['role_control_roles'] ) && is_array( $field['role_control_roles'] ) ? array_map( 'sanitize_key', $field['role_control_roles'] ) : array();
		if ( empty( $allowed_roles ) ) {
			return true;
		}

		$current_user = wp_get_current_user();
		$user_roles   = $current_user instanceof WP_User ? array_map( 'sanitize_key', (array) $current_user->roles ) : array();

		if ( empty( $user_roles ) ) {
			return false;
		}

		return (bool) array_intersect( $allowed_roles, $user_roles );
	}

	/**
	 * Check whether a field can participate in a profile context.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $context Profile context.
	 * @param string               $screen Screen context: new or edit.
	 * @param WP_User|int|null     $user User object, user ID, or null.
	 * @return bool
	 */
	private function field_matches_profile_context( $field, $context, $screen, $user ) {
		$allowed = $this->field_matches_role_control( $field );

		/**
		 * Filters whether a field is available in a profile rendering, validation,
		 * or save context.
		 *
		 * Returning false prevents the field from participating in that context.
		 *
		 * @param bool                 $allowed Whether the field is available.
		 * @param array<string, mixed> $field Field definition.
		 * @param string               $context Profile context.
		 * @param string               $screen Screen context: new or edit.
		 * @param WP_User|int|null     $user User object, user ID, or null.
		 */
		return (bool) apply_filters( 'atshift_upf_field_matches_profile_context', $allowed, $field, $context, $screen, $user );
	}

	/**
	 * Check whether a field is a horizontal group.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_horizontal_group( $field ) {
		return 'group' === ( $field['type'] ?? '' );
	}

	/**
	 * Check whether a field is a boxed group.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_box_group( $field ) {
		return 'box' === ( $field['type'] ?? '' );
	}

	/**
	 * Check whether a field is a conditional group.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_conditional_group( $field ) {
		return 'conditional' === ( $field['type'] ?? '' );
	}

	/**
	 * Check whether a field is an accordion group.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_accordion_group( $field ) {
		return 'accordion' === ( $field['type'] ?? '' );
	}

	/**
	 * Check whether a field maps to a WordPress default profile field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_core_field( $field ) {
		return 0 === strpos( $field['type'] ?? '', 'core_' );
	}

	/**
	 * Determine whether format validation is enabled for a field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_format_validation_enabled( $field ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		if ( ! in_array( $type, array( 'email', 'url', 'phone' ), true ) ) {
			return false;
		}

		return ! array_key_exists( 'validation_enabled', $field ) || ! empty( $field['validation_enabled'] );
	}

	/**
	 * Determine whether a field value should receive format validation.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function should_validate_format( $field ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		if ( in_array( $type, array( 'core_username', 'core_email', 'core_website', 'number' ), true ) ) {
			return true;
		}

		return $this->is_format_validation_enabled( $field );
	}

	/**
	 * Return a field-type-specific format validation message.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return string
	 */
	private function get_format_error_message( $field ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		if ( 'core_username' === $type ) {
			return __( 'Use only letters, numbers, and these symbols: _ . - @. Spaces cannot be used.', 'atshift-user-profile-fields' );
		}

		if ( in_array( $type, array( 'email', 'core_email' ), true ) ) {
			return __( 'Enter a valid email address.', 'atshift-user-profile-fields' );
		}

		if ( in_array( $type, array( 'url', 'core_website' ), true ) ) {
			return __( 'Enter a valid URL.', 'atshift-user-profile-fields' );
		}

		if ( 'phone' === $type ) {
			return __( 'Enter a valid phone number.', 'atshift-user-profile-fields' );
		}

		if ( 'number' === $type ) {
			return __( 'Enter a valid number.', 'atshift-user-profile-fields' );
		}

		return __( 'Enter a valid value.', 'atshift-user-profile-fields' );
	}

	/**
	 * Validate a field value by field type.
	 *
	 * @param mixed                $value Raw value.
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	private function is_valid_format( $value, $field ) {
		$type  = isset( $field['type'] ) ? $field['type'] : 'text';
		$value = trim( (string) $value );

		if ( 'core_username' === $type ) {
			return false === strpos( $value, ' ' ) && validate_username( $value );
		}

		if ( in_array( $type, array( 'email', 'core_email' ), true ) ) {
			return is_email( $value );
		}

		if ( in_array( $type, array( 'url', 'core_website' ), true ) ) {
			return false !== filter_var( $value, FILTER_VALIDATE_URL );
		}

		if ( 'phone' === $type ) {
			return (bool) preg_match( '/^[0-9+\-\s().]+$/', $value );
		}

		if ( 'number' === $type ) {
			return is_numeric( $value );
		}

		return true;
	}

	/**
	 * Check whether a submitted password meets the plugin's minimum rules.
	 *
	 * @param string $value Password.
	 * @return bool
	 */
	private function is_valid_password( $value ) {
		if ( strlen( $value ) < 8 || preg_match( '/\s/', $value ) ) {
			return false;
		}

		if ( preg_match( '/^[A-Za-z]+$/', $value ) || preg_match( '/^[0-9]+$/', $value ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Build a user meta key.
	 *
	 * @param string $field_key Field key.
	 * @return string
	 */
	private function meta_key( $field_key ) {
		return '_atshift_upf_' . sanitize_key( $field_key );
	}
}
