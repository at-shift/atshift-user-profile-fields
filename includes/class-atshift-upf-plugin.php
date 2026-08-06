<?php
/**
 * Main plugin bootstrap.
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates admin and profile features.
 */
final class Atshift_UPF_Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Atshift_UPF_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Atshift_UPF_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		if ( class_exists( 'Atshift_UPF_GitHub_Updater' ) ) {
			new Atshift_UPF_GitHub_Updater();
		}

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'announce_loaded' ), 20 );
		add_action( 'init', array( $this, 'boot' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ATSHIFT_UPF_FILE ), array( $this, 'filter_plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'filter_plugin_row_meta' ), 10, 4 );
	}

	/**
	 * Add settings and optional Pro purchase links to the plugin row.
	 *
	 * @param array<int, string> $links Existing plugin action links.
	 * @return array<int, string>
	 */
	public function filter_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'admin.php?page=atshift-user-profile-fields' ) ),
			esc_html__( 'Settings', 'atshift-user-profile-fields' )
		);

		array_unshift( $links, $settings_link );

		if ( $this->is_pro_installed() ) {
			return $links;
		}

		$price_url = 0 === strpos( determine_locale(), 'ja' ) ? 'https://upf.at-shift.net/price/' : 'https://upf.at-shift.net/en/price/';
		$pro_link  = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer"><strong>%2$s</strong></a>',
			esc_url( $price_url ),
			esc_html__( 'Upgrade to Pro', 'atshift-user-profile-fields' )
		);

		array_splice( $links, 1, 0, array( $pro_link ) );

		return $links;
	}

	/**
	 * Determine whether the Pro add-on is installed, including when inactive.
	 *
	 * @return bool
	 */
	public function is_pro_installed() {
		if ( defined( 'ATSHIFT_UPF_PRO_FILE' ) ) {
			return true;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( array_keys( get_plugins() ) as $plugin_file ) {
			if ( 'atshift-user-profile-fields-pro.php' === basename( $plugin_file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Replace the generic plugin-site link and add the official usage guide.
	 *
	 * @param array<int, string>   $links       Existing plugin metadata links.
	 * @param string               $plugin_file Plugin basename.
	 * @param array<string, mixed> $plugin_data Parsed plugin headers.
	 * @param string               $status      Plugin status.
	 * @return array<int, string>
	 */
	public function filter_plugin_row_meta( $links, $plugin_file, $plugin_data, $status ) {
		unset( $plugin_data, $status );

		if ( plugin_basename( ATSHIFT_UPF_FILE ) !== $plugin_file ) {
			return $links;
		}

		$details_url  = 'https://github.com/at-shift/atshift-user-profile-fields';
		$guide_url    = 0 === strpos( determine_locale(), 'ja' ) ? 'https://upf.at-shift.net/guide/' : 'https://upf.at-shift.net/en/guide/';
		$details_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $details_url ),
			esc_html__( 'View details', 'atshift-user-profile-fields' )
		);
		$replaced     = false;

		foreach ( $links as $index => $link ) {
			if ( false !== strpos( $link, esc_url( $details_url ) ) ) {
				$links[ $index ] = $details_link;
				$replaced        = true;
				break;
			}
		}

		if ( ! $replaced ) {
			$links[] = $details_link;
		}

		$links[] = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $guide_url ),
			esc_html__( 'Usage guide', 'atshift-user-profile-fields' )
		);

		return $links;
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'atshift-user-profile-fields',
			false,
			dirname( plugin_basename( ATSHIFT_UPF_FILE ) ) . '/languages'
		);
	}

	/**
	 * Announce that the base plugin is available for add-ons.
	 *
	 * @return void
	 */
	public function announce_loaded() {
		/**
		 * Fires after WordPress has loaded all active plugins and the base plugin
		 * is ready for add-ons to register their integrations.
		 *
		 * @param Atshift_UPF_Plugin $plugin Base plugin instance.
		 */
		do_action( 'atshift_upf_loaded', $this );
	}

	/**
	 * Start feature classes.
	 *
	 * @return void
	 */
	public function boot() {
		if ( is_admin() ) {
			new Atshift_UPF_Admin();
			new Atshift_UPF_Tools();

			if ( ! self::is_safe_mode() ) {
				new Atshift_UPF_Profile();
			}
		}
	}

	/**
	 * Check whether emergency safe mode was enabled in wp-config.php.
	 *
	 * @return bool
	 */
	public static function is_safe_mode() {
		return defined( 'ATSHIFT_UPF_SAFE_MODE' ) && ATSHIFT_UPF_SAFE_MODE;
	}

	/**
	 * Return all field definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_fields() {
		$fields = get_option( 'atshift_upf_fields', array() );

		if ( ! is_array( $fields ) ) {
			$fields = array();
		}

		if ( ! empty( $fields ) ) {
			usort(
				$fields,
				static function ( $a, $b ) {
					$a_order = isset( $a['sort_order'] ) ? (int) $a['sort_order'] : 0;
					$b_order = isset( $b['sort_order'] ) ? (int) $b['sort_order'] : 0;

					return $a_order <=> $b_order;
				}
			);
		}

		/**
		 * Filters field definitions returned by the base plugin.
		 *
		 * Add-ons can use this to attach runtime-only metadata. Persistent field
		 * settings should be saved through the admin/import field filters.
		 *
		 * @param array<int, array<string, mixed>> $fields Field definitions.
		 */
		return apply_filters( 'atshift_upf_get_fields', $fields );
	}

	/**
	 * Return enabled field definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_enabled_fields() {
		/**
		 * Filters enabled field definitions returned for profile handling.
		 *
		 * @param array<int, array<string, mixed>> $fields Field definitions.
		 */
		return apply_filters( 'atshift_upf_get_enabled_fields', self::get_fields() );
	}

	/**
	 * Return plugin settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings() {
		$defaults = array(
			'hidden_core_fields'          => array(),
			'disabled_hidden_core_fields' => array(),
			'apply_to_own_profile'        => true,
			'editor_layout'               => 'two',
			'show_extras'                 => true,
			'field_group_enabled'         => true,
		);

		$settings = get_option( 'atshift_upf_settings', array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings = wp_parse_args( $settings, $defaults );

		/**
		 * Filters plugin settings returned by the base plugin.
		 *
		 * @param array<string, mixed> $settings Plugin settings.
		 * @param array<string, mixed> $defaults Default settings.
		 */
		return apply_filters( 'atshift_upf_get_settings', $settings, $defaults );
	}

	/**
	 * Return default states for checkbox-driven WordPress profile fields.
	 *
	 * @return array<string, bool>
	 */
	public static function get_initial_state_defaults() {
		$defaults = array(
			'core_visual_editor'       => true,
			'core_syntax_highlighting' => true,
			'core_keyboard_shortcuts'  => false,
			'core_toolbar'             => true,
			'core_notification'        => true,
		);

		/**
		 * Filters initial states used on the Add New User screen.
		 *
		 * @param array<string, bool> $defaults Initial state keyed by field type.
		 */
		return (array) apply_filters( 'atshift_upf_initial_state_defaults', $defaults );
	}

	/**
	 * Check whether a field type supports a configurable initial state.
	 *
	 * @param string $type Field type.
	 * @return bool
	 */
	public static function supports_initial_state( $type ) {
		return array_key_exists( sanitize_key( $type ), self::get_initial_state_defaults() );
	}

	/**
	 * Return a field's configured initial state with a compatible fallback.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	public static function get_field_initial_enabled( $field ) {
		$type     = sanitize_key( $field['type'] ?? '' );
		$defaults = self::get_initial_state_defaults();

		if ( ! array_key_exists( $type, $defaults ) ) {
			return false;
		}

		if ( array_key_exists( 'initial_enabled', $field ) && null !== $field['initial_enabled'] ) {
			return ! empty( $field['initial_enabled'] );
		}

		return ! empty( $defaults[ $type ] );
	}

	/**
	 * Return CSS variables based on the current WordPress admin color scheme.
	 *
	 * @param string $selector CSS selector that should receive the variables.
	 * @return string
	 */
	public static function get_admin_color_scheme_css( $selector ) {
		global $_wp_admin_css_colors;

		$scheme_key = get_user_option( 'admin_color' );
		$scheme     = '';
		$colors     = array();
		$accent     = '#2271b1';
		$accent_alt = '#72aee6';
		$chrome     = '#1d2327';
		$chrome_alt = '#2c3338';

		if ( is_string( $scheme_key ) && isset( $_wp_admin_css_colors[ $scheme_key ] ) ) {
			$scheme = $_wp_admin_css_colors[ $scheme_key ];
		} elseif ( isset( $_wp_admin_css_colors['fresh'] ) ) {
			$scheme = $_wp_admin_css_colors['fresh'];
		}

		if ( is_object( $scheme ) && ! empty( $scheme->colors ) && is_array( $scheme->colors ) ) {
			$colors = array_values( $scheme->colors );
		}

		if ( ! empty( $colors[0] ) ) {
			$chrome = $colors[0];
		}

		if ( ! empty( $colors[1] ) ) {
			$chrome_alt = $colors[1];
		}

		if ( ! empty( $colors[2] ) ) {
			$accent = $colors[2];
		}

		if ( ! empty( $colors[3] ) ) {
			$accent_alt = $colors[3];
		}

		return sprintf(
			"%s{\n--atshift-upf-accent:%s;\n--atshift-upf-accent-alt:%s;\n--atshift-upf-accent-dark:%s;\n--atshift-upf-chrome:%s;\n--atshift-upf-chrome-alt:%s;\n}\n",
			$selector,
			esc_attr( $accent ),
			esc_attr( $accent_alt ),
			esc_attr( $chrome ),
			esc_attr( $chrome ),
			esc_attr( $chrome_alt )
		);
	}
}
