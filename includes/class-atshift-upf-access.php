<?php
/**
 * Role-based access to plugin administration areas.
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps the plugin hidden by default and permits explicit page-level grants.
 */
final class Atshift_UPF_Access {
	const OPTION_KEY = 'atshift_upf_access_roles';
	const PAGE_SLUG  = 'atshift-user-profile-fields-access';
	const CAP_PREFIX = 'atshift_upf_access_';

	/** Register access hooks. */
	public function __construct() {
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
		add_action( 'admin_menu', array( $this, 'add_menu' ), 90 );
		add_action( 'admin_init', array( $this, 'handle_save' ) );
	}

	/**
	 * Return the meta capability for an administration area.
	 *
	 * @param string $area Area key.
	 * @return string
	 */
	public static function capability( $area ) {
		$area = sanitize_key( $area );
		return self::CAP_PREFIX . ( '' === $area ? 'menu' : $area );
	}

	/**
	 * Register the site-administrator-only access settings page.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			'atshift-user-profile-fields',
			__( 'Access Permissions', 'atshift-user-profile-fields' ),
			__( 'Access Permissions', 'atshift-user-profile-fields' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Map plugin meta capabilities to administrator access or explicit roles.
	 *
	 * @param array<int, string> $caps Required primitive capabilities.
	 * @param string             $cap Requested capability.
	 * @param int                $user_id User ID.
	 * @param array<int, mixed>  $args Capability arguments.
	 * @return array<int, string>
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		unset( $args );
		if ( 0 !== strpos( $cap, self::CAP_PREFIX ) ) {
			return $caps;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array( 'do_not_allow' );
		}
		if ( user_can( $user, 'manage_options' ) ) {
			return array( 'exist' );
		}

		$area    = substr( $cap, strlen( self::CAP_PREFIX ) );
		$grants  = self::get_grants();
		$allowed = array();
		if ( 'menu' === $area ) {
			foreach ( $grants as $roles ) {
				$allowed = array_merge( $allowed, $roles );
			}
		} else {
			$allowed = isset( $grants[ $area ] ) ? $grants[ $area ] : array();
		}

		return array_intersect( (array) $user->roles, $allowed ) ? array( 'exist' ) : array( 'do_not_allow' );
	}

	/** Render the role-by-area matrix. */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage access permissions.', 'atshift-user-profile-fields' ) );
		}

		$areas  = self::get_areas();
		$roles  = wp_roles()->roles;
		$grants = self::get_grants();
		?>
		<div class="wrap atshift-upf">
			<h1><?php esc_html_e( 'Access Permissions', 'atshift-user-profile-fields' ); ?></h1>
			<p><?php esc_html_e( 'Site administrators always have access. Other roles see only the plugin pages explicitly enabled below.', 'atshift-user-profile-fields' ); ?></p>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Access permissions saved.', 'atshift-user-profile-fields' ); ?></p></div>
			<?php endif; ?>
			<form method="post">
				<?php wp_nonce_field( 'atshift_upf_save_access' ); ?>
				<input type="hidden" name="atshift_upf_access_action" value="save">
				<table class="widefat striped" style="max-width: 1100px;">
					<thead><tr><th><?php esc_html_e( 'Role', 'atshift-user-profile-fields' ); ?></th><?php foreach ( $areas as $area ) : ?><th><?php echo esc_html( $area['label'] ); ?></th><?php endforeach; ?></tr></thead>
					<tbody>
					<?php foreach ( $roles as $role_key => $role ) : ?>
						<?php if ( ! empty( $role['capabilities']['manage_options'] ) ) { continue; } ?>
						<tr>
							<th scope="row"><?php echo esc_html( translate_user_role( $role['name'] ) ); ?></th>
							<?php foreach ( $areas as $area_key => $area ) : ?>
								<td><label><input type="checkbox" name="access[<?php echo esc_attr( $area_key ); ?>][]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $grants[ $area_key ] ?? array(), true ) ); ?>> <span class="screen-reader-text"><?php echo esc_html( $area['label'] ); ?></span></label></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button( __( 'Save Access Permissions', 'atshift-user-profile-fields' ) ); ?>
			</form>
		</div>
		<?php
	}

	/** Save explicit role grants. */
	public function handle_save() {
		if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) || empty( $_POST['atshift_upf_access_action'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage access permissions.', 'atshift-user-profile-fields' ) );
		}
		check_admin_referer( 'atshift_upf_save_access' );

		$submitted = isset( $_POST['access'] ) && is_array( $_POST['access'] ) ? wp_unslash( $_POST['access'] ) : array();
		$areas     = self::get_areas();
		$roles     = wp_roles()->roles;
		$saved     = array();
		foreach ( $areas as $area_key => $area ) {
			unset( $area );
			$selected = isset( $submitted[ $area_key ] ) && is_array( $submitted[ $area_key ] ) ? array_map( 'sanitize_key', $submitted[ $area_key ] ) : array();
			$saved[ $area_key ] = array_values(
				array_filter(
					array_unique( $selected ),
					static function ( $role_key ) use ( $roles ) {
						return isset( $roles[ $role_key ] ) && empty( $roles[ $role_key ]['capabilities']['manage_options'] );
					}
				)
			);
		}
		update_option( self::OPTION_KEY, $saved, false );
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'updated' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render a useful entry page when a delegated role lacks field access.
	 *
	 * @return void
	 */
	public static function render_landing() {
		$links = array();
		foreach ( self::get_areas() as $area_key => $area ) {
			if ( current_user_can( self::capability( $area_key ) ) && ! empty( $area['url'] ) ) {
				$links[] = '<li><a href="' . esc_url( $area['url'] ) . '">' . esc_html( $area['label'] ) . '</a></li>';
			}
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'atshift User Profile Fields', 'atshift-user-profile-fields' ) . '</h1><ul class="ul-disc">' . wp_kses_post( implode( '', $links ) ) . '</ul></div>';
	}

	/** @return array<string, array<string, string>> */
	public static function get_areas() {
		$areas = array(
			'fields'   => array( 'label' => __( 'Field Management', 'atshift-user-profile-fields' ), 'url' => admin_url( 'admin.php?page=atshift-user-profile-fields' ) ),
			'display'  => array( 'label' => __( 'Display Settings', 'atshift-user-profile-fields' ), 'url' => admin_url( 'admin.php?page=atshift-user-profile-fields-extras' ) ),
			'presets'  => array( 'label' => __( 'Field Set Presets', 'atshift-user-profile-fields' ), 'url' => admin_url( 'admin.php?page=atshift-user-profile-fields-presets' ) ),
			'tools'    => array( 'label' => __( 'Import, Export, and Data Deletion', 'atshift-user-profile-fields' ), 'url' => admin_url( 'tools.php?page=atshift-user-profile-fields-tools' ) ),
		);
		return (array) apply_filters( 'atshift_upf_access_areas', $areas );
	}

	/** @return array<string, array<int, string>> */
	public static function get_grants() {
		$stored = get_option( self::OPTION_KEY, array() );
		return is_array( $stored ) ? $stored : array();
	}
}
