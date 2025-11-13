<?php
/**
 * Handles mail log.
 *
 * @package WP_Email_Essentials
 */

namespace Acato\Email_Essentials;

use WP_Error;

/**
 * The mail history / log class.
 */
class History {
	const MAIL_NEW    = 0;
	const MAIL_SENT   = 1;
	const MAIL_FAILED = 2;
	const MAIL_OPENED = 3;
	const MAIL_RESENT = 4;

	/**
	 * Get the singleton instance.
	 *
	 * @return History
	 */
	public static function instance() {
		static $instance;
		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		self::init();
	}

	/**
	 * Memory cell: remember the last inserted ID.
	 *
	 * @param null|int $set The content for the cell.
	 *
	 * @return null|int
	 */
	private static function last_insert( $set = null ) {
		static $id;
		if ( $set ) {
			$id = $set;
		}

		return $id;
	}

	/**
	 * Main code.
	 */
	private static function init() {
		global $wpdb;
		$enabled = Plugin::get_config();
		$enabled = $enabled['enable_history'];

		if ( $enabled ) {
			$schema = "CREATE TABLE `{$wpdb->prefix}wpes_hist` (
			  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `sender` varchar(256) NOT NULL DEFAULT '',
			  `ip` varchar(128) NOT NULL DEFAULT '',
			  `recipient` varchar(256) NOT NULL DEFAULT '',
			  `subject` varchar(256) NOT NULL DEFAULT '',
			  `headers` text NOT NULL,
			  `body` text NOT NULL,
			  `alt_body` text NOT NULL,
			  `eml` LONGTEXT NOT NULL,
			  `thedatetime` datetime NOT NULL,
			  `status` int(11) NOT NULL,
			  `errinfo` text NOT NULL,
			  `debug` text NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `sender` (`sender`(255)),
			  KEY `recipient` (`recipient`(255)),
			  KEY `subject` (`subject`(255)),
			  KEY `thedatetime` (`thedatetime`),
			  KEY `status` (`status`)
			) DEFAULT CHARSET=utf8mb4";
			$hash   = md5( $schema );
			if ( get_option( 'acato_email_essentials_history_revision' ) !== $hash ) {
				require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/upgrade.php';
				dbDelta( $schema );

				update_option( 'acato_email_essentials_history_revision', $hash );
			}
			add_action( 'phpmailer_init', [ self::class, 'phpmailer_init' ], 10000000000 );
			add_filter( 'wp_mail', [ self::class, 'wp_mail' ], 10000000000 );
			add_action( 'wp_mail_failed', [ self::class, 'wp_mail_failed' ], 10000000000 );
			add_action( 'wp_mail_succeeded', [ self::class, 'wp_mail_succeeded' ], 10000000000 );
			add_action( 'pre_handle_404', [ self::class, 'handle_tracker' ], ~PHP_INT_MAX );
			add_action( 'shutdown', [ self::class, 'shutdown' ] );
			add_action( 'admin_menu', [ self::class, 'admin_menu' ] );
		} elseif ( get_option( 'acato_email_essentials_history_revision', 0 ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "DROP TABLE `{$wpdb->prefix}wpes_hist`;" );
			delete_option( 'acato_email_essentials_history_revision' );
		}

