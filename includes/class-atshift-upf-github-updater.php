<?php
/**
 * GitHub release update integration.
 *
 * @package AtshiftUserProfileFields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supplies WordPress with updates published as GitHub release assets.
 */
final class Atshift_UPF_GitHub_Updater {
	/**
	 * GitHub repository URL used by the Update URI plugin header.
	 */
	const UPDATE_URI = 'https://github.com/at-shift/atshift-user-profile-fields';

	/**
	 * Latest public GitHub release endpoint.
	 */
	const RELEASE_API_URL = 'https://api.github.com/repos/at-shift/atshift-user-profile-fields/releases/latest';

	/**
	 * Cached release transient key.
	 */
	const CACHE_KEY = 'atshift_upf_github_release';

	/**
	 * Register update hooks.
	 */
	public function __construct() {
		add_filter( 'update_plugins_github.com', array( $this, 'filter_update' ), 10, 4 );
	}

	/**
	 * Return an update when a newer packaged GitHub release is available.
	 *
	 * @param array|false              $update Existing update data.
	 * @param array<string, mixed>     $plugin_data Plugin header data.
	 * @param string                   $plugin_file Plugin basename.
	 * @param array<int, string>       $locales Installed locales.
	 * @return array<string, mixed>|false
	 */
	public function filter_update( $update, $plugin_data, $plugin_file, $locales ) {
		unset( $plugin_data, $locales );

		if ( plugin_basename( ATSHIFT_UPF_FILE ) !== $plugin_file ) {
			return $update;
		}

		$release = $this->get_latest_release();
		if ( empty( $release ) ) {
			return false;
		}

		$version = $this->get_release_version( $release );
		$package = $this->get_release_package( $release, $version );

		if ( '' === $version || '' === $package || ! version_compare( ATSHIFT_UPF_VERSION, $version, '<' ) ) {
			return false;
		}

		return array(
			'id'           => self::UPDATE_URI,
			'slug'         => 'atshift-user-profile-fields',
			'version'      => $version,
			'url'          => isset( $release['html_url'] ) ? esc_url_raw( $release['html_url'] ) : self::UPDATE_URI . '/releases',
			'package'      => $package,
			'tested'       => '7.1',
			'requires_php' => '7.4',
		);
	}

	/**
	 * Fetch and cache the latest public GitHub release.
	 *
	 * @return array<string, mixed>
	 */
	private function get_latest_release() {
		$cached = get_site_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return ! empty( $cached['release'] ) && is_array( $cached['release'] ) ? $cached['release'] : array();
		}

		$response = wp_safe_remote_get(
			self::RELEASE_API_URL,
			array(
				'timeout' => 8,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'atshift-user-profile-fields/' . ATSHIFT_UPF_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( self::CACHE_KEY, array( 'release' => array() ), HOUR_IN_SECONDS );
			return array();
		}

		$release = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $release ) || empty( $release['tag_name'] ) || empty( $release['assets'] ) || ! is_array( $release['assets'] ) ) {
			set_site_transient( self::CACHE_KEY, array( 'release' => array() ), HOUR_IN_SECONDS );
			return array();
		}

		set_site_transient( self::CACHE_KEY, array( 'release' => $release ), 6 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Normalize a GitHub tag into a plugin version.
	 *
	 * @param array<string, mixed> $release GitHub release data.
	 * @return string
	 */
	private function get_release_version( $release ) {
		$tag = isset( $release['tag_name'] ) ? ltrim( (string) $release['tag_name'], "vV \t\n\r\0\x0B" ) : '';

		return preg_match( '/^\d+(?:\.\d+){1,3}(?:[-+][0-9A-Za-z.-]+)?$/', $tag ) ? $tag : '';
	}

	/**
	 * Find the installable ZIP attached to a GitHub release.
	 *
	 * @param array<string, mixed> $release GitHub release data.
	 * @param string               $version Normalized release version.
	 * @return string
	 */
	private function get_release_package( $release, $version ) {
		if ( '' === $version ) {
			return '';
		}

		$expected_name = 'atshift-user-profile-fields-' . $version . '.zip';

		foreach ( (array) $release['assets'] as $asset ) {
			if ( ! is_array( $asset ) || $expected_name !== ( $asset['name'] ?? '' ) ) {
				continue;
			}

			if ( isset( $asset['state'] ) && 'uploaded' !== $asset['state'] ) {
				continue;
			}

			return isset( $asset['browser_download_url'] ) ? esc_url_raw( $asset['browser_download_url'] ) : '';
		}

		return '';
	}
}
