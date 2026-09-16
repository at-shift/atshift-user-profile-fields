<?php
/**
 * Uninstall handler.
 *
 * The basic version intentionally keeps user meta to prevent accidental data loss.
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'atshift_upf_fields' );
delete_option( 'atshift_upf_settings' );
delete_option( 'atshift_upf_access_roles' );
delete_option( 'atshift_upf_preset_applications' );
delete_option( 'atshift_upf_preset_apply_lock' );
