<?php
/**
 * Class for Migrating other plugins settings
 *
 * @package WP_Email_Essentials
 */

namespace Acato\Email_Essentials;

/**
 * The PHP 5.4 and earlier version of this class, also used as the base for the PHP 5.5+ version.
 */
class Migrations {

	/**
	 * Implementation of register_activation_hook().
	 */
	public static function plugin_activation_hook() {
		self::migrate_from_smtp_connect();
		self::migrate_from_post_smtp();
	}

	/**
	 * Runs on init, implement filters for Acato specific services.
	 */
	public static function init() {
		add_filter( 'email_essentials_ip_services', [ self::class, 'email_essentials_ip_services' ] );
		add_filter( 'email_essentials_website_root_path', [ self::class, 'email_essentials_website_root_path' ] );

		self::maybe_migrate_from_wp_email_essentials();
	}

	/**
	 * Migrate settings from SMTP Connect and deactivate the plugin.
	 */
	public static function migrate_from_smtp_connect() {
		$plugin = 'smtp-connect/smtp-connect.php';
		$active = self::plugin_active( $plugin );

		if ( $active ) {
			// plugin active, migrate.
			$wpes_config = Plugin::get_config();

			$smtp_connect = get_option( 'smtp-connect', [] );
			if ( $smtp_connect['enabled'] ?? false ) {
				$wpes_config['smtp-enabled'] = true;
				$wpes_config['host']         = $smtp_connect['Host'];
				$wpes_config['username']     = $smtp_connect['Username'];
				$wpes_config['password']     = $smtp_connect['Password'];
			}
			Plugin::update_config( $wpes_config );

			self::deactivate( $plugin );
		}
	}

	/**
	 * Migrate settings from Postman SMTP and deactivate the plugin.
	 */
	public static function migrate_from_post_smtp() {
		$plugin = 'post-smtp/postman-smtp.php';
		$active = self::plugin_active( $plugin );

		if ( $active ) {
			// plugin active, migrate.
			$wpes_config = Plugin::get_config();

			$postman_settings = get_option( 'postman_options', [] );
			if ( $postman_settings['hostname'] ?? false ) {
				$wpes_config['smtp-enabled']   = true;
				$wpes_config['secure']         = '' !== $postman_settings['enc_type'] ? 'tls-' : ''; // Assume unvalidated TLS; we don't know.
				$wpes_config['host']           = $postman_settings['hostname'];
				$wpes_config['port']           = $postman_settings['port'];
				$wpes_config['username']       = $postman_settings['basic_auth_username'];
				$wpes_config['password']       = $postman_settings['basic_auth_password'];
				$wpes_config['from_name']      = $postman_settings['sender_name'];
				$wpes_config['from_email']     = $postman_settings['sender_email'];
				$wpes_config['enable_history'] = true === $postman_settings['mail_log_enabled'] || 'true' === $postman_settings['mail_log_enabled'];
			}
			Plugin::update_config( $wpes_config );

			self::deactivate( $plugin );
		}
	}

	/**
	 * Check if the plugin is active
	 *
	 * @param string $plugin The plugin to check.
	 *
	 * @return false|string False for not active, blog or network for active on blog or network
	 */
	private static function plugin_active( $plugin ) {
		if ( is_multisite() && is_plugin_active_for_network( $plugin ) ) {
			return 'network';
		}
		if ( is_plugin_active( $plugin ) ) {
			return 'blog';
		}

		return false;
	}

	/**
	 * Deactivate a plugin, the hard way.
	 *
	 * @param string $plugin The plugin file path relative to wp-content/plugins.
	 */
	private static function deactivate( $plugin ) {
		// deactivate conflicting plugin.
		deactivate_plugins( $plugin, false, true );

		// WordPress still thinks the plugin is active, do it the hard way.
		$active = get_option( 'active_plugins', [] );
		unset( $active[ array_search( $plugin, $active, true ) ] );
		update_option( 'active_plugins', $active );

		$active = get_site_option( 'active_sitewide_plugins', [] );
		unset( $active[ array_search( $plugin, $active, true ) ] );
		update_site_option( 'active_sitewide_plugins', $active );

		// log the deactivation.
		update_option( 'recently_activated', array_merge( get_option( 'recently_activated', [] ) ?: [], [ $plugin => time() ] ) );
		update_site_option( 'recently_activated', array_merge( get_site_option( 'recently_activated', [] ) ?: [], [ $plugin => time() ] ) );
	}

	/**
	 * Provide Acato IP services.
	 *
	 * @return array
	 */
	public static function email_essentials_ip_services() {
		$services = [
			'ipv4'       => 'https://ip4.acato.nl',
			'ipv6'       => 'https://ip6.acato.nl',
			'dual-stack' => 'https://ip.acato.nl',
		];

		return $services;
	}

	/**
	 * Provide the website root path, considering WordPress being in a subdirectory.
	 *
	 * @param string $root_path The current root path.
	 *
	 * @return string
	 */
	public static function email_essentials_website_root_path( $root_path ) {
		$wp_path_rel_to_home = self::email_essentials_get_wp_subdir();

		if ( '' !== $wp_path_rel_to_home ) {
			$pos       = strripos( str_replace( '\\', '/', ABSPATH ), trailingslashit( $wp_path_rel_to_home ) );
			$home_path = substr( ABSPATH, 0, $pos );
			$home_path = trailingslashit( $home_path );
			$root_path = self::email_essentials_nice_path( $home_path );
		}

		// Support Deployer style paths.
		if ( preg_match( '@/releases/(\d+)/@', $root_path, $matches ) ) {
			$path_named_current = str_replace( '/releases/' . $matches[1] . '/', '/current/', $root_path );
			if ( is_dir( $path_named_current ) && realpath( $path_named_current ) === realpath( $root_path ) ) {
				$root_path = $path_named_current;
			}

		}

		return $root_path;
	}

	/**
	 * Get the WordPress subdirectory relative to home URL.
	 *
	 * @return string The subdirectory path, or empty string if WordPress is installed in the root.
	 */
	private static function email_essentials_get_wp_subdir() {
		$home    = preg_replace( '@https?://@', 'http://', get_option( 'home' ) );
		$siteurl = preg_replace( '@https?://@', 'http://', get_option( 'siteurl' ) );

		if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
			return str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
		}

		return '';
	}

	/**
	 * Cleanup a path a bit.
	 *
	 * @param string $path The path to cleanup.
	 *
	 * @return string
	 */
	private static function email_essentials_nice_path( $path ) {
		// Turn \ into / .
		$path = str_replace( '\\', '/', $path );
		// Remove "current" instances.
		$path = str_replace( '/./', '/', $path );
		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// @todo: remove  ../somethingotherthandotdot/ .

		return $path;
	}

	/**
	 * Migrate settings from WP Email Essentials (old plugin) to Email Essentials.
	 */
	private static function maybe_migrate_from_wp_email_essentials() {
		// Settings mapping from old to new.
		$settings_map = [];
	}
}
