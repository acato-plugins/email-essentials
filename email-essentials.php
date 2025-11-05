<?php
/**
 * The main plugin file.
 *
 * @package WP_Email_Essentials
 */

namespace Acato\Email_Essentials;

/**
 * Plugin Name: Email Essentials
 * Description: A must-have plugin for WordPress to get your outgoing emails straightened out.
 * Plugin URI: https://github.com/acato-plugins/email-essentials
 * Author: Remon Pel <remon@acato.nl>
 * Author URI: https://acato.nl
 * Version: 5.4.7
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Text Domain: email-essentials
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

spl_autoload_register(
	function ( $class_name ) {
		global $wp_version;
		$n = __NAMESPACE__;

		$class_map = [
			$n . '\\Plugin'                => __DIR__ . '/lib/class-plugin.php',
			$n . '\\IP'                    => __DIR__ . '/lib/class-ip.php',
			$n . '\\History'               => __DIR__ . '/lib/class-history.php',
			$n . '\\Queue'                 => __DIR__ . '/lib/class-queue.php',
			$n . '\\Fake_Sender'           => __DIR__ . '/lib/class-fake-sender.php',
			$n . '\\WPES_Queue_List_Table' => __DIR__ . '/lib/class-wpes-queue-list-table.php',
			$n . '\\CSS_Inliner'           => __DIR__ . '/lib/class-css-inliner.php',
			$n . '\\CssVarEval'            => __DIR__ . '/lib/class-cssvareval.php',
			$n . '\\CssToInlineStyles'     => __DIR__ . '/lib/class-csstoinlinestyles.php',
		];
		if ( file_exists( __DIR__ . '/lib/class-migrations.php' ) ) {
			$class_map[ $n . '\\Migrations' ] = __DIR__ . '/lib/class-migrations.php';
		}

		/**
		 * Depending on the WordPress version, the phpMailer object to overload is in a different file/is called differently.
		 */
		if ( version_compare( $wp_version, '5.4.99', '<' ) ) {
			$class_map[ $n . '\\EEMailer' ] = __DIR__ . '/lib/class-eemailer.wp54.php';
		} else {
			$class_map[ $n . '\\EEMailer' ] = __DIR__ . '/lib/class-eemailer.wp55.php';
		}

		// Deprecation support.
		$n         = 'WP_Email_Essentials';
		$class_map = array_merge(
			$class_map,
			[
				$n . '\\Plugin'                => __DIR__ . '/lib/deprecation.php',
				$n . '\\Migrations'            => __DIR__ . '/lib/deprecation.php',
				$n . '\\IP'                    => __DIR__ . '/lib/deprecation.php',
				$n . '\\History'               => __DIR__ . '/lib/deprecation.php',
				$n . '\\Queue'                 => __DIR__ . '/lib/deprecation.php',
				$n . '\\Fake_Sender'           => __DIR__ . '/lib/deprecation.php',
				$n . '\\WPES_Queue_List_Table' => __DIR__ . '/lib/deprecation.php',
				$n . '\\CSS_Inliner'           => __DIR__ . '/lib/deprecation.php',
				$n . '\\CssVarEval'            => __DIR__ . '/lib/deprecation.php',
				$n . '\\CssToInlineStyles'     => __DIR__ . '/lib/deprecation.php',
				$n . '\\WPES_PHPMailer'        => __DIR__ . '/lib/deprecation.php',
			]
		);

		if ( ! empty( $class_map[ $class_name ] ) && is_file( $class_map[ $class_name ] ) ) {
			require_once $class_map[ $class_name ];
		}
	}
);

require_once __DIR__ . '/lib/sabberworm/autoload.php';

// This file is used internally to define presets we use, but are not allowed to ship with the plugin.
// You are not missing out. This is not a secret feature, not a path to a premium upgrade, just some predefined settings we use on our clients' sites.
// See documentation on filter 'email_essentials_ip_services', for example, what we use this for.
// You do not need this file for the plugin to work, and you can define the same settings yourself using the available filters, in your theme or a custom plugin.
if ( file_exists( __DIR__ . '/presets.php' ) ) {
	require_once __DIR__ . '/presets.php';
}

new Plugin();

register_activation_hook( __FILE__, [ Migrations::class, 'plugin_activation_hook' ] );