		add_action(
			'init',
			function () {
				global $wpdb;
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- not a form!.
				$download_eml = isset( $_GET['download_eml'] ) ? (int) $_GET['download_eml'] : 0;
				if ( current_user_can( 'manage_options' ) && $download_eml ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.Security.NonceVerification.Recommended -- still not a form!.
					$data = $wpdb->get_row( $wpdb->prepare( "SELECT ID, eml, subject, recipient, thedatetime FROM {$wpdb->prefix}wpes_hist WHERE id = %d LIMIT 1", $download_eml ), ARRAY_A );
					if ( $data['eml'] ?? false ) {
						header( 'Content-Type: message/rfc822' );
						$uniq = sprintf(
							'%1$s-%2$d-%3$s-%4$s',
							sanitize_title( $data['thedatetime'] ),
							(int) $data['ID'],
							sanitize_title(
								strtr(
									$data['recipient'],
									[
										'.' => '-dot-',
										'@' => '-at-',
									]
								)
							),
							sanitize_title( $data['subject'] )
						);
						header( 'Content-Disposition: inline; filename=' . $uniq . '.eml' );
						header( 'Content-Length: ' . strlen( $data['eml'] ) );
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- How do you escape an email? we're DOWNLOADING it.
						print $data['eml'];
						exit;
					}
				}
			}
		);
	}

	/**
	 * Callback for shutdown action. Purge the history older than 1 month.
	 */
	public static function shutdown() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM `{$wpdb->prefix}wpes_hist` WHERE thedatetime <  NOW() - INTERVAL 1 MONTH" );
	}

	/**
	 * Callback for admin_menu action.
	 */
	public static function admin_menu() {
		add_submenu_page(
			'acato-email-essentials',
			Plugin::plugin_data()['Name'] . ' - ' . __( 'Email History', 'email-essentials' ),
			__( 'Email History', 'email-essentials' ),
			'manage_options',
			'wpes-emails',
			[ self::class, 'admin_interface' ]
		);
		self::maybe_resend_email();
	}

	/**
	 * The admin interface.
	 */
	public static function admin_interface() {
		Plugin::view( 'admin-emails' );
	}

	/**
	 * Maybe resend an email.
	 */
	private static function maybe_resend_email() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce is verified below.
		$the_nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce is verified below.
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce is verified below.
		$email = isset( $_GET['email'] ) ? (int) $_GET['email'] : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce is verified below.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( ! is_admin() || 'wpes-emails' !== $page ) {
			return;
		}

		if ( ! wp_verify_nonce( $the_nonce, 'wpes_resend_email_' . $email ) ) {
			return;
		}

		if ( 'resend-failed-email' !== $action || ! $email || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		if ( $email <= 0 ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}wpes_hist` WHERE ID = %d LIMIT 1", $email ), ARRAY_A );
		if ( ! $data ) {
			return;
		}

		$relates_to = $email;
		preg_match( '/X-Relates-To: (.*)/', $data['headers'], $matches );
		if ( ! empty( $matches[1] ) ) {
			$relates_to      = $matches[1] . ',' . $email;
			$data['headers'] = str_replace( $matches[0], '', $data['headers'] );
		}
		$headers   = explode( "\n", $data['headers'] );
		$headers   = array_map( 'trim', $headers );
		$headers   = array_filter( $headers );
		$headers[] = 'X-Relates-To: ' . $relates_to;

		wp_mail( Plugin::rfc_explode( $data['recipient'] ), $data['subject'], $data['body'], $headers, self::extract_attachments( $data['eml'] ) );

		// Mark email as re-sent so we don't try to resend it again.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpes_hist` SET status = %d WHERE ID = %d LIMIT 1", self::MAIL_RESENT, $email ) );

		wp_safe_redirect( remove_query_arg( [ 'action', 'nonce', 'email' ] ) );
		exit;
	}

	/**
	 * Extract attachments from an EML file.
	 *
	 * @param string $eml The EML content.
	 *
	 * @return array
	 */
	private static function extract_attachments( $eml ) {
		$boundary = '';
		if ( preg_match( '/boundary="([^"]+)"/i', $eml, $matches ) ) {
			$boundary = $matches[1];
		}
		if ( ! $boundary ) {
			return []; // No boundary found, no attachments.
		}
		// Split the EML content by the boundary.
		$parts = preg_split( '/--' . preg_quote( $boundary, '/' ) . '/', $eml );
		if ( ! is_array( $parts ) || empty( $parts ) ) {
			return []; // No parts found, no attachments.
		}

		$attachments = [];

		foreach ( $parts as $part ) {
			list( $headers, $content ) = explode( "\r\n\r\n", $part . "\r\n\r\n", 2 );

			$headers = trim( $headers );
			$content = trim( $content );
			if ( empty( $headers ) || empty( $content ) ) {
				continue; // Skip empty parts.
			}

			// Parse headers into an associative array.
			$header_lines = explode( "\r\n", $headers );
			$header_array = [];
			foreach ( $header_lines as $line ) {
				$line = trim( $line );
				if ( strpos( $line, ':' ) !== false ) {
					list( $key, $value ) = explode( ':', $line, 2 );

					$header_array[ trim( $key ) ] = trim( $value );
				}
			}

			// Check if this part is an attachment.
			if ( isset( $header_array['Content-Disposition'] ) && strpos( $header_array['Content-Disposition'], 'attachment' ) !== false ) {
				// Extract the filename.
				$filename = '';
				if ( preg_match( '/filename="([^"]+)"/', $header_array['Content-Disposition'], $matches ) ) {
					$filename = $matches[1];
				} elseif ( preg_match( '/filename=([^\s]+)/', $header_array['Content-Disposition'], $matches ) ) {
					$filename = $matches[1];
				} elseif ( preg_match( '/name="([^"]+)"/', $header_array['Content-Disposition'], $matches ) ) {
					$filename = $matches[1];
				}

				if ( ! empty( $filename ) && ! empty( $content ) ) {
					// create a temporary file to store the attachment.
					$tmp_dir = wp_tempnam( 'wpes-attachments' );
					$tmp_dir = wp_upload_dir()['basedir'] . '/.wpes/' . basename( $tmp_dir );
					if ( $tmp_dir ) {
						// Write the content to the temporary file.
						wp_mkdir_p( $tmp_dir );
						// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents,WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
						file_put_contents( $tmp_dir . '/' . $filename, base64_decode( $content ) );
						add_action(
							'shutdown',
							function () use ( $tmp_dir, $filename ) {
								// Delete the temporary file on shutdown.
								if ( file_exists( $tmp_dir . '/' . $filename ) ) {
									wp_delete_file( $tmp_dir . '/' . $filename );
									// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir,WordPress.PHP.NoSilencedErrors.Discouraged -- We don't care if it fails.
									@rmdir( $tmp_dir );
								}
							}
						);
					}
					$attachments[] = $tmp_dir . '/' . $filename; // Store the path to the temporary file.
				}
			}
		}

		return $attachments;
	}

	/**
	 * Retrieve the recipients from the Mailer object.
	 *
	 * @param EEMailer $phpmailer The mailer object.
	 *
	 * @return array
	 */
	public static function get_to_addresses( $phpmailer ) {
		if ( method_exists( $phpmailer, 'getToAddresses' ) ) {
			return $phpmailer->getToAddresses();
		}

		// this version of PHPMailer does not have getToAddresses and To is protected. Use a dump to get the data we need.
		$mailer_data = self::object_data( $phpmailer );

		return $mailer_data->to;
	}

	/**
	 * Use print_r to dump the object and extract the data we need.
	 *
	 * @param mixed $an_object Object to inspect.
	 *
	 * @return mixed
	 */
	private static function object_data( $an_object ) {
		ob_start();
		$class = get_class( $an_object );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- How else are we supposed to get the info we need?.
		print_r( $an_object );
		$an_object = ob_get_clean();
		$an_object = str_replace( $class . ' Object', 'Array', $an_object );
		$an_object = str_replace( ':protected]', ']', $an_object );
		$an_object = self::print_r_reverse( $an_object );

		return json_decode( wp_json_encode( $an_object ) );
	}

	/**
	 * The reverse of print_r; make an object of a dump.
	 *
	 * @param string $in A print_r dump.
	 *
	 * @return mixed
	 */
	private static function print_r_reverse( $in ) {
		$lines = explode( "\n", trim( $in ) );
		if ( 'Array' !== trim( $lines[0] ) ) {
			// bottomed out to something that isn't an array.
			return $in;
		} else {
			// this is an array, lets parse it.
			if ( preg_match( '/(\s{5,})\(/', $lines[1], $match ) ) {
				// this is a tested array/recursive call to this function.
				// take a set of spaces off the beginning.
				$spaces        = $match[1];
				$spaces_length = strlen( $spaces );
				$lines_total   = count( $lines );
				for ( $i = 0; $i < $lines_total; $i++ ) {
					if ( substr( $lines[ $i ], 0, $spaces_length ) === $spaces ) {
						$lines[ $i ] = substr( $lines[ $i ], $spaces_length );
					}
				}
			}
			array_shift( $lines ); // Array.
			array_shift( $lines ); // ( .
			array_pop( $lines ); // ) .
			$in = implode( "\n", $lines );
			// make sure we only match stuff with 4 preceding spaces (stuff for this array and not a nested one).
			preg_match_all( '/^\s{4}\[(.+?)\] \=\> /m', $in, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER );
			$pos          = [];
			$previous_key = '';
			$in_length    = strlen( $in );
			// store the following in $pos:.
			// array with key = key of the parsed array's item.
			// value = array(start position in $in, $end position in $in).
			foreach ( $matches as $match ) {
				$key         = $match[1][0];
				$start       = $match[0][1] + strlen( $match[0][0] );
				$pos[ $key ] = [ $start, $in_length ];
				if ( '' !== $previous_key ) {
					$pos[ $previous_key ][1] = $match[0][1] - 1;
				}
				$previous_key = $key;
			}
			$ret = [];
			foreach ( $pos as $key => $where ) {
				// recursively see if the parsed out value is an array too.
				$ret[ $key ] = self::print_r_reverse( substr( $in, $where[0], $where[1] - $where[0] ) );
			}

			return $ret;
		}
	}

	/**
	 * Callback to action phpmailer_init: Grab the object for debug purposes.
	 *
	 * @param EEMailer $phpmailer The PHPMailer object.
	 */
	public static function phpmailer_init( &$phpmailer ) {
		// @phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer, folks...
		global $wpdb;
		$data                  = self::object_data( $phpmailer );
		$data->Password        = '********';
		$data->DKIM_passphrase = '********';
		$sender                = $data->From === $data->FromName || ! $data->FromName ? $data->From : sprintf( '%s <%s>', $data->FromName, $data->From );
		$reply_to              = $data->ReplyTo ?? $sender;
		if ( $sender !== $reply_to ) {
			$reply_to       = (array) $data->ReplyTo;
			$reply_to       = reset( $reply_to );
			$reply_to_name  = trim( $reply_to[1] ?? '' );
			$reply_to_email = trim( $reply_to[0] ?? '' );
			$reply_to       = $reply_to_name && $reply_to_name !== $reply_to_email ? sprintf( '%s <%s>', $reply_to_name, $reply_to_email ) : $reply_to_email;

			$sender = $reply_to . ' *';
		}
		$data = wp_json_encode( $data, JSON_PRETTY_PRINT );

		self::add_tracker( $phpmailer->Body, self::last_insert() );

		$phpmailer->PreSend();
		$eml = $phpmailer->GetSentMIMEMessage();
		Plugin::log_message( "UPDATE sender to $sender" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpes_hist` SET status = %d, sender = %s, alt_body = %s, debug = %s, eml = %s WHERE ID = %d AND subject = %s LIMIT 1", self::MAIL_SENT, $sender, $phpmailer->AltBody, $data, $eml, self::last_insert(), $phpmailer->Subject ) );
		// @phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer, folks...
	}

	/**
	 * Callback on action wp_mail: Record a mail into the history.
	 *
	 * @param array $data WP Mail data with keys 'to', 'subject', 'message', 'headers' and 'attachments'.
	 *
	 * @return mixed
	 */
	public static function wp_mail( $data ) {
		global $wpdb;
		// fallback values.
		$to          = '';
		$subject     = '';
		$message     = '';
		$from        = '';
		$headers     = [];
		$attachments = [];

		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Deal with it.

		if ( ! is_array( $headers ) ) {
			/**
			 * Headers might be a string...
			 *
			 * @var string|array $headers
			 */
			$headers = explode( "\n", $headers );
		}

		$headers = array_map( 'trim', $headers );

		foreach ( $headers as $header ) {
			if ( preg_match( '/^From:(.+)$/i', $header, $m ) ) {
				$from = trim( $m[1] );
			}
		}
		$_headers = trim( implode( "\n", $headers ) );

		$ip = Plugin::server_remote_addr();
		Plugin::log_message( "INSERT with sender $from" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "INSERT INTO `{$wpdb->prefix}wpes_hist` (status, sender, recipient, subject, headers, body, thedatetime, ip) VALUES (%d, %s, %s, %s, %s, %s, %s, %s);", self::MAIL_NEW, $from, is_array( $to ) ? implode( ',', $to ) : $to, $subject, $_headers, $message, gmdate( 'Y-m-d H:i:s', time() ), $ip ) );
		self::last_insert( $wpdb->insert_id );

		return $data;
	}

	/**
	 * Callback on action wp_mail_failed: register the error.
	 *
	 * @param WP_Error $error The error.
	 */
	public static function wp_mail_failed( $error ) {
		global $wpdb;
		$data     = $error->get_error_data();
		$errormsg = $error->get_error_message();
		if ( ! $data ) {
			$errormsg = 'Unknown error';
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpes_hist` SET status = %d, errinfo = CONCAT(%s, errinfo) WHERE ID = %d LIMIT 1", self::MAIL_FAILED, $errormsg . "\n", self::last_insert() ) );

		self::store_log( Logger::get() );
	}

	/**
	 * Callback on action wp_mail_succeeded: store the log.
	 */
	public static function wp_mail_succeeded() {
		self::store_log( Logger::get() );
	}

	/**
	 * Store the log.
	 *
	 * @param array $log The log.
	 */
	private static function store_log( $log ) {
		global $wpdb;
		$log = implode( "\n", $log );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpes_hist` SET debug = CONCAT(debug, '\n----\n', %s) WHERE ID = %d LIMIT 1", $log, self::last_insert() ) );
	}

	/**
	 * Add a tracker to the outgoing email. This only happens when debugging is enabled, which is not GDPR compliant anyway.
	 *
	 * @param string $message The email.
	 * @param int    $mail_id The mail id.
	 */
	private static function add_tracker( &$message, $mail_id ) {
		$tracker_url = trailingslashit( home_url() ) . 'email-image-' . $mail_id . '.png';

		$tracker = '<img src="' . esc_attr( $tracker_url ) . '" alt="" />';

		$message = false !== strpos( $message, '</body>' ) ? str_replace( '</body>', $tracker . '</body>', $message ) : $message . $tracker;
	}

	/**
	 * Callback for action pre_handle_404: act on the calling of the tracker URL.
	 *
	 * For the security people; this is not a vulnerability, as the tracker URL is only included
	 * in emails sent by the site, and the ID is just an integer that maps to an email in the log.
	 *
	 * We do not store any personal data other than what is already in the email log.
	 * We just mark the email as opened, so we know email is actually sent and received.
	 *
	 * Also; this is only active when email logging is enabled, which is not GDPR compliant anyway.
	 * Email logging is a DEBUG function and should only be active for a short time, in case of issues.
	 */
	public static function handle_tracker() {
		global $wpdb;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated by regex below.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( preg_match( '/\/email-image-(\d+).png/', $request_uri, $match ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpes_hist` SET status = %s WHERE ID = %d;", self::MAIL_OPENED, $match[1] ) );

			header( 'Content-Type: image/png' );
			header( 'Content-Length: 0' );
			header( 'HTTP/1.1 404 Not Found' );
			exit;
		}

		return false;
	}
}
