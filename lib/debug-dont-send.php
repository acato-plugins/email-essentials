<?php
/**
 * Debug mode: "Don't send, just log".
 *
 * When enabled, NO email leaves this website. Every email is captured at the deepest possible level, logged in full, and
 * reported back to the caller as successfully sent.
 *
 * Two layers are intercepted:
 * 1. wp_mail()   - via filter `pre_wp_mail` (or `wp_mail` on WordPress < 5.7): the arguments as wp_mail() received them.
 * 2. PHPMailer   - the transport layer:
 *    a. The global $phpmailer object is replaced with Dont_Send_Mailer, which captures the full MIME message in postSend()
 *       instead of transmitting it. This also covers code that uses `global $phpmailer` directly, bypassing wp_mail().
 *    b. Any other PHPMailer instance that passes through action `phpmailer_init` gets its transport replaced with
 *       Dont_Send_SMTP, a fake SMTP connection that accepts everything and captures the DATA instead of transmitting it.
 *
 * An email sent with wp_mail() passes both layers; these are de-duplicated into a single log entry that registers both
 * methods (wp_mail+phpmailer). An email that only passes one layer is registered with that method only.
 *
 * Limitation: a PHPMailer instance created by third party code that neither uses the global $phpmailer object nor fires
 * `phpmailer_init` cannot be reached by any WordPress hook. Such code bypasses WordPress entirely.
 *
 * Enable with the checkbox in the settings screen, or force on/off in wp-config.php with:
 *     define( 'ACATO_EMAIL_ESSENTIALS_DONT_SEND', true );
 *
 * THIS FILE IS NOT PART OF THE WORDPRESS.ORG RELEASE. It is excluded in .gitattributes and package.sh.
 *
 * @since   6.0.10
 * @package Acato_Email_Essentials
 * @author  Remon Pel <remon@acato.nl>
 */

namespace Acato\Email_Essentials;

defined( 'ABSPATH' ) || exit;

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- By design: this feature must be a single file.
// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Our own debug table, caching would be counter-productive.
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer properties, not our code.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use WP_Error;

/**
 * The "Don't send, just log" debug mode.
 */
class Dont_Send {
	/**
	 * Option holding the enabled state.
	 */
	const OPTION = 'acato_email_essentials_dont_send';

	/**
	 * Option holding the database schema revision.
	 */
	const REVISION_OPTION = 'acato_email_essentials_dont_send_revision';

	/**
	 * Constant to force the mode on or off from wp-config.php.
	 */
	const CONSTANT = 'ACATO_EMAIL_ESSENTIALS_DONT_SEND';

	/**
	 * Admin page slug of the log.
	 */
	const PAGE = 'acato-email-essentials/dont-send-log';

	/**
	 * Entries per page in the log.
	 */
	const PER_PAGE = 50;

	/**
	 * The log entry of the wp_mail() call currently in progress, if any.
	 * PHPMailer captures made while this is set belong to this wp_mail() call; this is the de-duplication.
	 *
	 * @var array|null
	 */
	private static $current = null;

