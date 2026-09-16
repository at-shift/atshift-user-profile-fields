<?php
/** Standalone checks for role-by-page administration access. */

define( 'ABSPATH', __DIR__ . '/' );

$access_options = array(
	'atshift_upf_access_roles' => array(
		'fields'  => array( 'editor' ),
		'display' => array( 'author' ),
	),
);
$access_users = array(
	1 => (object) array( 'roles' => array( 'administrator' ), 'allcaps' => array( 'manage_options' => true ) ),
	2 => (object) array( 'roles' => array( 'editor' ), 'allcaps' => array( 'read' => true ) ),
	3 => (object) array( 'roles' => array( 'subscriber' ), 'allcaps' => array( 'read' => true ) ),
);

function add_filter() {}
function add_action() {}
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function get_userdata( $id ) { global $access_users; return $access_users[ $id ] ?? false; }
function user_can( $user, $capability ) { return ! empty( $user->allcaps[ $capability ] ); }
function get_option( $key, $default = false ) { global $access_options; return array_key_exists( $key, $access_options ) ? $access_options[ $key ] : $default; }

function check_access( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
	echo "PASS: {$message}\n";
}

require dirname( __DIR__ ) . '/includes/class-atshift-upf-access.php';
$access = new Atshift_UPF_Access();

check_access( array( 'exist' ) === $access->map_meta_cap( array(), Atshift_UPF_Access::capability( 'display' ), 1, array() ), 'site administrators always have access' );
check_access( array( 'exist' ) === $access->map_meta_cap( array(), Atshift_UPF_Access::capability( 'fields' ), 2, array() ), 'an explicitly granted role can access its page' );
check_access( array( 'do_not_allow' ) === $access->map_meta_cap( array(), Atshift_UPF_Access::capability( 'display' ), 2, array() ), 'a role cannot access an ungranted page' );
check_access( array( 'exist' ) === $access->map_meta_cap( array(), Atshift_UPF_Access::capability( 'menu' ), 2, array() ), 'a role with any grant sees the plugin entry' );
check_access( array( 'do_not_allow' ) === $access->map_meta_cap( array(), Atshift_UPF_Access::capability( 'menu' ), 3, array() ), 'a role without grants does not see the plugin entry' );
check_access( array( 'read' ) === $access->map_meta_cap( array( 'read' ), 'read', 3, array() ), 'unrelated WordPress capabilities remain unchanged' );

echo "Administration access checks passed.\n";
