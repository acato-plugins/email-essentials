<?php
/**
 * Deprecation handling for Email Essentials.
 *
 * @since   6.0.0
 * @package Acato_Email_Essentials
 * @author  Remon Pel <remon@acato.nl>
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	function ( $class_name ) {
		$n = 'Acato\\Email_Essentials';

		$class_map = [];
		if ( file_exists( __DIR__ . '/class-migrations.php' ) ) {
			$class_map[ $n . '\\Migrations' ] = __DIR__ . '/class-migrations.php';
		}
		
		// Deprecation support.
		$n         = 'WP_Email_Essentials';
		$class_map = array_merge(
			$class_map,
			[
				$n . '\\Plugin'                => __DIR__ . '/class-deprecation.php',
				$n . '\\Migrations'            => __DIR__ . '/class-deprecation.php',
				$n . '\\IP'                    => __DIR__ . '/class-deprecation.php',
				$n . '\\History'               => __DIR__ . '/class-deprecation.php',
				$n . '\\Queue'                 => __DIR__ . '/class-deprecation.php',
				$n . '\\Fake_Sender'           => __DIR__ . '/class-deprecation.php',
				$n . '\\WPES_Queue_List_Table' => __DIR__ . '/class-deprecation.php',
				$n . '\\CSS_Inliner'           => __DIR__ . '/class-deprecation.php',
				$n . '\\CssVarEval'            => __DIR__ . '/class-deprecation.php',
				$n . '\\CssToInlineStyles'     => __DIR__ . '/class-deprecation.php',
				$n . '\\WPES_PHPMailer'        => __DIR__ . '/class-deprecation.php',
			]
		);

		if ( ! empty( $class_map[ $class_name ] ) && is_file( $class_map[ $class_name ] ) ) {
			require_once $class_map[ $class_name ];
		}
	}
);

require_once __DIR__ . '/filter-deprecation.php';

add_action(
	'after_setup_theme',
	function () {
		$tpl = locate_template( [ 'wpes-email-template.php' ], false, false );
		if ( $tpl ) {
			add_action(
				'admin_notices',
				function () use ( $tpl ) {
					?>
					<div class="notice notice-warning is-dismissible">
						<p>
							<?php
							// translators: 1: the old template file name, 2: the new template file name.
							echo wp_kses_post( sprintf( __( 'The email template file <code>%1$s</code> is deprecated and has been replaced by <code>%2$s</code>. Please copy the new template file into your theme folder and remove the old one.', 'email-essentials' ), 'wpes-email-template.php', 'email-essentials-email-template.php' ) );
							?>
						</p>
					</div>
					<?php
				}
			);
			// and rename the file to prevent further usage.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Will not be in public version, only for deprecation handling.
			rename( $tpl, str_replace( 'wpes-', 'email-essentials-', $tpl ) );
		}
	}
);
