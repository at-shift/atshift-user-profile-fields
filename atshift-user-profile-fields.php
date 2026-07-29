<?php
/**
 * Plugin Name: atshift User Profile Fields
 * Description: Add custom user profile fields and hide unnecessary default WordPress profile fields.
 * Version: 0.1.100
 * Author: atshift
 * License: GPLv2 or later
 * Text Domain: atshift-user-profile-fields
 * Domain Path: /languages
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ATSHIFT_UPF_VERSION', '0.1.100' );
define( 'ATSHIFT_UPF_FILE', __FILE__ );
define( 'ATSHIFT_UPF_DIR', plugin_dir_path( __FILE__ ) );
define( 'ATSHIFT_UPF_URL', plugin_dir_url( __FILE__ ) );

require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-plugin.php';
require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-admin.php';
require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-profile.php';
require_once ATSHIFT_UPF_DIR . 'includes/class-atshift-upf-tools.php';

Atshift_UPF_Plugin::instance();
