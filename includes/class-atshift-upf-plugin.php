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
		new Atshift_UPF_GitHub_Updater();

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'boot' ) );
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
			return array();
		}

		usort(
			$fields,
			static function ( $a, $b ) {
				$a_order = isset( $a['sort_order'] ) ? (int) $a['sort_order'] : 0;
				$b_order = isset( $b['sort_order'] ) ? (int) $b['sort_order'] : 0;

				return $a_order <=> $b_order;
			}
		);

		return $fields;
	}

	/**
	 * Return enabled field definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_enabled_fields() {
		return self::get_fields();
	}

	/**
	 * Return plugin settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings() {
		$defaults = array(
			'hidden_core_fields'   => array(),
			'apply_to_own_profile' => true,
			'editor_layout'        => 'two',
			'show_extras'          => true,
			'field_group_enabled'  => true,
		);

		$settings = get_option( 'atshift_upf_settings', array() );

		if ( ! is_array( $settings ) ) {
			return $defaults;
		}

		return wp_parse_args( $settings, $defaults );
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
