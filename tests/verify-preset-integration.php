<?php
/** Standalone contract checks for the Members preset integration. */

define( 'ABSPATH', __DIR__ . '/' );
define( 'ATSHIFT_UPF_VERSION', '1.3.1' );
define( 'ATSHIFT_UPF_PRO_VERSION', '1.3' );

$filters = array();
$options = array();

function add_filter( $name, $callback, $priority = 10, $accepted_args = 1 ) {
	global $filters;
	$filters[ $name ][ $priority ][] = array( $callback, $accepted_args );
}
function apply_filters( $name, $value ) {
	global $filters;
	$args = func_get_args();
	array_shift( $args );
	if ( empty( $filters[ $name ] ) ) {
		return $value;
	}
	ksort( $filters[ $name ] );
	foreach ( $filters[ $name ] as $callbacks ) {
		foreach ( $callbacks as $registered ) {
			$args[0] = call_user_func_array( $registered[0], array_slice( $args, 0, $registered[1] ) );
		}
	}
	return $args[0];
}
function atshift_upf_preset_api() { return array( 'version' => 1 ); }
function __( $text ) { return $text; }
function determine_locale() { return 'ja_JP'; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( (string) $value ); }
function sanitize_textarea_field( $value ) { return trim( (string) $value ); }
function get_option( $key, $default = false ) { global $options; return array_key_exists( $key, $options ) ? $options[ $key ] : $default; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
class WP_Error {
	private $code;
	public function __construct( $code ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}

function check_contract( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
	echo "PASS: {$message}\n";
}

require dirname( __DIR__, 2 ) . '/atshift-members/includes/class-profile-preset.php';
require dirname( __DIR__ ) . '/includes/class-atshift-upf-presets.php';

\Atshift\Membership\ProfilePreset::hooks();
$providers = apply_filters( 'atshift_upf_preset_providers', array(), array( 'locale' => 'ja_JP' ) );
check_contract( isset( $providers['atshift-members.basic-profile'] ), 'Members registers its provider through API v1' );

$class    = new ReflectionClass( 'Atshift_UPF_Presets' );
$presets  = $class->newInstanceWithoutConstructor();
$resolve  = $class->getMethod( 'resolve_provider' );
$resolve->setAccessible( true );
$payload = $resolve->invoke( $presets, $providers['atshift-members.basic-profile'] );
check_contract( ! is_wp_error( $payload ), 'Members payload resolves' );
check_contract( empty( $payload['fields'] ), 'all Members fields are independently optional' );
check_contract( 14 === count( $payload['optional_components'] ), '11 profile fields and 3 Pro classification options are exposed' );
$selected_fields = array();
foreach ( $payload['optional_components'] as $component ) {
	if ( ! empty( $component['default_selected'] ) ) {
		$selected_fields = array_merge( $selected_fields, $component['fields'] );
	}
}
check_contract( 11 === count( $selected_fields ), 'all 11 profile fields are selected initially' );
check_contract( 'last_name' === $selected_fields[0]['key'] && 'first_name' === $selected_fields[1]['key'], 'Japanese name order is preserved' );

$merge = $class->getMethod( 'merge_fields' );
$merge->setAccessible( true );
$current = array(
	array( 'id' => 'field_first_name', 'key' => 'first_name', 'type' => 'core_first_name', 'sort_order' => 10 ),
);
$candidates = array();
foreach ( $selected_fields as $field ) {
	$field['parent_id'] = '';
	$candidates[] = $field;
}
$result = $merge->invoke( $presets, $current, $candidates );
check_contract( array( 'field_first_name' ) === $result['unchanged'], 'same key and type reuses the existing field' );
check_contract( 10 === count( $result['added'] ) && empty( $result['conflicts'] ), 'only missing selected fields are appended' );

$conflicting = $candidates;
$conflicting[1]['type'] = 'text';
$result = $merge->invoke( $presets, $current, $conflicting );
check_contract( ! empty( $result['conflicts'] ) && 1 === count( $result['fields'] ), 'same key with a different type cancels the merge' );

$available = $class->getMethod( 'component_is_available' );
$available->setAccessible( true );
$branch = array_values( array_filter( $payload['optional_components'], static function ( $component ) { return 'branch' === $component['id']; } ) )[0];
check_contract( false === $available->invoke( $presets, $branch ), 'Pro component is unavailable without a validated handler' );
add_filter( 'atshift_upf_preset_extension_handlers', static function ( $handlers ) { $handlers['atshift-user-profile-fields-pro'] = static function () { return array(); }; return $handlers; } );
check_contract( true === $available->invoke( $presets, $branch ), 'Pro component is available with its handler' );

echo "Preset integration contract checks passed.\n";
