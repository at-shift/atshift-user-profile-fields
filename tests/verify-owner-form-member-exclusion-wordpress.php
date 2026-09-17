<?php
/** WordPress integration check for excluding fields from member registration and account forms. */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

define( 'WP_USE_THEMES', false );
require '/Users/Hellomac/Documents/New project/at-shift Fields/wordpress-local/wordpress/wp-load.php';

$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( empty( $admins ) ) {
	fwrite( STDERR, "No administrator user available.\n" );
	exit( 1 );
}
wp_set_current_user( (int) $admins[0]->ID );

function member_exclusion_check( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	echo "PASS: {$message}\n";
}

/** Run the private bulk field save and stop at its redirect instead of exiting. */
function member_exclusion_save_rows( $rows ) {
	$admin  = new Atshift_UPF_Admin();
	$method = new ReflectionMethod( $admin, 'save_fields' );
	$method->setAccessible( true );

	$nonce              = wp_create_nonce( 'atshift_upf_save_fields' );
	$_POST              = array( 'fields' => wp_slash( $rows ), '_wpnonce' => $nonce );
	$_REQUEST['_wpnonce'] = $nonce;
	$stop               = static function () {
		throw new LogicException( 'redirect' );
	};

	add_filter( 'wp_redirect', $stop );
	try {
		$method->invoke( $admin );
	} catch ( LogicException $redirect ) {
		unset( $redirect );
	} finally {
		remove_filter( 'wp_redirect', $stop );
		$_POST    = array();
		$_REQUEST = array();
	}
}

/** Return a stored field definition by key. */
function member_exclusion_stored( $key ) {
	foreach ( Atshift_UPF_Plugin::get_fields() as $field ) {
		if ( $key === ( $field['key'] ?? '' ) ) {
			return $field;
		}
	}
	return null;
}

$option_keys = array( 'atshift_upf_fields', 'atshift_upf_settings' );
$backup      = array();
foreach ( $option_keys as $key ) {
	$value          = get_option( $key, null );
	$backup[ $key ] = array( 'exists' => null !== $value, 'value' => $value );
}

$suffix  = strtolower( wp_generate_password( 6, false, false ) );
$visible = 'member_visible_' . $suffix;
$hidden  = 'member_hidden_' . $suffix;
$group   = 'member_group_' . $suffix;
$child   = 'member_child_' . $suffix;
$exit_status = 0;
$consumer_on  = static function () {
	return true;
};
$consumer_off = static function () {
	return false;
};