	/**
	 * Is the mode enabled?
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$enabled = defined( self::CONSTANT ) ? (bool) constant( self::CONSTANT ) : (bool) get_option( self::OPTION, false );

		/**
		 * Filter the "Don't send, just log" state.
		 *
		 * @param bool $enabled Whether the mode is enabled.
		 */
		return (bool) apply_filters( 'acato_email_essentials_dont_send', $enabled );
	}

	/**
	 * Is the state forced by the constant?
	 *
	 * @return bool
	 */
	public static function is_forced() {
		return defined( self::CONSTANT );
	}

	/**
	 * Is this WordPress version supported? We need the namespaced PHPMailer (WP 5.5+).
	 *
	 * @return bool
	 */
	public static function is_supported() {
		global $wp_version;

		return version_compare( $wp_version, '5.5', '>=' );
	}

	/**
	 * The log table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;

		return $wpdb->prefix . 'acato_email_essentials_dont_send_log';
	}

	/**
	 * The URL of the log.
	 *
	 * @param array $args Additional query arguments.
	 *
	 * @return string
	 */
	public static function log_url( $args = [] ) {
		return add_query_arg( array_merge( [ 'page' => self::PAGE ], $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Initialisation.
	 */
	public static function init() {
		add_action( 'acato_email_essentials_settings_panels', [ self::class, 'settings_panel' ] );
		add_action( 'acato_email_essentials_settings_saved', [ self::class, 'settings_saved' ] );

		if ( ! self::is_supported() || ! self::is_enabled() ) {
			return;
		}

		self::maybe_create_table();

		// Layer 2a: take over the global PHPMailer object right away, so even direct use of `global $phpmailer` is caught.
		self::install_global_mailer();
		// Email Essentials' own JIT overload runs at ~PHP_INT_MAX on wp_mail_from; make sure we win.
		add_filter( 'wp_mail_from', [ self::class, 'install_global_mailer_passthru' ], ~PHP_INT_MAX + 1 );

		// Layer 1: wp_mail().
		global $wp_version;
		if ( version_compare( $wp_version, '5.7', '>=' ) ) {
			add_filter( 'pre_wp_mail', [ self::class, 'pre_wp_mail' ], PHP_INT_MAX, 2 );
		} else {
			add_filter( 'wp_mail', [ self::class, 'wp_mail' ], PHP_INT_MAX );
		}
		add_action( 'wp_mail_succeeded', [ self::class, 'wp_mail_done' ], PHP_INT_MAX );
		add_action( 'wp_mail_failed', [ self::class, 'wp_mail_done' ], PHP_INT_MAX );

		// Layer 2b: any PHPMailer instance that is not ours.
		add_action( 'phpmailer_init', [ self::class, 'phpmailer_init' ], PHP_INT_MAX );

		add_action( 'shutdown', [ self::class, 'shutdown' ] );

		add_action( 'admin_menu', [ self::class, 'admin_menu' ], 11 );
		add_action( 'admin_init', [ self::class, 'handle_admin_actions' ] );
		add_action( 'admin_notices', [ self::class, 'admin_notice' ] );
		add_action( 'admin_bar_menu', [ self::class, 'admin_bar_menu' ], 999 );
	}

	/**
	 * Create or update the log table.
	 */
	private static function maybe_create_table() {
		$table  = self::table();
		$schema = "CREATE TABLE `{$table}` (
			  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `thedatetime` datetime NOT NULL,
			  `methods` varchar(64) NOT NULL DEFAULT '',
			  `status` varchar(32) NOT NULL DEFAULT '',
			  `recipient` text NOT NULL,
			  `subject` varchar(256) NOT NULL DEFAULT '',
			  `origin` text NOT NULL,
			  `backtrace` text NOT NULL,
			  `request` text NOT NULL,
			  `wp_mail_args` longtext NOT NULL,
			  `messages` longtext NOT NULL,
			  `notes` text NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `thedatetime` (`thedatetime`)
			) DEFAULT CHARSET=utf8mb4";
		$hash   = md5( $schema );
		if ( get_option( self::REVISION_OPTION ) !== $hash ) {
			require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/upgrade.php';
			dbDelta( $schema );
			update_option( self::REVISION_OPTION, $hash );
		}
	}

	/**
	 * Drop the log table.
	 */
	private static function drop_table() {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is ours.
		$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
		delete_option( self::REVISION_OPTION );
	}

	/**
	 * Make sure the global PHPMailer object is our capturing mailer.
	 */
	public static function install_global_mailer() {
		global $phpmailer;
		if ( ! ( $phpmailer instanceof Dont_Send_Mailer ) ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- That is the whole point.
			$phpmailer = new Dont_Send_Mailer( true );

			$phpmailer::$validator = static function ( $email ) {
				return (bool) is_email( $email );
			};
		}
	}

	/**
	 * Filter wrapper for install_global_mailer().
	 *
	 * @param mixed $passthru Filter value, untouched.
	 *
	 * @return mixed
	 */
	public static function install_global_mailer_passthru( $passthru ) {
		self::install_global_mailer();

		return $passthru;
	}

	/**
	 * Layer 1: register the wp_mail() call. Runs last, so we see the arguments after all other filters.
	 *
	 * @param null|bool $short_circuit Short-circuit return value, null to continue.
	 * @param array     $atts          The wp_mail() arguments.
	 *
	 * @return null|bool
	 */
	public static function pre_wp_mail( $short_circuit, $atts ) {
		// A previous wp_mail() call that never reached wp_mail_succeeded/wp_mail_failed.
		self::finish_current( 'incomplete', __( 'wp_mail() did not finish; neither wp_mail_succeeded nor wp_mail_failed fired.', 'email-essentials' ) );
		self::install_global_mailer();

		$entry = self::new_entry( 'wp_mail', $atts );
		if ( null !== $short_circuit ) {
			$entry['status'] = 'short-circuited';
			$entry['notes']  = sprintf(
				// translators: %s: the value returned by the pre_wp_mail filter.
				__( 'Another plugin short-circuited wp_mail() with filter pre_wp_mail (returning %s). That code handled the email, so we CANNOT guarantee it was not sent.', 'email-essentials' ),
				wp_json_encode( $short_circuit )
			);
			self::save( $entry );

			return $short_circuit;
		}

		self::$current = self::save( $entry );

		return $short_circuit;
	}

	/**
	 * Layer 1 for WordPress < 5.7, which has no pre_wp_mail filter.
	 *
	 * @param array $atts The wp_mail() arguments.
	 *
	 * @return array
	 */
	public static function wp_mail( $atts ) {
		self::pre_wp_mail( null, $atts );

		return $atts;
	}

	/**
	 * The wp_mail() call completed.
	 *
	 * @param array|WP_Error $result Mail data on success, WP_Error on failure.
	 */
	public static function wp_mail_done( $result ) {
		if ( $result instanceof WP_Error ) {
			self::finish_current( 'failed', $result->get_error_message() );
		} else {
			self::finish_current( 'captured' );
		}
	}

	/**
	 * Close the current wp_mail() log entry.
	 *
	 * @param string $status The status.
	 * @param string $note   An optional note.
	 */
	private static function finish_current( $status, $note = '' ) {
		if ( ! self::$current ) {
			return;
		}
		$entry = self::$current;

		self::$current = null;

		if ( 'captured' === $status && empty( $entry['messages'] ) ) {
			$status = 'not-transported';
			$note   = __( 'wp_mail() reported success, but the email never reached a PHPMailer transport. It was probably queued (Email Essentials throttling) or handled by other code.', 'email-essentials' );
		}
		$entry['status'] = $status;
		$entry['notes']  = trim( $entry['notes'] . "\n" . $note );
		self::save( $entry );
	}

	/**
	 * Layer 2b: neutralise the transport of any PHPMailer instance that is not ours.
	 *
	 * @param PHPMailer $mailer The PHPMailer instance.
	 */
	public static function phpmailer_init( $mailer ) {
		if ( ! ( $mailer instanceof PHPMailer ) || $mailer instanceof Dont_Send_Mailer ) {
			return;
		}

		$original_transport = $mailer->Mailer;
		$mailer->isSMTP();
		$mailer->SMTPKeepAlive = false;
		$mailer->setSMTPInstance( new Dont_Send_SMTP( $mailer, $original_transport ) );
	}

	/**
	 * Layer 2: capture a fully built message. Called instead of the actual transport.
	 *
	 * @param PHPMailer $mailer             The mailer.
	 * @param string    $how                How it was captured; 'global' or 'phpmailer_init'.
	 * @param string    $eml                The complete MIME message.
	 * @param string    $original_transport The transport PHPMailer would have used.
	 */
	public static function capture( $mailer, $how, $eml, $original_transport = '' ) {
		$format = static function ( $addresses ) {
			return array_values(
				array_map(
					static function ( $address ) {
						return $address[1] ? sprintf( '%s <%s>', $address[1], $address[0] ) : $address[0];
					},
					(array) $addresses
				)
			);
		};

		$attachments = [];
		foreach ( $mailer->getAttachments() as $attachment ) {
			$attachments[] = [
				'name'        => $attachment[2],
				'type'        => $attachment[4],
				'disposition' => $attachment[6],
				'size'        => $attachment[5] ? strlen( $attachment[0] ) : ( is_file( $attachment[0] ) ? filesize( $attachment[0] ) : 0 ),
			];
		}

		$message = [
			'captured_by'  => 'global' === $how
				? __( 'The global $phpmailer object (replaced by Dont_Send_Mailer)', 'email-essentials' )
				: __( 'A separate PHPMailer instance, through action phpmailer_init (transport replaced by Dont_Send_SMTP)', 'email-essentials' ),
			'mailer_class' => get_class( $mailer ),
			'transport'    => $original_transport ? $original_transport : $mailer->Mailer,
			'from'         => $mailer->FromName ? sprintf( '%s <%s>', $mailer->FromName, $mailer->From ) : $mailer->From,
			'sender'       => $mailer->Sender,
			'to'           => $format( $mailer->getToAddresses() ),
			'cc'           => $format( $mailer->getCcAddresses() ),
			'bcc'          => $format( $mailer->getBccAddresses() ),
			'reply_to'     => $format( $mailer->getReplyToAddresses() ),
			'subject'      => $mailer->Subject,
			'content_type' => $mailer->ContentType,
			'body'         => $mailer->Body,
			'alt_body'     => $mailer->AltBody,
			'attachments'  => $attachments,
			'eml'          => $eml,
		];

		$recipients = array_merge( $message['to'], $message['cc'], $message['bcc'] );

		if ( self::$current ) {
			// Same email, seen again one level deeper: de-duplicate into the wp_mail() entry.
			self::$current['methods']    = 'wp_mail+phpmailer';
			self::$current['messages'][] = $message;
			// The actual recipients, after all filters, of all (SingleTo split) messages.
			$all_recipients = [];
			foreach ( self::$current['messages'] as $captured ) {
				$all_recipients = array_merge( $all_recipients, $captured['to'], $captured['cc'], $captured['bcc'] );
			}
			self::$current['recipient'] = implode( ', ', array_unique( $all_recipients ) );
			self::$current              = self::save( self::$current );

			return;
		}

		// No wp_mail() in progress: this email bypassed wp_mail().
		$entry              = self::new_entry( 'phpmailer', null );
		$entry['status']    = 'captured';
		$entry['recipient'] = implode( ', ', $recipients );
		$entry['subject']   = $message['subject'];
		$entry['messages']  = [ $message ];
		self::save( $entry );
	}

	/**
	 * Close a dangling wp_mail() entry at the end of the request.
	 */
	public static function shutdown() {
		self::finish_current( 'incomplete', __( 'Request ended while wp_mail() was in progress.', 'email-essentials' ) );
	}

	/**
	 * Create a new log entry.
	 *
	 * @param string     $method 'wp_mail' or 'phpmailer'.
	 * @param array|null $atts   The wp_mail() arguments, if applicable.
	 *
	 * @return array
	 */
	private static function new_entry( $method, $atts ) {
		[ $origin, $backtrace ] = self::backtrace();

		$recipient = '';
		if ( isset( $atts['to'] ) ) {
			$recipient = is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : (string) $atts['to'];
		}

		return [
			'ID'           => 0,
			'thedatetime'  => current_time( 'mysql' ),
			'methods'      => $method,
			'status'       => 'pending',
			'recipient'    => $recipient,
			'subject'      => isset( $atts['subject'] ) ? (string) $atts['subject'] : '',
			'origin'       => $origin,
			'backtrace'    => $backtrace,
			'request'      => self::request_description(),
			'wp_mail_args' => $atts,
			'messages'     => [],
			'notes'        => '',
		];
	}

	/**
	 * Store a log entry.
	 *
	 * @param array $entry The entry.
	 *
	 * @return array The entry, with ID.
	 */
	private static function save( $entry ) {
		global $wpdb;
		$row             = $entry;
		$row['subject']  = mb_substr( $row['subject'], 0, 255 );
		$row['messages'] = (string) wp_json_encode( $entry['messages'], JSON_PARTIAL_OUTPUT_ON_ERROR );
		// Null (not wp_mail) is stored as empty string.
		$row['wp_mail_args'] = null === $entry['wp_mail_args'] ? '' : (string) wp_json_encode( $entry['wp_mail_args'], JSON_PARTIAL_OUTPUT_ON_ERROR );
		unset( $row['ID'] );

		if ( $entry['ID'] ) {
			$wpdb->update( self::table(), $row, [ 'ID' => $entry['ID'] ] );
		} else {
			$wpdb->insert( self::table(), $row );
			$entry['ID'] = (int) $wpdb->insert_id;
		}

		return $entry;
	}

	/**
	 * Determine who sent the email.
	 *
	 * @return string[] The origin (first frame outside WordPress mail code and this file) and the full backtrace.
	 */
	private static function backtrace() {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Debug feature.
		$frames = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 50 );
		$root   = wp_normalize_path( ABSPATH );
		$skip   = [
			wp_normalize_path( __FILE__ ),
			wp_normalize_path( __DIR__ . '/class-eemailer' ),
			wp_normalize_path( ABSPATH . WPINC . '/PHPMailer/' ),
			wp_normalize_path( ABSPATH . WPINC . '/class-wp-hook.php' ),
			wp_normalize_path( ABSPATH . WPINC . '/plugin.php' ),
			wp_normalize_path( ABSPATH . WPINC . '/pluggable.php' ),
		];

		$origin = '';
		$lines  = [];
		foreach ( $frames as $frame ) {
			$file  = isset( $frame['file'] ) ? wp_normalize_path( $frame['file'] ) : '';
			$where = $file ? str_replace( $root, '', $file ) . ':' . ( $frame['line'] ?? 0 ) : '[internal]';
			$what  = ( isset( $frame['class'] ) ? $frame['class'] . $frame['type'] : '' ) . $frame['function'] . '()';
			$line  = $where . '  ' . $what;

			$lines[] = $line;
			if ( ! $origin && $file && ! array_filter(
				$skip,
				static function ( $prefix ) use ( $file ) {
					return 0 === strpos( $file, $prefix );
				}
			) ) {
				$origin = $line;
			}
		}

		return [ $origin, implode( "\n", $lines ) ];
	}

	/**
	 * Describe the current request.
	 *
	 * @return string
	 */
	private static function request_description() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return 'WP-CLI: wp ' . implode( ' ', array_slice( (array) ( $GLOBALS['argv'] ?? [] ), 1 ) );
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$type   = '';
		if ( wp_doing_cron() ) {
			$type = ' [cron]';
		} elseif ( wp_doing_ajax() ) {
			$type = ' [ajax]';
		} elseif ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$type = ' [rest]';
		}

		return trim( $method . ' ' . $uri . $type );
	}

	/**
	 * Number of logged entries.
	 *
	 * @return int
	 */
	private static function count() {
		global $wpdb;
		static $count;
		if ( null === $count ) {
			$table = self::table();
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is ours.
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		}

		return $count;
	}

	/**
	 * Get a single entry.
	 *
	 * @param int $id The entry ID.
	 *
	 * @return array|null
	 */
	private static function get_entry( $id ) {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is ours.
		$entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE ID = %d", $id ), ARRAY_A );
		if ( ! $entry ) {
			return null;
		}
		$entry['messages']     = json_decode( $entry['messages'], true ) ?: [];
		$entry['wp_mail_args'] = '' === $entry['wp_mail_args'] ? null : json_decode( $entry['wp_mail_args'], true );

		return $entry;
	}

	/**
	 * The settings panel, rendered inside the Email Essentials settings form.
	 */
	public static function settings_panel() {
		$enabled = self::is_enabled();
		?>
		<div id="email-dont-send" class="postbox">
			<div class="postbox-header">
				<h2><?php esc_html_e( 'Debug: Don\'t send, just log', 'email-essentials' ); ?></h2>
			</div>
			<div class="inside">
				<div class="wpes-radio-list">
					<input type="hidden" name="settings[dont_send_panel]" value="1"/>
					<input
						<?php checked( $enabled ); ?>
						<?php disabled( self::is_forced() || ! self::is_supported() ); ?>
						type="checkbox" name="settings[dont_send]"
						value="1"
						id="dont_send"/>
					<label for="dont_send">
						<?php esc_html_e( 'Don\'t send any email, just log it and report success', 'email-essentials' ); ?>
					</label>
				</div>
				<div class="wpes-notice--warning">
					<?php esc_html_e( 'With this enabled, NO email leaves this website. Emails sent with wp_mail() and emails sent with PHPMailer directly (using the global $phpmailer object or a PHPMailer instance passing through phpmailer_init) are captured in full and logged. The sending code is told the email was sent successfully.', 'email-essentials' ); ?>
					<br/>
					<?php esc_html_e( 'Emails seen by both wp_mail() and PHPMailer are logged once, registering both methods.', 'email-essentials' ); ?>
					<br/>
					<strong class="warning"><?php esc_html_e( 'Disabling this feature deletes the log. This is a debugging tool, do not leave it enabled on a production website.', 'email-essentials' ); ?></strong>
					<?php if ( self::is_forced() ) { ?>
						<br/>
						<?php
						// translators: %s: the constant name.
						print wp_kses_post( sprintf( __( 'This setting is forced by the constant <code>%s</code> in wp-config.php.', 'email-essentials' ), self::CONSTANT ) );
						?>
					<?php } ?>
					<?php if ( ! self::is_supported() ) { ?>
						<br/>
						<?php esc_html_e( 'This feature requires WordPress 5.5 or later.', 'email-essentials' ); ?>
					<?php } ?>
				</div>
				<?php if ( $enabled && self::is_supported() ) { ?>
					<p>
						<a class="button button-primary" href="<?php print esc_url( self::log_url() ); ?>">
							<?php
							// translators: %d: number of logged emails.
							print esc_html( sprintf( _n( 'View the log (%d email)', 'View the log (%d emails)', self::count(), 'email-essentials' ), self::count() ) );
							?>
						</a>
					</p>
				<?php } ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save the setting. Nonce and capability are verified by the caller.
	 *
	 * @param array $raw_settings The posted settings.
	 */
	public static function settings_saved( $raw_settings ) {
		if ( empty( $raw_settings['dont_send_panel'] ) || self::is_forced() ) {
			return;
		}

		if ( ! empty( $raw_settings['dont_send'] ) ) {
			update_option( self::OPTION, 1 );
		} elseif ( get_option( self::OPTION ) ) {
			delete_option( self::OPTION );
			self::drop_table();
		}
	}

	/**
	 * Admin menu: the log panel, only present when the mode is enabled.
	 */
	public static function admin_menu() {
		add_submenu_page(
			'acato-email-essentials',
			Plugin::plugin_data()['Name'] . ' - ' . __( 'Don\'t send log', 'email-essentials' ),
			__( 'Don\'t send log', 'email-essentials' ) . ' <span class="awaiting-mod">' . (int) self::count() . '</span>',
			'manage_options',
			self::PAGE,
			[ self::class, 'admin_interface' ]
		);
	}

	/**
	 * Download an .eml file, or clear the log.
	 */
	public static function handle_admin_actions() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only routing, nonces are verified below.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( self::PAGE !== $page || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['dont_send_clear'] ) ) {
			check_admin_referer( 'acato_email_essentials_dont_send_clear' );
			global $wpdb;
			$table = self::table();
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is ours.
			$wpdb->query( "TRUNCATE TABLE `{$table}`" );
			wp_safe_redirect( self::log_url() );
			exit;
		}

		if ( isset( $_GET['download'], $_GET['message'] ) ) {
			$id    = (int) $_GET['download'];
			$index = (int) $_GET['message'];
			check_admin_referer( 'acato_email_essentials_dont_send_download_' . $id );
			$entry = self::get_entry( $id );
			if ( empty( $entry['messages'][ $index ]['eml'] ) ) {
				wp_die( esc_html__( 'Message not found.', 'email-essentials' ) );
			}
			header( 'Content-Type: message/rfc822' );
			header( 'Content-Disposition: attachment; filename=dont-send-' . $id . '-' . $index . '-' . sanitize_title( $entry['subject'] ) . '.eml' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw email download.
			print $entry['messages'][ $index ]['eml'];
			exit;
		}
	}

	/**
	 * Warn on every admin screen that no email is being sent.
	 */
	public static function admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Email Essentials: Don\'t send mode is active.', 'email-essentials' ); ?></strong>
				<?php esc_html_e( 'No email leaves this website; all emails are logged instead.', 'email-essentials' ); ?>
				<a href="<?php print esc_url( self::log_url() ); ?>">
					<?php
					// translators: %d: number of logged emails.
					print esc_html( sprintf( _n( 'View the log (%d email)', 'View the log (%d emails)', self::count(), 'email-essentials' ), self::count() ) );
					?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Admin bar link to the log, also on the front-end, for testing forms.
	 *
	 * @param \WP_Admin_Bar $admin_bar The admin bar.
	 */
	public static function admin_bar_menu( $admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$admin_bar->add_node(
			[
				'id'    => 'acato-email-essentials-dont-send',
				// translators: %d: number of logged emails.
				'title' => esc_html( sprintf( __( 'Don\'t send: %d emails logged', 'email-essentials' ), self::count() ) ),
				'href'  => self::log_url(),
				'meta'  => [ 'html' => '<style>#wp-admin-bar-acato-email-essentials-dont-send > .ab-item{background:#b32d2e !important;color:#fff !important}</style>' ],
			]
		);
	}

	/**
	 * The log panel.
	 */
	public static function admin_interface() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Uh uh uh! You didn\'t say the magic word!', 'email-essentials' ) );
		}
		wp_enqueue_style( 'email-essentials' );
		?>
		<style>
			.wpes-dont-send .methods code { margin-right: 2px; }
			.wpes-dont-send .status-failed, .wpes-dont-send .status-short-circuited, .wpes-dont-send .status-incomplete { color: #b32d2e; font-weight: bold; }
			.wpes-dont-send .status-not-transported { color: #996800; font-weight: bold; }
			.wpes-dont-send pre { white-space: pre-wrap; word-break: break-all; background: #f6f7f7; padding: 8px; max-height: 400px; overflow: auto; }
			.wpes-dont-send iframe { width: 100%; height: 600px; border: 1px solid #c3c4c7; background: #fff; }
			.wpes-dont-send table.meta th { width: 160px; text-align: left; vertical-align: top; }
		</style>
		<div class="wrap wpes-wrap wpes-dont-send">
			<?php
			Plugin::template_header( __( 'Don\'t send log', 'email-essentials' ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view.
			$id = isset( $_GET['entry'] ) ? (int) $_GET['entry'] : 0;
			if ( $id ) {
				self::render_entry( $id );
			} else {
				self::render_list();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render the list of logged emails.
	 */
	private static function render_list() {
		global $wpdb;
		$table = self::table();
		$total = self::count();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view.
		$paged = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is ours.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT ID, thedatetime, methods, status, recipient, subject, origin, request FROM `{$table}` ORDER BY ID DESC LIMIT %d OFFSET %d", self::PER_PAGE, ( $paged - 1 ) * self::PER_PAGE ), ARRAY_A );
		?>
		<p><?php esc_html_e( 'Method: wp_mail = seen by wp_mail(), phpmailer = captured at the PHPMailer transport. An email with only "phpmailer" bypassed wp_mail().', 'email-essentials' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'acato_email_essentials_dont_send_clear' ); ?>
			<input type="submit" name="dont_send_clear" class="button" value="<?php esc_attr_e( 'Clear log', 'email-essentials' ); ?>"/>
		</form>
		<br/>
		<table class="wp-list-table widefat fixed striped">
			<thead>
			<tr>
				<th style="width:50px">#</th>
				<th style="width:140px"><?php esc_html_e( 'Date', 'email-essentials' ); ?></th>
				<th style="width:150px"><?php esc_html_e( 'Method', 'email-essentials' ); ?></th>
				<th style="width:110px"><?php esc_html_e( 'Status', 'email-essentials' ); ?></th>
				<th><?php esc_html_e( 'Recipient', 'email-essentials' ); ?></th>
				<th><?php esc_html_e( 'Subject', 'email-essentials' ); ?></th>
				<th><?php esc_html_e( 'Sent by', 'email-essentials' ); ?></th>
				<th><?php esc_html_e( 'Request', 'email-essentials' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if ( ! $rows ) { ?>
				<tr><td colspan="8"><?php esc_html_e( 'No emails logged yet.', 'email-essentials' ); ?></td></tr>
			<?php } ?>
			<?php foreach ( $rows as $row ) { ?>
				<tr>
					<td><?php print (int) $row['ID']; ?></td>
					<td><?php print esc_html( $row['thedatetime'] ); ?></td>
					<td class="methods"><?php self::render_methods( $row['methods'] ); ?></td>
					<td class="status-<?php print esc_attr( $row['status'] ); ?>"><?php print esc_html( $row['status'] ); ?></td>
					<td><?php print esc_html( $row['recipient'] ); ?></td>
					<td><a href="<?php print esc_url( self::log_url( [ 'entry' => $row['ID'] ] ) ); ?>"><?php print esc_html( '' !== $row['subject'] ? $row['subject'] : __( '(no subject)', 'email-essentials' ) ); ?></a></td>
					<td><code><?php print esc_html( $row['origin'] ); ?></code></td>
					<td><?php print esc_html( $row['request'] ); ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php
		$pages = (int) ceil( $total / self::PER_PAGE );
		if ( $pages > 1 ) {
			print '<div class="tablenav"><div class="tablenav-pages">';
			print wp_kses_post(
				paginate_links(
					[
						'base'    => add_query_arg( 'paged', '%#%', self::log_url() ),
						'format'  => '',
						'current' => $paged,
						'total'   => $pages,
					]
				)
			);
			print '</div></div>';
		}
	}

	/**
	 * Render method badges.
	 *
	 * @param string $methods The methods, '+' separated.
	 */
	private static function render_methods( $methods ) {
		foreach ( explode( '+', $methods ) as $method ) {
			print '<code>' . esc_html( $method ) . '</code>';
		}
	}

	/**
	 * Render a single logged email.
	 *
	 * @param int $id The entry ID.
	 */
	private static function render_entry( $id ) {
		$entry = self::get_entry( $id );
		print '<p><a href="' . esc_url( self::log_url() ) . '">&larr; ' . esc_html__( 'Back to the log', 'email-essentials' ) . '</a></p>';
		if ( ! $entry ) {
			print '<p>' . esc_html__( 'Entry not found.', 'email-essentials' ) . '</p>';

			return;
		}
		?>
		<div class="postbox">
			<div class="postbox-header"><h2><?php print esc_html( '#' . $entry['ID'] . ' ' . $entry['subject'] ); ?></h2></div>
			<div class="inside">
				<table class="meta">
					<tr><th><?php esc_html_e( 'Date', 'email-essentials' ); ?></th><td><?php print esc_html( $entry['thedatetime'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Method', 'email-essentials' ); ?></th><td class="methods"><?php self::render_methods( $entry['methods'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Status', 'email-essentials' ); ?></th><td class="status-<?php print esc_attr( $entry['status'] ); ?>"><?php print esc_html( $entry['status'] ); ?></td></tr>
					<?php if ( '' !== $entry['notes'] ) { ?>
						<tr><th><?php esc_html_e( 'Notes', 'email-essentials' ); ?></th><td><?php print nl2br( esc_html( $entry['notes'] ) ); ?></td></tr>
					<?php } ?>
					<tr><th><?php esc_html_e( 'Recipient', 'email-essentials' ); ?></th><td><?php print esc_html( $entry['recipient'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Request', 'email-essentials' ); ?></th><td><?php print esc_html( $entry['request'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Sent by', 'email-essentials' ); ?></th><td><code><?php print esc_html( $entry['origin'] ); ?></code></td></tr>
				</table>
				<details>
					<summary><?php esc_html_e( 'Full backtrace', 'email-essentials' ); ?></summary>
					<pre><?php print esc_html( $entry['backtrace'] ); ?></pre>
				</details>
				<?php if ( null !== $entry['wp_mail_args'] ) { ?>
					<details>
						<summary><?php esc_html_e( 'wp_mail() arguments (after all filters)', 'email-essentials' ); ?></summary>
						<pre><?php print esc_html( (string) wp_json_encode( $entry['wp_mail_args'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre>
					</details>
				<?php } ?>
			</div>
		</div>
		<?php
		if ( count( $entry['messages'] ) > 1 ) {
			print '<p>' . esc_html__( 'PHPMailer transmitted this email as multiple messages (e.g. one per recipient, the "SingleTo" setting).', 'email-essentials' ) . '</p>';
		}
		foreach ( $entry['messages'] as $index => $message ) {
			self::render_message( $entry, $index, $message );
		}
	}

	/**
	 * Render a single captured message.
	 *
	 * @param array $entry   The log entry.
	 * @param int   $index   The message index.
	 * @param array $message The message.
	 */
	private static function render_message( $entry, $index, $message ) {
		$fields       = [
			[ __( 'Captured from', 'email-essentials' ), $message['captured_by'] ],
			[ __( 'PHPMailer class', 'email-essentials' ), $message['mailer_class'] ],
			[ __( 'Would have used', 'email-essentials' ), $message['transport'] ],
			[ __( 'From', 'email-essentials' ), $message['from'] ],
			[ __( 'Sender (Return-Path)', 'email-essentials' ), $message['sender'] ],
			[ __( 'To', 'email-essentials' ), implode( ', ', $message['to'] ) ],
			[ __( 'Cc', 'email-essentials' ), implode( ', ', $message['cc'] ) ],
			[ __( 'Bcc', 'email-essentials' ), implode( ', ', $message['bcc'] ) ],
			[ __( 'Reply-To', 'email-essentials' ), implode( ', ', $message['reply_to'] ) ],
			[ __( 'Subject', 'email-essentials' ), $message['subject'] ],
			[ __( 'Content-Type', 'email-essentials' ), $message['content_type'] ],
		];
		$download_url = wp_nonce_url(
			self::log_url(
				[
					'download' => $entry['ID'],
					'message'  => $index,
				]
			),
			'acato_email_essentials_dont_send_download_' . $entry['ID']
		);
		?>
		<div class="postbox">
			<div class="postbox-header">
				<h2>
					<?php
					// translators: %d: message number.
					print esc_html( sprintf( __( 'Message %d, as captured by PHPMailer', 'email-essentials' ), $index + 1 ) );
					?>
				</h2>
			</div>
			<div class="inside">
				<table class="meta">
					<?php foreach ( $fields as [ $label, $value ] ) { ?>
						<?php
						if ( '' === (string) $value ) {
							continue;
						}
						?>
						<tr><th><?php print esc_html( $label ); ?></th><td><?php print esc_html( $value ); ?></td></tr>
					<?php } ?>
					<?php foreach ( $message['attachments'] as $attachment ) { ?>
						<tr>
							<th><?php esc_html_e( 'Attachment', 'email-essentials' ); ?></th>
							<td><?php print esc_html( sprintf( '%s (%s, %s, %s)', $attachment['name'], $attachment['type'], $attachment['disposition'], Plugin::nice_size( $attachment['size'] ) ) ); ?></td>
						</tr>
					<?php } ?>
				</table>
				<p><a class="button" href="<?php print esc_url( $download_url ); ?>"><?php esc_html_e( 'Download .eml', 'email-essentials' ); ?></a></p>
				<?php
				// After preSend(), PHPMailer reports multipart/* for HTML with alternative body or attachments.
				$is_html = false !== stripos( $message['content_type'], 'html' ) || ( 'text/plain' !== $message['content_type'] && wp_strip_all_tags( $message['body'] ) !== $message['body'] );
				if ( $is_html ) {
					?>
					<iframe sandbox="" srcdoc="<?php print esc_attr( $message['body'] ); ?>" title="<?php esc_attr_e( 'Email body', 'email-essentials' ); ?>"></iframe>
				<?php } else { ?>
					<pre><?php print esc_html( $message['body'] ); ?></pre>
				<?php } ?>
				<?php if ( '' !== (string) $message['alt_body'] ) { ?>
					<details>
						<summary><?php esc_html_e( 'Plain text alternative', 'email-essentials' ); ?></summary>
						<pre><?php print esc_html( $message['alt_body'] ); ?></pre>
					</details>
				<?php } ?>
				<details>
					<summary><?php esc_html_e( 'Raw message (EML)', 'email-essentials' ); ?></summary>
					<pre><?php print esc_html( $message['eml'] ); ?></pre>
				</details>
			</div>
		</div>
		<?php
	}
}

/*
 * The capturing classes extend PHPMailer, declaring them loads PHPMailer. Only do that when the mode is active.
 */
if ( Dont_Send::is_supported() && Dont_Send::is_enabled() ) {
	/**
	 * Replacement for the global $phpmailer: builds the complete message, but captures it instead of transmitting it.
	 * Extends EEMailer, so all Email Essentials behaviour (like SingleTo splitting) remains intact.
	 */
	class Dont_Send_Mailer extends EEMailer {
		/**
		 * Overloaded transport: capture, don't send.
		 *
		 * @return bool Always true.
		 */
		public function postSend() {
			Dont_Send::capture( $this, 'global', $this->getSentMIMEMessage() );

			return true;
		}
	}

	/**
	 * Fake SMTP connection for PHPMailer instances that are not ours: accepts everything, transmits nothing.
	 */
	class Dont_Send_SMTP extends SMTP {
		/**
		 * The mailer using this connection.
		 *
		 * @var PHPMailer
		 */
		private $mailer;

		/**
		 * The transport the mailer was configured with before we took over.
		 *
		 * @var string
		 */
		private $original_transport;

		/**
		 * Constructor.
		 *
		 * @param PHPMailer $mailer             The mailer using this connection.
		 * @param string    $original_transport The original transport.
		 */
		public function __construct( $mailer, $original_transport ) {
			$this->mailer             = $mailer;
			$this->original_transport = $original_transport;
		}

		/**
		 * Capture the message instead of transmitting it.
		 *
		 * @param string $msg_data The complete message.
		 *
		 * @return bool
		 */
		public function data( $msg_data ) {
			Dont_Send::capture( $this->mailer, 'phpmailer_init', $msg_data, $this->original_transport );

			return true;
		}

		// phpcs:disable Squiz.Commenting.FunctionComment.Missing,Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed,WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- Fakes, see parent.
		public function connect( $host, $port = null, $timeout = 30, $options = [] ) {
			return true;
		}

		public function connected() {
			return true;
		}

		public function startTLS() {
			return true;
		}

		public function authenticate( $username, $password, $authtype = null, $OAuth = null ) {
			return true;
		}

		public function hello( $host = '' ) {
			return true;
		}

		public function mail( $from ) {
			return true;
		}

		public function recipient( $address, $dsn = '' ) {
			return true;
		}

		public function xclient( array $vars ) {
			return true;
		}

		public function reset() {
			return true;
		}

		public function noop() {
			return true;
		}

		public function quit( $close_on_error = true ) {
			return true;
		}

		public function close() {
		}

		public function client_send( $data, $command = '' ) {
			return 0;
		}

		public function getServerExt( $name ) {
			return true;
		}

		public function getLastTransactionID() {
			return 'dont-send';
		}
		// phpcs:enable
	}
}

Dont_Send::init();
