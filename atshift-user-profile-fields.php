<?php
/**
 * Plugin Name: atshift User Profile Fields
 * Plugin URI: https://plugins.at-shift.net/en/user-profile-fields/
 * Description: Beautiful and practical WordPress user profiles with custom fields and flexible control over default profile items.
 * Version: 1.3.1
 * Author: @shift
 * Author URI: https://plugins.at-shift.net/
 * License: GPLv2 or later
 * Text Domain: atshift-user-profile-fields
 * Domain Path: /languages
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ATSHIFT_UPF_VERSION', '1.3.1' );
define( 'ATSHIFT_UPF_FILE', __FILE__ );
define( 'ATSHIFT_UPF_DIR', plugin_dir_path( __FILE__ ) );
define( 'ATSHIFT_UPF_URL', plugin_dir_url( __FILE__ ) );

if ( ! function_exists( 'atshift_upf_get_user_field' ) ) {
	/**
	 * Get a saved custom user profile field value.
	 *
	 * The field key is the generated/saved field name from the field editor.
	 * Standard WordPress profile fields should be read with WordPress core APIs.
	 *
	 * @since 0.1.104
	 *
	 * @param string $field_key Saved custom field key.
	 * @param int    $user_id Optional user ID. Defaults to the current user.
	 * @return mixed Saved field value, or an empty string when unavailable.
	 */
	function atshift_upf_get_user_field( $field_key, $user_id = 0 ) {
		$field_key = sanitize_key( $field_key );

		if ( '' === $field_key ) {
			return '';
		}

		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return '';
		}

		return get_user_meta( $user_id, '_atshift_upf_' . $field_key, true );
	}
}

if ( ! function_exists( 'atshift_upf_preset_api' ) ) {
	/**
	 * Return the versioned field-set preset integration contract.
	 *
	 * Providers register definitions through the documented filter. Applying a
	 * preset is always an administrator-initiated merge and never changes saved
	 * profile values.
	 *
	 * @since 1.2
	 *
	 * @return array<string, mixed>
	 */
	function atshift_upf_preset_api() {
		$api = array(
			'version'                  => 1,
			'provider_filter'          => 'atshift_upf_preset_providers',
			'extension_handler_filter' => 'atshift_upf_preset_extension_handlers',
			'supports'                 => array(
				'merge'               => true,
				'optional_components' => true,
				'application_history' => true,
				'extensions'          => true,
			),
		);

		/**
		 * Filters the public preset API descriptor.
		 *
		 * Consumers should require version 1 before registering providers.
		 *
		 * @param array<string, mixed> $api API descriptor.
		 */
		return (array) apply_filters( 'atshift_upf_preset_api', $api );
	}
}

if ( ! function_exists( 'atshift_upf_admin_capability' ) ) {
	/**
	 * Return the meta capability for one plugin administration area.
	 *
	 * @since 1.2
	 *
	 * @param string $area Administration area key.
	 * @return string
	 */
	function atshift_upf_admin_capability( $area = 'fields' ) {
		$capability = class_exists( 'Atshift_UPF_Access' ) ? Atshift_UPF_Access::capability( $area ) : 'manage_options';

		/**
		 * Filters the capability used for plugin administration.
		 *
		 * The second argument was added for page-level access. Existing callbacks
		 * accepting only the capability remain compatible.
		 *
		 * @param string $capability Capability.
		 * @param string $area Administration area key.
		 */
		$capability = (string) apply_filters( 'atshift_upf_manage_capability', $capability, $area );

		return (string) apply_filters( 'atshift_upf_admin_capability', $capability, $area );
	}
}

require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-plugin.php';
require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-admin.php';
require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-profile.php';
require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-tools.php';
require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-presets.php';
require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-access.php';

Atshift_UPF_Plugin::instance();
