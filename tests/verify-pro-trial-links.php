<?php
/** Standalone checks for the Pro trial links shown on the Plugins screen. */

define( 'ABSPATH', __DIR__ . '/' );
define( 'ATSHIFT_UPF_FILE', dirname( __DIR__ ) . '/atshift-user-profile-fields.php' );
define( 'ATSHIFT_UPF_VERSION', '1.3' );

function add_action() {}
function add_filter() {}
function plugin_basename( $file ) { return basename( $file ); }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
function home_url() { return 'https://example.test'; }
function determine_locale() { return $GLOBALS['upf_test_locale']; }
function esc_url( $url ) { return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $text ) { return 'Try Pro Add-on for 7 Days / Purchase' === $text ? 'Proアドオンを7日間試す・購入' : $text; }
function __( $text ) { return $text; }
function add_query_arg( $key, $value, $url ) { return $url . '?' . rawurlencode( $key ) . '=' . rawurlencode( $value ); }
function get_plugins() { return $GLOBALS['upf_test_plugins']; }
class Atshift_UPF_Access {}

function upf_trial_link_check( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
	echo "PASS: {$message}\n";
}

$GLOBALS['upf_test_plugins'] = array();
$GLOBALS['upf_test_locale']  = 'ja_JP';
require dirname( __DIR__ ) . '/includes/class-atshift-upf-plugin.php';
$plugin = Atshift_UPF_Plugin::instance();

$actions = $plugin->filter_plugin_action_links( array( '<a href="#">Deactivate</a>' ) );
upf_trial_link_check( false !== strpos( $actions[1], 'Proアドオンを7日間試す・購入' ), 'Plugins action link uses the trial-first label' );
upf_trial_link_check( false !== strpos( $actions[1], 'https://plugins.at-shift.net/pro/' ), 'Plugins action link opens the Japanese Pro page' );
upf_trial_link_check( false !== strpos( $actions[1], 'site_url=https%3A%2F%2Fexample.test' ), 'Plugins action link includes the current site URL' );

$meta = $plugin->filter_plugin_row_meta(
	array(),
	plugin_basename( ATSHIFT_UPF_FILE ),
	array( 'Version' => ATSHIFT_UPF_VERSION ),
	'active'
);
upf_trial_link_check( false !== strpos( end( $meta ), 'Proアドオンを7日間試す・購入' ), 'Plugin metadata uses the same trial-first label' );
upf_trial_link_check( false !== strpos( end( $meta ), 'https://plugins.at-shift.net/pro/' ), 'Plugin metadata opens the Japanese Pro page' );

$GLOBALS['upf_test_locale'] = 'en_US';
$english_actions = $plugin->filter_plugin_action_links( array( '<a href="#">Deactivate</a>' ) );
upf_trial_link_check( false !== strpos( $english_actions[1], 'https://plugins.at-shift.net/en/pro/' ), 'Plugins action link opens the English Pro page' );

$english_meta = $plugin->filter_plugin_row_meta(
	array(),
	plugin_basename( ATSHIFT_UPF_FILE ),
	array( 'Version' => ATSHIFT_UPF_VERSION ),
	'active'
);
upf_trial_link_check( false !== strpos( end( $english_meta ), 'https://plugins.at-shift.net/en/pro/' ), 'Plugin metadata opens the English Pro page' );

$GLOBALS['upf_test_plugins'] = array( 'atshift-user-profile-fields-pro/atshift-user-profile-fields-pro.php' => array() );
$installed_actions = $plugin->filter_plugin_action_links( array( '<a href="#">Deactivate</a>' ) );
upf_trial_link_check( 2 === count( $installed_actions ), 'Trial link is hidden when the Pro add-on is installed' );

echo "Pro trial link checks passed.\n";
