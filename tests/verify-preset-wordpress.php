<?php
/** WordPress integration check for Members + UPF + UPF Pro preset application. */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

define( 'WP_USE_THEMES', false );
define( 'ATSHIFT_UPF_PRO_DEV_LICENSE_ACTIVE', true );
require '/Users/Hellomac/Documents/New project/at-shift Fields/wordpress-local/wordpress/wp-load.php';

$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( empty( $admins ) ) {
	fwrite( STDERR, "No administrator user available.\n" );
	exit( 1 );
}
wp_set_current_user( (int) $admins[0]->ID );

function preset_wp_check( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	echo "PASS: {$message}\n";
}

$option_keys = array(
	'atshift_upf_fields',
	'atshift_upf_settings',
	Atshift_UPF_Presets::HISTORY_OPTION,
	Atshift_UPF_Pro_Organization_Groups::CLASSIFICATION_GROUPS_KEY,
);
$backup = array();
foreach ( $option_keys as $key ) {
	$backup[ $key ] = array( 'exists' => false, 'value' => null );
	$value = get_option( $key, null );
	if ( null !== $value ) {
		$backup[ $key ] = array( 'exists' => true, 'value' => $value );
	}
}
$meta_key = '_atshift_upf_asm_phone';
$had_meta = metadata_exists( 'user', (int) $admins[0]->ID, $meta_key );
$old_meta = get_user_meta( (int) $admins[0]->ID, $meta_key, true );

try {
	update_option(
		'atshift_upf_fields',
		array(
			array(
				'id' => 'existing_first_name', 'key' => 'first_name', 'type' => 'core_first_name',
				'label' => 'Existing First Name', 'parent_id' => '', 'sort_order' => 10,
			),
		),
		false
	);
	delete_option( Atshift_UPF_Presets::HISTORY_OPTION );
	update_option( Atshift_UPF_Pro_Organization_Groups::CLASSIFICATION_GROUPS_KEY, array(), false );
	update_user_meta( (int) $admins[0]->ID, $meta_key, 'sentinel-profile-value' );

	$presets = new Atshift_UPF_Presets( new Atshift_UPF_Tools() );
	$class   = new ReflectionClass( $presets );
	$get_providers = $class->getMethod( 'get_providers' );
	$get_providers->setAccessible( true );
	$resolve = $class->getMethod( 'resolve_provider' );
	$resolve->setAccessible( true );
	$fingerprint = $class->getMethod( 'configuration_fingerprint' );
	$fingerprint->setAccessible( true );
	$apply = $class->getMethod( 'apply_request' );
	$apply->setAccessible( true );

	$providers = $get_providers->invoke( $presets );
	preset_wp_check( isset( $providers['atshift-members.basic-profile'] ), 'Members provider is registered in WordPress' );
	$provider = $providers['atshift-members.basic-profile'];
	$payload  = $resolve->invoke( $presets, $provider );
	$selected = array( 'branch' );
	foreach ( $payload['optional_components'] as $component ) {
		if ( ! empty( $component['default_selected'] ) ) {
			$selected[] = $component['id'];
		}
	}
	$_POST = array(
		'provider_id'              => $provider['id'],
		'configuration_fingerprint'=> $fingerprint->invoke( $presets, $provider, $payload ),
		'components'               => $selected,
	);
	$result = $apply->invoke( $presets );
	preset_wp_check( ! is_wp_error( $result ) && 'applied' === $result['notice'], 'selected additions are applied' );

	$fields = get_option( 'atshift_upf_fields', array() );
	preset_wp_check( 11 === count( $fields ), '11 selected profile fields exist after merge' );
	preset_wp_check( 'existing_first_name' === $fields[0]['id'], 'existing matching field definition and order are preserved' );
	preset_wp_check( 'sentinel-profile-value' === get_user_meta( (int) $admins[0]->ID, $meta_key, true ), 'saved profile values are untouched' );
	$groups = Atshift_UPF_Pro_Organization_Groups::get_classification_groups( 'all' );
	preset_wp_check( in_array( 'asm_basic_branch', wp_list_pluck( $groups, 'id' ), true ), 'selected Pro classification group is created' );
	preset_wp_check( 1 === count( get_option( Atshift_UPF_Presets::HISTORY_OPTION, array() ) ), 'one non-PII application receipt is recorded' );

	$payload = $resolve->invoke( $presets, $provider );
	$_POST['configuration_fingerprint'] = $fingerprint->invoke( $presets, $provider, $payload );
	$result = $apply->invoke( $presets );
	preset_wp_check( ! is_wp_error( $result ) && 'unchanged' === $result['notice'], 'identical reapplication is a no-op' );
	preset_wp_check( 1 === count( get_option( Atshift_UPF_Presets::HISTORY_OPTION, array() ) ), 'no duplicate receipt is added for a no-op' );

	echo "WordPress preset integration verification passed.\n";
} finally {
	foreach ( $backup as $key => $state ) {
		if ( $state['exists'] ) {
			update_option( $key, $state['value'], false );
		} else {
			delete_option( $key );
		}
	}
	if ( $had_meta ) {
		update_user_meta( (int) $admins[0]->ID, $meta_key, $old_meta );
	} else {
		delete_user_meta( (int) $admins[0]->ID, $meta_key );
	}
}