try {
	$settings                        = Atshift_UPF_Plugin::get_settings();
	$settings['field_group_enabled'] = true;
	update_option( 'atshift_upf_settings', $settings, false );
	update_option(
		'atshift_upf_fields',
		array(
			array( 'id' => $visible, 'key' => $visible, 'label' => 'Visible', 'type' => 'text', 'sort_order' => 10 ),
			array( 'id' => $hidden, 'key' => $hidden, 'label' => 'Hidden', 'type' => 'text', 'admin_only' => true, 'sort_order' => 20 ),
			array( 'id' => $group, 'key' => $group, 'label' => 'Hidden Group', 'type' => 'box', 'admin_only' => true, 'sort_order' => 30 ),
			array( 'id' => $child, 'key' => $child, 'label' => 'Child', 'type' => 'text', 'parent_id' => $group, 'sort_order' => 40 ),
		),
		false
	);

	$api        = apply_filters( 'atshift_upf_public_profile_api', null );
	$owner_form = $api['owner_form'];
	$fields     = call_user_func( $owner_form['fields'], null );
	member_exclusion_check( isset( $fields[ $visible ] ), 'Fields without the exclusion stay in member forms.' );
	member_exclusion_check( ! isset( $fields[ $hidden ] ), 'An excluded field is removed from member forms.' );
	member_exclusion_check( ! isset( $fields[ $group ] ) && ! isset( $fields[ $child ] ), 'Children of an excluded group are removed from member forms.' );
	member_exclusion_check(
		is_wp_error( call_user_func( $owner_form['validate'], null, array( $visible => 'ok', $hidden => 'forged' ) ) ),
		'A forged submission for an excluded field is rejected.'
	);

	$statuses = array();
	foreach ( call_user_func( $api['settings_fields'] ) as $row ) {
		$statuses[ $row['label'] ] = $row['status'];
	}
	member_exclusion_check( 'excluded' === ( $statuses['Hidden'] ?? '' ), 'Members settings report an excluded field as excluded.' );

	$admin  = new Atshift_UPF_Admin();
	$render = new ReflectionMethod( $admin, 'render_field_form' );
	$render->setAccessible( true );
	add_filter( 'atshift_upf_owner_form_consumer_active', $consumer_on );
	ob_start();
	$render->invoke( $admin, member_exclusion_stored( $hidden ), 'fields[0]', false );
	$with_consumer = ob_get_clean();
	remove_filter( 'atshift_upf_owner_form_consumer_active', $consumer_on );
	add_filter( 'atshift_upf_owner_form_consumer_active', $consumer_off );
	ob_start();
	$render->invoke( $admin, member_exclusion_stored( $hidden ), 'fields[0]', false );
	$without_consumer = ob_get_clean();
	remove_filter( 'atshift_upf_owner_form_consumer_active', $consumer_off );
	member_exclusion_check( 1 === preg_match( '/name="fields\[0\]\[admin_only\]" value="1"\s+checked=/', $with_consumer ), 'The setting is shown and checked while a member-form integration is active.' );
	member_exclusion_check( false === strpos( $without_consumer, 'fields[0][admin_only]' ), 'The setting is hidden without a member-form integration.' );

	$rows = array();
	foreach ( Atshift_UPF_Plugin::get_fields() as $index => $field ) {
		$rows[ $index ] = array(
			'field_id'  => $field['id'],
			'client_id' => $field['id'],
			'field_key' => $field['key'],
			'label'     => $field['label'],
			'type'      => $field['type'],
			'parent_id' => $field['parent_id'] ?? '',
		);
	}

	member_exclusion_save_rows( $rows );
	member_exclusion_check( ! empty( member_exclusion_stored( $hidden )['admin_only'] ), 'Saving without the rendered setting keeps a stored exclusion.' );

	$marked = $rows;
	foreach ( $marked as $index => $row ) {
		$marked[ $index ]['owner_form_setting'] = '1';
		if ( $visible === $row['field_key'] ) {
			$marked[ $index ]['admin_only'] = '1';
		}
	}
	member_exclusion_save_rows( $marked );
	member_exclusion_check( ! empty( member_exclusion_stored( $visible )['admin_only'] ), 'Checking the setting saves an exclusion.' );
	member_exclusion_check( empty( member_exclusion_stored( $hidden )['admin_only'] ), 'Unchecking the rendered setting clears an exclusion.' );

	$tools  = new Atshift_UPF_Tools();
	$import = new ReflectionMethod( $tools, 'sanitize_import_fields' );
	$import->setAccessible( true );
	$imported = $import->invoke(
		$tools,
		array(
			array( 'id' => $hidden, 'key' => $hidden, 'label' => 'Hidden', 'type' => 'text', 'admin_only' => true ),
		)
	);
	member_exclusion_check( is_array( $imported ) && ! empty( $imported[0]['admin_only'] ), 'Field set import keeps the exclusion.' );

	echo "Member form exclusion verification passed.\n";
} catch ( Throwable $error ) {
	fwrite( STDERR, 'Member form exclusion verification failed: ' . $error->getMessage() . "\n" );
	// Exiting here would skip the finally cleanup.
	$exit_status = 1;
} finally {
	foreach ( $backup as $key => $state ) {
		if ( $state['exists'] ) {
			update_option( $key, $state['value'], false );
		} else {
			delete_option( $key );
		}
	}
}

exit( $exit_status );
