<?php
/**
 * The main plugin file.
 *
 * @package Acato_Email_Essentials
 */

namespace Acato\Email_Essentials;

/**
 * Plugin Name: Email Essentials
 * Description: A must-have plugin for WordPress to get your outgoing emails straightened out.
 * Plugin URI: https://github.com/acato-plugins/email-essentials
 * Author: Remon Pel <remon@acato.nl>
 * Author URI: https://acato.nl
 * Version: 5.5.3
 * Requires PHP: 7.4
 * Requires at least: 5.6
 * Tested up to: 6.9
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
			$n . '\\Plugin'            => __DIR__ . '/lib/class-plugin.php',
			$n . '\\IP'                => __DIR__ . '/lib/class-ip.php',
			$n . '\\History'           => __DIR__ . '/lib/class-history.php',
			$n . '\\Queue'             => __DIR__ . '/lib/class-queue.php',
			$n . '\\Fake_Sender'       => __DIR__ . '/lib/class-fake-sender.php',
			$n . '\\Queue_List_Table'  => __DIR__ . '/lib/class-queue-list-table.php',
			$n . '\\CSS_Inliner'       => __DIR__ . '/lib/class-css-inliner.php',
			$n . '\\CssVarEval'        => __DIR__ . '/lib/class-cssvareval.php',
			$n . '\\CssToInlineStyles' => __DIR__ . '/lib/class-csstoinlinestyles.php',
			$n . '\\Logger'            => __DIR__ . '/lib/class-logger.php',
		];

		/**
		 * Depending on the WordPress version, the phpMailer object to overload is in a different file/is called differently.
		 */
		if ( version_compare( $wp_version, '5.4.99', '<' ) ) {
			$class_map[ $n . '\\EEMailer' ] = __DIR__ . '/lib/class-eemailer.wp54.php';
		} elseif ( version_compare( $wp_version, '6.8', '>=' ) ) {
			$class_map[ $n . '\\EEMailer' ] = __DIR__ . '/lib/class-eemailer.wp68.php';
		} else {
			$class_map[ $n . '\\EEMailer' ] = __DIR__ . '/lib/class-eemailer.wp55.php';
		}

		if ( ! empty( $class_map[ $class_name ] ) && is_file( $class_map[ $class_name ] ) ) {
			require_once $class_map[ $class_name ];
		}
	}
);

require_once __DIR__ . '/lib/sabberworm/autoload.php';

// This file is only used for migration from older versions, not applicable to the public version on WordPress.org.
if ( file_exists( __DIR__ . '/lib/deprecation.php' ) ) {
	require_once __DIR__ . '/lib/deprecation.php';
}

Plugin::instance( __FILE__ );
