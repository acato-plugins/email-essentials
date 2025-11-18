<?php
/**
 * Deprecation handling for Email Essentials.
 *
 * @since   6.0.0
 * @package Acato_Email_Essentials
 * @author  Remon Pel <remon@acato.nl>
 */

spl_autoload_register(
	function ( $class_name ) {
		$n = 'Acato\\Email_Essentials';

		$class_map = [];
		if ( file_exists( __DIR__ . '/lib/class-migrations.php' ) ) {
			$class_map[ $n . '\\Migrations' ] = __DIR__ . '/lib/class-migrations.php';
		}

		// Deprecation support.
		$n         = 'WP_Email_Essentials';
		$class_map = array_merge(
			$class_map,
			[
				$n . '\\Plugin'                => __DIR__ . '/lib/class-deprecation.php',
				$n . '\\Migrations'            => __DIR__ . '/lib/class-deprecation.php',
				$n . '\\IP'                    => __DIR__ . '/lib/class-deprecation.php',
				$n . '\\History'               => __DIR__ . '/lib/class-deprecation.php',
				$n . '\\Queue'                 => __DIR__ . '/lib/class-deprecation.php',
				$n . '\\Fake_Sender'           => __DIR__ . '/lib/class-deprecation.php',
				$n . '\\WPES_Queue_List_Table' => __DIR__ . '/lib/class-deprecation.php',
				$n . '\\CSS_Inliner'           => __DIR__ . '/lib/class-deprecation.php',
				$n . '\\CssVarEval'            => __DIR__ . '/lib/class-deprecation.php',
				$n . '\\CssToInlineStyles'     => __DIR__ . '/lib/class-deprecation.php',
				$n . '\\WPES_PHPMailer'        => __DIR__ . '/lib/class-deprecation.php',
			]
		);

		if ( ! empty( $class_map[ $class_name ] ) && is_file( $class_map[ $class_name ] ) ) {
			require_once $class_map[ $class_name ];
		}
	}
);

require_once __DIR__ . '/filter-deprecation.php';

add_action( 'after_setup_theme', function () {
	$tpl = locate_template( [ 'wpes-email-template.php' ], false, false );
	if ( $tpl ) {
		_deprecated_file( $tpl, '6.0.0', 'Use the `email-essentials-email-template.php` filename instead.' );
		// and rename the file to prevent further usage.
		rename( $tpl, str_replace( 'wpes-', 'email-essentials-', $tpl ) );
	}
} );
