<?php
/**
 * Plugin Name: atshift User Profile Fields
 * Plugin URI: https://github.com/at-shift/atshift-user-profile-fields
 * Description: Beautiful and practical WordPress user profiles with custom fields and flexible control over default profile items.
 * Version: 0.9.4
 * Author: @shift
 * Author URI: https://github.com/at-shift
 * Update URI: https://github.com/at-shift/atshift-user-profile-fields
 * License: GPLv2 or later
 * Text Domain: atshift-user-profile-fields
 * Domain Path: /languages
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ATSHIFT_UPF_VERSION', '0.9.4' );
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

require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-plugin.php';
require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-github-updater.php';
require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-admin.php';
require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-profile.php';
require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-tools.php';

Atshift_UPF_Plugin::instance();
