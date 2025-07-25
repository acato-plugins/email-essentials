<?php
/**
 * The main class.
 *
 * @package WP_Email_Essentials
 */

namespace Acato\Email_Essentials;

use Exception;
use WP_Comment;
use WPCF7_ContactForm;

/**
 * The main plugin class.
 */
class Plugin {
	/**
	 * The plugin slug.
	 *
	 * @const string
	 */
	const SLUG = 'email-essentials/email-essentials.php';

	/**
	 * Holds a message to show in the admin panel.
	 *
	 * @var string
	 */
	public static $message = '';

	/**
	 * Holds an error-message to show in the admin panel.
	 *
	 * @var string
	 */
	public static $error = '';

	/**
	 * Holds debug information.
	 *
	 * @var mixed
	 */
	public static $debug;

	/**
	 * List of supported encodings.
	 */
	const ENCODINGS = 'utf-8,utf-16,utf-32,latin-1,iso-8859-1';

	/**
	 * Constructor.
	 */
	public function __construct() {
		self::$message = get_transient( 'wpes_message' ) ?: '';
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );

		self::init();
	}

	/**
	 * Plugin data
	 */
	public static function plugin_data() {
		static $plugin_data;
		if ( ! $plugin_data ) {
			if ( ! is_admin() ) {
				$plugin_data = get_transient( 'wpes_plugin_data' );
			}
			if ( ! $plugin_data ) {
				if ( ! function_exists( 'get_plugin_data' ) ) {
					require_once ABSPATH . '/wp-admin/includes/plugin.php';
				}
				$plugin_data = get_plugin_data( __DIR__ . '/../email-essentials.php' );
			}
			set_transient( 'wpes_plugin_data', $plugin_data, WEEK_IN_SECONDS );
		}

		if ( empty( $plugin_data['LongName'] ) ) {
			$plugin_data['LongName'] = $plugin_data['Name'];
			$plugin_data['Name']     = str_replace( 'WordPress', 'WP', $plugin_data['Name'] );
		}

		return $plugin_data;
	}

	/**
	 * The main initialisation.
	 */
	public static function init() {

		add_filter( 'wp_mail', [ self::class, 'jit_overload_phpmailer' ], ~PHP_INT_MAX );

		add_filter( 'wp_mail', [ self::class, 'start_log' ], -100000000 );
		add_action( 'wp_mail_succeeded', [ self::class, 'end_log' ], 10000000001 ); // prio of HIST + 1.
		add_action( 'wp_mail_failed', [ self::class, 'end_log' ], 10000000001 ); // prio of HIST + 1.

		add_filter( 'wp_mail', [ self::class, 'alternative_to' ] );

		add_action( 'wp_ajax_nopriv_wpes_get_ip', [ self::class, 'ajax_get_ip' ] );

		add_action(
			'plugins_loaded',
			function () {
				load_plugin_textdomain( 'email-essentials', false, plugin_basename( dirname( __DIR__ ) ) . '/lang' );
			}
		);
		add_action(
			'admin_enqueue_scripts',
			function () {
				wp_register_style( 'email-essentials', plugins_url( 'public/styles/wpes-admin.css', __DIR__ ), [], filemtime( __DIR__ . '/../public/styles/wpes-admin.css' ), 'all' );
				wp_register_script( 'email-essentials', plugins_url( 'public/scripts/wpes-admin.js', __DIR__ ), [], filemtime( __DIR__ . '/../public/scripts/wpes-admin.js' ), true );
			}
		);

		$config = self::get_config();
		add_action( 'phpmailer_init', [ self::class, 'action_phpmailer_init' ] );
		if ( $config['is_html'] ) {
			add_filter(
				'wp_mail_content_type',
				function () {
					return 'text/html';
				}
			);
			add_filter(
				'wp_mail_charset',
				function () {
					return 'UTF-8';
				}
			);
			add_filter(
				'wpcf7_mail_html_header',
				[
					self::class,
					'wpcf7_mail_html_header',
				],
				~PHP_INT_MAX
			);
			add_filter(
				'wpcf7_mail_html_footer',
				[
					self::class,
					'wpcf7_mail_html_footer',
				],
				~PHP_INT_MAX
			);

			// Disable GravityForms HTML wrapper, because we want our own.
			add_filter(
				'gform_html_message_template_pre_send_email',
				function () {
					return '{message}';
				}
			);
		}

		// set default from email and from name.
		if ( $config['from_email'] ) {
			add_filter( 'wp_mail_from', [ self::class, 'filter_wp_mail_from' ], 9999 );
		}
		if ( $config['from_name'] ) {
			add_filter( 'wp_mail_from_name', [ self::class, 'filter_wp_mail_from_name' ], 9999 );
		}

		add_filter( 'wp_mail', [ self::class, 'action_wp_mail' ], PHP_INT_MAX - 1000 );

		add_action( 'admin_menu', [ self::class, 'admin_menu' ], 9 );

		add_action( 'admin_footer', [ self::class, 'maybe_inject_admin_settings' ] );

		add_filter( 'cfdb_form_data', [ self::class, 'correct_cfdb_form_data_ip' ] );

		add_filter( 'comment_notification_headers', [ self::class, 'correct_comment_from' ], 11, 2 );

		add_filter(
			'comment_moderation_recipients',
			[ self::class, 'correct_moderation_to' ],
			~PHP_INT_MAX,
			2
		);
		add_filter(
			'comment_notification_recipients',
			[ self::class, 'correct_comment_to' ],
			~PHP_INT_MAX,
			2
		);

		add_action( 'after_setup_theme', function () {
			self::mail_key_registrations();
		} );

		// Maybe process settings.
		add_action( 'init', [ self::class, 'save_admin_settings' ] );

		// Load add-ons.
		History::instance();
		Queue::instance();

		add_filter( 'plugin_action_links', [ self::class, 'plugin_actions' ], 10, 2 );

		// Public API filters.
		add_filter( 'email_essentials_minify_css', [ self::class, 'minify_css' ] );

		add_action( 'wp_ajax_dismiss_wpes_deprecation_notice', [ self::class, 'dismiss_deprecation_notice' ] );
	}

	/**
	 * Just-in-time overloading of the phpMailer object. Chances are, the object has not been created yet, but in any case,
	 * we create iw with our own class (but only on WordPress 5.5 or higher) so we can overload the Send method.
	 *
	 * @param mixed $passthru The wp_mail filter result. We don't care about this, but it is a filter, so we have no choice but to passthru.
	 *
	 * @return mixed
	 */
	public static function jit_overload_phpmailer( $passthru ) {
		global $phpmailer, $wp_version;
		if ( version_compare( $wp_version, '5.4.99', '<' ) ) {
			return $passthru;
		}

		// TAKEN FROM wp-includes/pluggable.php.
		// Changed class name, so we can overload the Send method.
		if ( ! ( $phpmailer instanceof WPES_PHPMailer ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			// @phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Give me a different way to do this, and I will gladly refactor.
			$phpmailer = new WPES_PHPMailer( true );

			$phpmailer::$validator = static function ( $email ) {
				return (bool) is_email( $email );
			};
		}

		return $passthru;
	}

	/**
	 * Implementation of filter plugin_action_links.
	 *
	 * @param string[] $links A list of links (HTML) shown at the plugin row.
	 * @param string   $file  The plugin dir relative to wp-content/plugins.
	 *
	 * @return string[]
	 */
	public static function plugin_actions( $links, $file ) {
		if ( self::SLUG === $file && function_exists( 'admin_url' ) ) {
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- we want to use the WordPress default translation here.
			$settings_link = '<a href="' . admin_url( 'admin.php?page=wp-email-essentials' ) . '">' . _x( 'Settings', 'translators: ignore this.' ) . '</a>';
			array_unshift( $links, $settings_link ); // before other links.
		}

		return $links;
	}

	/**
	 * Get the root path to the website. This is NOT ABSPATH if WordPress is in a subdirectory.
	 *
	 * @return string
	 */
	public static function root_path() {
		static $root_path;

		if ( ! $root_path ) {
			$wp_path_rel_to_home = self::get_wp_subdir();
			if ( '' !== $wp_path_rel_to_home ) {
				$pos       = strripos( str_replace( '\\', '/', ABSPATH ), trailingslashit( $wp_path_rel_to_home ) );
				$home_path = substr( ABSPATH, 0, $pos );
				$home_path = trailingslashit( $home_path );
			} else {
				$home_path = ABSPATH;
			}

			$root_path = self::nice_path( $home_path );
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
	 * Suggested safe path for hidden data
	 *
	 * @param string $item A directory name.
	 */
	public static function suggested_safe_path_for( $item ) {
		$root             = self::root_path(); // The public_html folder.
		$parent           = dirname( $root ); // the outside-webspace-safe-folder.
		$might_be_current = basename( $parent );
		if ( 'current' === $might_be_current ) {
			// probably a deployer set-up. Go one up.
			$parent = dirname( $parent );
		}

		return $parent . '/' . $item;
	}

	/**
	 * Check if the path is in the webroot.
	 *
	 * @param string $path A path.
	 *
	 * @return bool
	 */
	public static function path_is_in_web_root( $path ) {
		return 0 === strpos( realpath( $path ), realpath( self::root_path() ) );
	}

	/**
	 * Get the subdirectory WordPress is sitting in.
	 *
	 * @return string
	 */
	public static function get_wp_subdir() {
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
	public static function nice_path( $path ) {
		// Turn \ into / .
		$path = str_replace( '\\', '/', $path );
		// Remove "current" instances.
		$path = str_replace( '/./', '/', $path );
		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// @todo: remove  ../somethingotherthandotdot/ .

		return $path;
	}

	/**
	 * Get the default sender email address like WordPress generates them; wordpress@domain.tld .
	 *
	 * @return string
	 */
	public static function get_wordpress_default_emailaddress() {
		$host    = wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
		$host    = preg_replace( '/^www\./', '', $host );
		$wpemail = 'wordpress@' . $host;
		self::log( 'wp-email ' . $wpemail );

		return $wpemail;
	}

	/**
	 * Filter callback for comment email hearders.
	 *
	 * @param string $mail_headers The headers.
	 * @param int    $comment_id   (unused) the Comment ID.
	 *
	 * @return string
	 */
	public static function correct_comment_from( $mail_headers, $comment_id ) {
		$unused = $comment_id;

		$u            = wp_get_current_user();
		$mail_headers = array_map( 'trim', explode( "\n", $mail_headers ) );
		foreach ( $mail_headers as $i => $header ) {
			if ( preg_match( '/^(From|Reply-To):[ \t]*(.+)$/i', $header, $m ) ) {
				$email = $m[2];
				$email = self::rfc_decode( $email );
				if ( $email['email'] === $email['name'] ) {
					$email['name'] = $u->ID ? $u->display_name : ( $email['name'] ?: __( 'anonymous', 'email-essentials' ) );
				}
				if ( $u->ID && $u->user_login === $email['name'] ) {
					$email['name'] = $u->display_name;
				}
				if ( $u->ID && self::get_wordpress_default_emailaddress() === $email['email'] ) {
					$email['email'] = $u->user_email;
				}
				$mail_headers[ $i ] = $m[1] . ': ' . self::rfc_encode( $email );
			}
		}

		return implode( "\r\n", $mail_headers );
	}

	/**
	 * Fix content in CD72DB plugin.
	 *
	 * @param WPCF7_ContactForm $cf7 The contact form.
	 *
	 * @return mixed
	 */
	public static function correct_cfdb_form_data_ip( $cf7 ) {
		// CF7 to DB tries variable X_FORWARDED_FOR which is never in use, Apache sets HTTP_X_FORWARDED_FOR
		// use our own method to get the remote_addr.
		$cf7->ip = self::server_remote_addr();

		return $cf7;
	}

	/**
	 * Get remote address, in any form it can be presented.
	 *
	 * @param bool $return_htaccess_variable Return the value (false) or the variable it was found in (true).
	 *
	 * @return string
	 */
	public static function server_remote_addr( $return_htaccess_variable = false ) {
		$possibilities = [
			'HTTP_CF_CONNECTING_IP' => 'HTTP:CF-CONNECTING-IP',
			'HTTP_X_FORWARDED_FOR'  => 'HTTP:X-FORWARDED-FOR',
			'REMOTE_ADDR'           => false,
		];
		foreach ( $possibilities as $option => $htaccess_variable ) {
			if ( isset( $_SERVER[ $option ] ) && trim( $_SERVER[ $option ] ) ) {
				$ip = explode( ',', $_SERVER[ $option ] );

				return $return_htaccess_variable ? $htaccess_variable : end( $ip );
			}
		}

		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Implementation of filter  wp_mail .
	 *
	 * @param array $wp_mail Array with wp_mail data. ('to', 'subject', 'message', 'headers', 'attachments') .
	 *
	 * @return array
	 */
	public static function action_wp_mail( $wp_mail ) {
		if ( [] === $wp_mail ) {
			return $wp_mail;
		}
		if ( ! $wp_mail['to'] ) {
			return $wp_mail;
		}
		if ( ! $wp_mail['subject'] ) {
			return $wp_mail;
		}
		if ( ! $wp_mail['message'] ) {
			return $wp_mail;
		}

		return self::patch_wp_mail( $wp_mail );
	}

	/**
	 * Change the WP_Mail data array to suit the settings.
	 *
	 * @param array $wp_mail Array with wp_mail data. ('to', 'subject', 'message', 'headers', 'attachments') .
	 *
	 * @return array
	 */
	public static function patch_wp_mail( $wp_mail ) {

		$config = self::get_config();

		self::wp_mail_from( $config['from_email'] );
		self::wp_mail_from_name( $config['from_name'] );

		$all_headers = [];

		if ( is_string( $wp_mail['headers'] ) ) {
			$wp_mail['headers'] = explode( "\n", $wp_mail['headers'] );
		}
		if ( ! is_array( $wp_mail['headers'] ) ) {
			$wp_mail['headers'] = [];
		}

		self::log( __LINE__ . ' raw headers' );
		self::log( wp_json_encode( $wp_mail['headers'] ) );

		$header_index = [];
		foreach ( $wp_mail['headers'] as $i => $header ) {
			if ( preg_match( '/([^:]+):(.*)$/U', $header, $match ) ) {
				$all_headers[ strtolower( trim( $match[1] ) ) ]  = $match[2];
				$header_index[ strtolower( trim( $match[1] ) ) ] = $i;
			}
		}

		if ( isset( $all_headers['from'] ) ) {
			self::log( __LINE__ . ' headers has FROM: ' . $all_headers['from'] );
			$from = self::rfc_decode( $all_headers['from'] );
			self::log( __LINE__ . ' decoded:' );
			self::log( wp_json_encode( $from ) );
			if ( $from['email'] && self::get_wordpress_default_emailaddress() !== $from['email'] ) {
				self::log( __LINE__ . ' set from mail' );
				self::wp_mail_from( $from['email'] );
			}
			if ( $from['name'] ) {
				self::log( __LINE__ . ' set from name' );
				self::wp_mail_from_name( $from['name'] );
			}
		}

		if ( ! array_key_exists( 'from', $header_index ) ) {
			$header_index['from'] = count( $header_index );
		}
		$wp_mail['headers'][ $header_index['from'] ] = 'From: "' . self::wp_mail_from_name() . '" <' . self::wp_mail_from() . '>';

		self::log( __LINE__ . ' headers now:' );
		self::log( wp_json_encode( $wp_mail['headers'] ) );

		if ( ! array_key_exists( 'reply-to', $header_index ) ) {
			self::log( __LINE__ . ' Adding REPLY-TO:' );
			$header_index['reply-to']                        = count( $header_index );
			$wp_mail['headers'][ $header_index['reply-to'] ] = 'Reply-To: ' . self::wp_mail_from_name() . ' <' . self::wp_mail_from() . '>';
		} else {
			self::log( __LINE__ . ' Already have REPLY-TO:' );
		}

		self::log( __LINE__ . ' headers now:' );
		self::log( wp_json_encode( $wp_mail['headers'] ) );

		if ( $config['make_from_valid'] ) {
			self::log( __LINE__ . ' Validifying FROM:' );
			$new_from = self::a_valid_from( self::wp_mail_from(), $config['make_from_valid'] );
			self::log_message( "Validified FROM: $new_from" );
			self::wp_mail_from( $new_from );
			$wp_mail['headers'][ $header_index['from'] ] = 'From: "' . self::wp_mail_from_name() . '" <' . $new_from . '>';
		}

		self::log( __LINE__ . ' headers now:' );
		self::log( wp_json_encode( $wp_mail['headers'] ) );

		return $wp_mail;
	}

	/**
	 * Transform invalid from-address to a valid one.
	 *
	 * @param string|array $invalid_from An invalid from-address.
	 * @param string       $method       The method of validating it.
	 *
	 * @return mixed|string
	 */
	public static function a_valid_from( $invalid_from, $method ) {
		$url    = get_bloginfo( 'url' );
		$host   = wp_parse_url( $url, PHP_URL_HOST );
		$host   = preg_replace( '/^www\d*\./', '', $host );
		$config = self::get_config();

		if ( ! self::i_am_allowed_to_send_in_name_of( $invalid_from ) ) {
			self::log_message( "I am not allowed to send in name of $invalid_from" );
			switch ( $method ) {
				case '-at-':
					$translation = [
						'@' => '-at-',
						'.' => '-dot-',
					];

					$return = strtr( $invalid_from, $translation );

					$new_from = $return . '@' . $host;
					self::log_message( "(-at- method) Transformed $invalid_from to $new_from" );

					return $new_from;
				case 'default':
					$defmail = self::wp_mail_from( $config['from_email'] );
					if ( self::i_am_allowed_to_send_in_name_of( $defmail ) ) {
						$new_from = $defmail;
						self::log_message( "(default method) Transformed $invalid_from to $new_from" );

						return $new_from;
					} // if test fails, bleed through to noreply, so leave this order in tact!
				case 'noreply':
					$new_from = 'noreply@' . $host;
					self::log_message( "(noreply method) Transformed $invalid_from to $new_from" );

					return $new_from;
				default:
					self::log_message( "Email kept in tact, $invalid_from" );

					return $invalid_from;
			}
		}

		self::log_message( "I am allowed to send with this address, kept in tact, $invalid_from" );

		return $invalid_from;
	}

	/**
	 * Get the domain of an email address.
	 *
	 * @param string $email The email address.
	 *
	 * @return string
	 */
	public static function get_domain( $email ) {
		if ( preg_match( '/@(.+)$/', $email, $sending_domain ) ) {
			$sending_domain = $sending_domain[1];
		} else {
			$sending_domain = '';
		}

		return $sending_domain;
	}

	/**
	 * Get SPF record for the domain of the email given.
	 *
	 * @param string $email   The email address.
	 * @param bool   $fix     If true; give a fixed SPF record.
	 * @param bool   $as_html If true, return as richly formatted HTML.
	 *
	 * @return string
	 */
	public static function get_spf( $email, $fix = false, $as_html = false ) {
		static $lookup;
		if ( ! $lookup ) {
			$lookup = [];
		}

		// Domain.
		$sending_domain = self::get_domain( $email );
		if ( '' === $sending_domain ) {
			return false; // invalid email.
		}

		// IP.
		$sending_server   = self::get_sending_ip();
		$sending_server_4 = false;
		switch ( true ) {
			case (bool) filter_var( trim( $sending_server ), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ):
				$ip = 'ip4';
				break;
			case (bool) filter_var( trim( $sending_server ), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ):
				$ip = 'ip6';
				// Also get IPv4.
				$sending_server_4 = self::get_sending_ip( true );
				break;
			default:
				$ip = false;
		}
		if ( ! $ip ) {
			return false;
		}

		// Cached?
		if ( ! isset( $lookup[ $sending_domain ] ) ) {
			$dns = self::dns_get_record( $sending_domain, DNS_TXT );
			foreach ( $dns as $record ) {
				if ( false !== strpos( $record['txt'], 'v=spf1' ) ) {
					$lookup[ $sending_domain ] = $record['txt'];
					break;
				}
			}
		}

		// Still not cached? make sure we return a string.
		if ( ! isset( $lookup[ $sending_domain ] ) ) {
			$lookup[ $sending_domain ] = '';
		}

		// The SPF record to work with.
		$spf = $lookup[ $sending_domain ];

		// Is the IP valid according the SPF?
		$sending_server_valid   = self::validate_ip_listed_in_spf( $sending_domain, $sending_server );
		$sending_server_4_valid = $sending_server_4 ? self::validate_ip_listed_in_spf( $sending_domain, $sending_server_4 ) : true; // assume valid so we don't list it as invalid.
		if ( $as_html ) {
			$spf = self::highlight_spf( $spf, $sending_server_valid, 'green' );
			if ( $sending_server_4 ) {
				$spf = self::highlight_spf( $spf, $sending_server_4_valid, 'green' );
			}
		}
		if ( $fix ) {
			// Get a default new SPF record.
			if ( ! $spf ) {
				$spf = 'v=spf1 a mx -all';
			}
			if ( ! $sending_server_valid ) {
				$spf = self::inject_in_spf( $spf, $ip . ':' . $sending_server );
			}
			if ( $sending_server_4 && ! $sending_server_4_valid ) {
				$spf = self::inject_in_spf( $spf, 'ip4:' . $sending_server_4 );
			}
			if ( $as_html ) {
				if ( ! $sending_server_valid ) {
					$spf = self::highlight_spf( $spf, $ip . ':' . $sending_server, 'red' );
				}
				if ( $sending_server_4 && ! $sending_server_4_valid ) {
					$spf = self::highlight_spf( $spf, 'ip4:' . $sending_server_4, 'red' );
				}
			}
		}

		return $spf;
	}

	/**
	 * Highlight a part of the SPF record.
	 *
	 * @param string $spf             The SPF record.
	 * @param string $highlight_text  The section to highlight.
	 * @param string $highlight_color Color red or green.
	 *
	 * @return string
	 */
	private static function highlight_spf( $spf, $highlight_text, $highlight_color ) {
		$spf = explode( ' ', $spf );
		foreach ( $spf as &$segment ) {
			if ( $segment === $highlight_text ) {
				$segment = sprintf( '<strong style="color:%s">%s</strong>', $highlight_color, $segment );
			}
		}

		return implode( ' ', $spf );
	}

	/**
	 * Inject a part into an SPF record.
	 *
	 * @param string $spf    The SPF record.
	 * @param string $inject The section to inject.
	 *
	 * @return string
	 */
	private static function inject_in_spf( $spf, $inject ) {
		$_spf  = explode( ' ', strtolower( $spf ) );
		$after = [ 'mx', 'a', 'v=spf1' ];
		$match = array_intersect( $after, $_spf );
		$match = $match[0] ?? false;
		if ( $match ) {
			$spf = trim( preg_replace( '/ ' . $match . ' /i', ' ' . $match . ' ' . $inject . ' ', ' ' . $spf . ' ', 1 ) );
		} elseif ( preg_match( '/([^ ]+all)/i', $spf ) ) {
			$spf = trim( preg_replace( '/([^ ]+all)/i', $inject . ' \1', $spf, 1 ) );
		} else {
			$spf = $spf . ' ' . $inject;
		}

		return $spf;
	}

	/**
	 * Test: I (this server) is allowed to send in name of given email address.
	 *
	 * @param string $email The email address to check.
	 *
	 * @return bool
	 */
	public static function i_am_allowed_to_send_in_name_of( $email ) {
		$config = self::get_config();

		self::log_message( 'Checking if I am allowed to send in name of ' . $email );

		if ( 'when_sender_not_as_set' === $config['make_from_valid_when'] ) {
			self::log_message( "Setting is 'when_sender_not_as_set', so I am allowed if $email is identical to $config[from_email]." );

			return $config['from_email'] === $email;
		}

		if ( ! $config['spf_lookup_enabled'] ) {
			// we tried and failed less than a day ago.
			// do not try again.
			self::log_message( "SPF Check is disabled, test based on domain-name." );

			return self::this_email_matches_website_domain( $email );
		}

		// try an SPF record.

		$sending_domain = [];
		preg_match( '/@(.+)$/', $email, $sending_domain );
		if ( [] === $sending_domain ) {
			return false; // invalid email.
		}
		$sending_server = self::get_sending_ip();

		$result = self::validate_ip_listed_in_spf( $sending_domain[1], $sending_server );
		self::log_message( "SPF Check result: " . ( $result ? 'spf allows' : 'spf does not allow' ) );

		return $result;
	}

	/**
	 * Get the sending IP address.
	 *
	 * @param bool $force_ip4 Get the IPv4 address, even if IPv6 is available.
	 *
	 * @return string
	 */
	public static function get_sending_ip( $force_ip4 = false ) {
		$sender_is_localhost = false;
		if ( 'definitely' === self::sender_validation_works() ) {
			$sender_is_localhost = true;
		}

		if ( ! $sender_is_localhost ) {
			// Get an IP for the configured SMTP server.
			$smtp_server = self::get_config()['smtp']['host'] ?? '';
			// This cannot be empty, because sender_validation_works() would have returned 'definitely' if it was.
			self::start_ip_trace();
			$remote_ip = self::dns_get_record_with_recurse( $smtp_server, $force_ip4 ? DNS_A | DNS_CNAME : DNS_AAAA | DNS_A | DNS_CNAME );
			$trace     = self::get_ip_trace();
			$tail      = end( $trace );
			// Clean the trace.
			self::start_ip_trace();

			return $remote_ip;
		}

		static $sending_ip;
		if ( ! $sending_ip ) {
			$sending_ip = [];
		}
		$ipkey = $force_ip4 ? 'force_ip4' : 'auto';
		if ( $sending_ip && ! empty( $sending_ip[ $ipkey ] ) ) {
			return $sending_ip[ $ipkey ];
		}
		$url = admin_url( 'admin-ajax.php' );
		$ip  = false; // start with unknown.

		/**
		 * Services:
		 *
		 * Will use a defined service ($see self::get_ip_service()) to get the IP address.
		 * Will use a callback to the website itself, if a service is unavailable.
		 */
		$ipv4_service = self::get_ip_service( 'ipv4' );
		if ( ! $ip && $force_ip4 && $ipv4_service ) {
			$ip = wp_remote_retrieve_body(
				wp_remote_get(
					$ipv4_service,
					[
						'httpversion' => '1.1',
						'referer'     => $_SERVER['HTTP_REFERER'] ?? get_bloginfo( 'url' ),
						'user-agent'  => sprintf( 'WordPress/%s/WP-Email-Essentials/%s', get_bloginfo( 'version' ), self::get_wpes_version() ),
					]
				)
			);
			$ip = filter_var( trim( $ip ), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
		}

		$dual_stack_service = self::get_ip_service( 'dual-stack' ) ?: self::get_ip_service( 'ipv6' ) ?: self::get_ip_service( 'ipv4' );
		if ( ! $ip && $dual_stack_service ) {
			$ip = wp_remote_retrieve_body(
				wp_remote_get(
					$dual_stack_service,
					[
						'httpversion' => '1.1',
						'referer'     => $_SERVER['HTTP_REFERER'] ?? get_bloginfo( 'url' ),
						'user-agent'  => sprintf( 'WordPress/%s/WP-Email-Essentials/%s', get_bloginfo( 'version' ), self::get_wpes_version() ),
					]
				)
			);
			if ( '0.0.0.0' === $ip ) {
				$ip = false;
			}
		}

		// If we have no IP yet, try to get it from the website itself.
		if ( ! $ip ) {
			$ip = wp_remote_retrieve_body( wp_remote_get( $url . '?action=wpes_get_ip' ) );

			$ip_type_filter = $force_ip4 ? FILTER_FLAG_IPV4 : ( FILTER_FLAG_IPV6 | FILTER_FLAG_IPV4 );
			if ( ! filter_var( trim( $ip ), FILTER_VALIDATE_IP, $ip_type_filter ) ) {
				$ip = false;
			}
		}

		// If all else fails, use the configured server address.
		if ( ! $ip ) {
			$ip = $_SERVER['SERVER_ADDR'];
		}

		$sending_ip[ $ipkey ] = $ip;

		return $sending_ip[ $ipkey ];
	}

	/**
	 * Get the sending IP addresses, list all IPs found.
	 *
	 * @return array
	 */
	public static function get_sending_ips() {
		$sender_is_localhost = false;
		if ( 'definitely' === self::sender_validation_works() ) {
			$sender_is_localhost = true;
		}
		if ( $sender_is_localhost ) {
			return array_values(
				array_unique(
					array_filter(
						[
							self::get_sending_ip(),
							self::get_sending_ip( true ),
						]
					)
				)
			);
		}

		self::start_ip_trace();
		$smtp_server = self::get_config()['smtp']['host'] ?? '';
		$remote_ips  = self::dns_get_record_with_recurse( $smtp_server, DNS_AAAA | DNS_A | DNS_CNAME, true );

		return array_unique( $remote_ips );
	}

	/**
	 * Test: Does the SPF for $domain contain or refer to this $ip.
	 *
	 * @param string $domain The domain name.
	 * @param string $ip     The IP address.
	 *
	 * @return bool|null
	 */
	public static function validate_ip_listed_in_spf( $domain, $ip ) {
		$dns = self::dns_get_record( $domain, DNS_TXT );
		if ( ! $dns ) {
			return null;
		}

		foreach ( $dns as $record ) {
			$record['txt'] = strtolower( $record['txt'] );
			if ( false !== strpos( $record['txt'], 'v=spf1' ) ) {
				$sections = explode( ' ', $record['txt'] );
				foreach ( $sections as $section ) {
					if ( preg_match( '/(a|aaaa|mx):(.+)/', $section, $mx_match ) ) {
						// here we only expand the record, the actual check is done later .
						foreach ( self::dns_get_record( $mx_match[2], DNS_MX ) as $item ) {
							$sections[] = 'a/' . $item['target'];
						}
					}

					if ( 'a' === $section || 'aaaa' === $section || 'a/' === substr( $section, 0, 2 ) ) {
						[ $_, $_domain ] = explode( '/', "$section/$domain" );
						if ( IP::is_4( $ip ) ) {
							$m_ip = self::dns_get_record( $_domain, DNS_A, true );
							if ( IP::a_4_is_4( $m_ip, $ip ) ) {
								return $section;
							}
						}
						if ( IP::is_6( $ip ) ) {
							$m_ip = self::dns_get_record( $_domain, DNS_AAAA, true );
							if ( IP::a_6_is_6( $m_ip, $ip ) ) {
								return $section;
							}
						}
					} elseif ( 'mx' === $section ) {
						$mx = self::dns_get_record( $domain, DNS_MX );
						foreach ( $mx as $mx_record ) {
							$target = $mx_record['target'] ?? '';
							if ( IP::is_4( $ip ) ) {
								try {
									$new_target = self::dns_get_record( $domain, DNS_A, true );
								} catch ( Exception $e ) {
									$new_target = $target;
								}
								if ( IP::a_4_is_4( $ip, $new_target ) ) {
									return $section;
								}
							}
							if ( IP::is_6( $ip ) ) {
								try {
									$new_target = self::dns_get_record( $domain, DNS_AAAA, true );
								} catch ( Exception $e ) {
									$new_target = $target;
								}
								if ( IP::a_6_is_6( $ip, $new_target ) ) {
									return $section;
								}
							}
						}
					} elseif ( preg_match( '/ip4:(\d+\.\d+\.\d+\.\d+)$/', $section, $m_ip ) ) {
						if ( IP::a_4_is_4( $ip, $m_ip[1] ) ) {
							return $section;
						}
					} elseif ( preg_match( '/ip4:([0-9.]+\/\d+)$/', $section, $ip_cidr ) ) {
						if ( IP::ip4_match_cidr( $ip, $ip_cidr[1] ) ) {
							return $section;
						}
					} elseif ( preg_match( '/ip6:([0-9A-Fa-f:]+)$/', $section, $m_ip ) ) {
						if ( IP::is_6( $m_ip[1] ) && IP::a_6_is_6( $ip, $m_ip[1] ) ) {
							return $section;
						}
					} elseif ( preg_match( '/ip6:([0-9A-Fa-f:]+\/\d+)$/', $section, $ip_cidr ) ) {
						if ( IP::ip6_match_cidr( $ip, $ip_cidr[1] ) ) {
							return $section;
						}
					} elseif ( preg_match( '/include:(.+)$/', $section, $include ) ) {
						$result = self::validate_ip_listed_in_spf( $include[1], $ip );
						if ( $result ) {
							return $section . ' > ' . $result;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Get the DNS record (cached) for a given domain.
	 *
	 * @param string $lookup        The domain to lookup.
	 * @param int    $filter        A DNS_* constant indicating which records you are looking for, DNS_A, DNS_TXT etx.
	 * @param bool   $single_output Return a single value (only works for DNS_A and DNS_A6/DNS_AAAA.
	 *
	 * @return array|mixed
	 */
	public static function dns_get_record( $lookup, $filter, $single_output = null ) {
		// pre-filter; these tlds can never have SPF or other special records.
		$local_tlds = apply_filters( 'wpes_local_tlds', [ 'local', 'test' ] );
		$local_tlds = array_filter( array_unique( $local_tlds ) );
		if ( [] !== $local_tlds ) {
			$local_tlds = array_map( 'preg_quote', $local_tlds, [ '/' ] );
			$local_tlds = implode( '|', $local_tlds );
			if ( preg_match( '/\.(' . $local_tlds . ')$/', $lookup ) && ( DNS_A !== $filter || DNS_A6 !== $filter ) ) {
				return [];
			}
		}
		// Proceed with normal lookup.
		$transient_name = "dns_{$lookup}__TYPE{$filter}__cache";
		$transient      = get_site_transient( $transient_name );
		if ( ! $transient ) {
			$transient = self::_dns_get_record( $lookup, $filter );
			$ttl       = count( $transient ) > 0 && is_array( $transient[0] && isset( $transient[0]['ttl'] ) ) ? $transient[0]['ttl'] : 3600;
			set_site_transient( $transient_name, $transient, $ttl );
		}
		if ( $single_output ) { // Most records are repeatable, should return array, calling code should process array.
			if ( empty( $transient ) ) {
				return false;
			}
			if ( DNS_A === $filter ) {
				return $transient[0]['ip'];
			}
			if ( DNS_A6 === $filter || DNS_AAAA === $filter ) {
				return $transient[0]['ipv6'];
			}
		}

		return $transient;
	}

	/**
	 * DNS lookup; will do IPv4, IPv6 and CNAME lookups, will recurse CNAME lookups.
	 * Will return the first IP found.
	 *
	 * @param string $lookup The domain to lookup.
	 * @param int    $filter A DNS_* constant indicating which records you are looking for, DNS_A, DNS_AAAA or DNS_CNAME are allowed.
	 * @param bool   $all    If true, will return all records found, not just the first one.
	 *
	 * @return string|string[]|false An IP address, either IPv4 or IPv6, whichever is found first.
	 */
	public static function dns_get_record_with_recurse( $lookup, $filter = DNS_A | DNS_AAAA | DNS_CNAME, $all = false ) {
		$return_values = [];
		if ( ( $filter & DNS_AAAA ) === DNS_AAAA ) {
			$ipv6 = self::dns_get_record( $lookup, DNS_AAAA );
			foreach ( $ipv6 as $record ) {
				if ( filter_var( $record['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
					self::ip_trace_step( $record['ipv6'] );

					if ( $all ) {
						$return_values[] = $record['ipv6'];
					} else {
						return $record['ipv6'];
					}
				}
			}
		}
		if ( ( $filter & DNS_A ) === DNS_A ) {
			$ipv4 = self::dns_get_record( $lookup, DNS_A );
			foreach ( $ipv4 as $record ) {
				if ( filter_var( $record['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
					self::ip_trace_step( $record['ip'] );

					if ( $all ) {
						$return_values[] = $record['ip'];
					} else {
						return $record['ip'];
					}
				}
			}
		}
		if ( ( $filter & DNS_CNAME ) === DNS_CNAME ) {
			$cname = self::dns_get_record( $lookup, DNS_CNAME );
			foreach ( $cname as $record ) {
				$lookup = $record['cname'];
				self::ip_trace_step( $lookup );
				$result = self::dns_get_record_with_recurse( $lookup, $filter );
				if ( $result ) {
					if ( $all ) {
						$return_values = array_merge( $return_values, (array) $result );
					} else {
						return $result;
					}
				}
			}
		}

		return $all ? $return_values : false;
	}

	/**
	 * Wrapper for dns_get_record, with Cloudflare DoH as first option, php dns_get_record as fallback, if available.
	 *
	 * @param string $hostname The hostname to lookup.
	 * @param int    $type     The type of record to lookup.
	 *
	 * @return array
	 * @see \dns_get_record()
	 *
	 */
	private static function _dns_get_record( $hostname, $type = DNS_ALL ) {
		$return = [];

		if ( DNS_ANY === $type ) {
			$returnA = self::_dns_get_record_cfdoh( $hostname, DNS_A );
			try {
				$returnAAAA = self::_dns_get_record_cfdoh( $hostname, DNS_AAAA );
			} catch ( \Exception $e ) {
				error_log( json_encode( $e ) );
			}
			$return = array_merge( (array) $returnA, (array) ( $returnAAAA ?? [] ) );
		} else {
			$return = self::_dns_get_record_cfdoh( $hostname, $type );
		}

		// Slow dns lookup, will crash on LocalWP, so we check if not disabled in php.ini, and if not disabled, we check if disabled in options.
		if ( ! $return && function_exists( 'dns_get_record' ) && ! get_option( 'disable_dns_get_record', false ) ) {
			// Slow dns lookup.
			if ( DNS_ANY === $type ) {
				$returnA = dns_get_record( $hostname, DNS_A );
				try {
					$returnAAAA = dns_get_record( $hostname, DNS_AAAA );
				} catch ( \Exception $e ) {
					error_log( json_encode( $e ) );
				}
				$return = array_merge( (array) $returnA, (array) ( $returnAAAA ?? [] ) );
			} else {
				$return = dns_get_record( $hostname, $type );
			}
		}

		return $return;
	}

	/**
	 * Get DNS record using Cloudflare DoH.
	 *
	 * @param string $hostname The hostname to lookup.
	 * @param int    $type     The type of record to lookup. Uses DNS_* constants, @see \dns_get_record()
	 *
	 * @return array
	 */
	private static function _dns_get_record_cfdoh( $hostname, $type = DNS_ANY ) {

		$is_wildcard          = 0 === strpos( $hostname, '*.' );
		$wildcard_hostname    = explode( '.', $hostname ?? '' ) ?: [];
		$wildcard_hostname[0] = '*';
		$wildcard_hostname    = implode( '.', $wildcard_hostname );
		$hostname             = trim( $hostname, '.' ) . '.';

		$php_to_cf_type   = [
			DNS_A     => 'A',
			DNS_AAAA  => 'AAAA',
			DNS_CNAME => 'CNAME',
			DNS_MX    => 'MX',
			DNS_NS    => 'NS',
			DNS_PTR   => 'PTR',
			DNS_SOA   => 'SOA',
			DNS_TXT   => 'TXT',
		];
		$cf_numeric_types = [
			1  => 'A',
			2  => 'NS',
			5  => 'CNAME',
			6  => 'SOA',
			12 => 'PTR',
			15 => 'MX',
			16 => 'TXT',
			28 => 'AAAA',
		];
		$cf_type          = $php_to_cf_type[ $type ] ?? 'ANY';

		if ( 'ANY' === $cf_type ) {
			$results = [];
			foreach ( array_keys( $php_to_cf_type ) as $DNS_TYPE ) {
				$results = array_merge( $results, dns_get_record( $hostname, $DNS_TYPE ) ?: [] );
			}

			return $results;
		}

		$result = wp_remote_get(
			'https://cloudflare-dns.com/dns-query?name=' . $hostname . '&type=' . $cf_type,
			[
				'timeout' => 5,
				'headers' => [
					'accept'  => 'application/dns-json',
					'referer' => 'https://cloudflare-dns.com/dns-query', // no leaking please.
				],
			]
		);
		if ( is_wp_error( $result ) ) {
			return $is_wildcard ? [] : self::_dns_get_record_cfdoh( $wildcard_hostname, $type );
		}
		$result = wp_remote_retrieve_body( $result );
		if ( ! $result ) {
			return $is_wildcard ? [] : self::_dns_get_record_cfdoh( $wildcard_hostname, $type );
		}
		$result = json_decode( $result, true );
		if ( ! $result ) {
			return $is_wildcard ? [] : self::_dns_get_record_cfdoh( $wildcard_hostname, $type );
		}
		$answer = $result['Answer'] ?? [];
		if ( ! $answer ) {
			return $is_wildcard ? [] : self::_dns_get_record_cfdoh( $wildcard_hostname, $type );
		}
		$return = [];
		foreach ( $answer as $entry ) {
			$entry                 = array_change_key_case( $entry, CASE_LOWER );
			$return_type           = $entry['type'] ?? 0;
			$request_type          = $php_to_cf_type[ $type ] ?? 'UNKNOWN';
			$return_type           = $cf_numeric_types[ $return_type ] ?? $request_type;
			$entry['type']         = $return_type;
			$entry['ttl']          = $entry['TTL'] ?? 0;
			$entry['class']        = $entry['class'] ?? 'IN';
			$entry['host']         = $entry['name'] ?? $hostname;
			$ip                    = $entry['data'] ?? '';
			$return_type           = strtolower( $return_type );
			$entry[ $return_type ] = trim( $entry['data'] ?? '' );
			$quote                 = substr( $entry['data'], 0, 1 );
			if ( $quote === '"' ) {
				$entry[ $return_type ] = str_replace( "$quote $quote", '', $entry[ $return_type ] );
				$entry[ $return_type ] = trim( $entry[ $return_type ], $quote );
			}
			if ( IP::is_6( $ip ) ) {
				$entry['ipv6'] = $ip;
			} else {
				$entry['ip'] = $ip;
			}
			$return[] = $entry;
		}

		return $return || $is_wildcard ? $return : self::_dns_get_record_cfdoh( $wildcard_hostname, $type );
	}

	/**
	 * Test: this email address matches the domain of this website.
	 *
	 * @param string $email The email address to test.
	 *
	 * @return bool
	 */
	public static function this_email_matches_website_domain( $email ) {
		$url  = get_bloginfo( 'url' );
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$host = preg_replace( '/^www\d*\./', '', $host );

		$result = ( preg_match( '/@' . $host . '$/', $email ) );
		self::log_message( "Email $email matches website domain $host ?: " . ( $result ? 'yes' : 'no' ) );

		return $result;
	}

	/**
	 * Implementation of action  wp_mailer_init .
	 *
	 * @param WPES_PHPMailer $mailer The PHPMailer object, either PHPMailer or PHPMailer\PHPMailer\PHPMailer ... Thank you WordPress...
	 */
	public static function action_phpmailer_init( &$mailer ) {
		// @phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$config = self::get_config();

		if ( isset( $config['smtp']['timeout'] ) ) {
			$mailer->Timeout = $config['smtp']['timeout'];
		}

		if ( $config['smtp'] ) {
			$mailer->IsSMTP();

			$mailer->Host = $config['smtp']['host'];
			if ( ! empty( $config['smtp']['port'] ) && (int) $config['smtp']['port'] > 0 ) {
				$mailer->Port = $config['smtp']['port'];
			} elseif ( isset( $config['smtp']['secure'] ) && $config['smtp']['secure'] ) {
				switch ( trim( $config['smtp']['secure'], '-' ) ) {
					case 'ssl':
						$mailer->Port = 465;
						break;
					case 'tls':
						$mailer->Port = 587;
						break;
					default:
						$mailer->Port = 25;
				}
			}

			if ( isset( $config['smtp']['username'] ) ) {
				if ( trim( $config['smtp']['username'] ) !== '' ) {
					$mailer->SMTPAuth = true;
					$mailer->Username = $config['smtp']['username'];
					$mailer->Password = $config['smtp']['password'];
				}
				if ( isset( $config['smtp']['secure'] ) && $config['smtp']['secure'] ) {
					$mailer->SMTPSecure = trim( $config['smtp']['secure'], '-' );
				} else {
					$mailer->SMTPAutoTLS = false;
				}
				if ( ( defined( 'WPES_ALLOW_SSL_SELF_SIGNED' ) && true === WPES_ALLOW_SSL_SELF_SIGNED ) || '-' === substr( $config['smtp']['secure'] ?? '', -1, 1 ) ) {
					$mailer->SMTPOptions = [
						'ssl' => [
							'verify_peer'       => false,
							'verify_peer_name'  => false,
							'allow_self_signed' => true,
						],
					];
				}
			}
		}

		self::log( 'MAILER ' . __LINE__ . ' set FROM: ' . self::wp_mail_from() );
		$mailer->Sender = self::wp_mail_from();

		$mailer->Body = self::preserve_weird_url_display( $mailer->Body );

		if ( $config['is_html'] || $config['enable_history'] ) {
			$check_encoding_result = false;
			if ( 'auto' === $config['content_precode'] ) {
				$encoding_table = explode( ',', self::ENCODINGS );
				foreach ( $encoding_table as $encoding ) {
					$check_encoding_result = mb_check_encoding( $mailer->Body, $encoding );
					if ( $check_encoding_result ) {
						$check_encoding_result = $encoding;
						break;
					}
				}
			}

			$mailer->Body = self::maybe_convert_to_html( $mailer->Body, $mailer->Subject, $mailer, $check_encoding_result ?: 'utf-8' );

			$css = Plugin::apply_filters_deprecated( 'wpes_css', [ '', &$mailer ], '5.0.0', 'email_essentials_css' );
			$css = apply_filters_ref_array( 'email_essentials_css', [ $css, &$mailer ] );

			if ( $config['css_inliner'] ) {
				require_once __DIR__ . '/../lib/class-css-inliner.php';
				$inliner      = new CSS_Inliner( $mailer->Body, $css );
				$mailer->Body = $inliner->convert();
			}

			$mailer->isHTML( true );
		}

		if ( $config['do_shortcodes'] ) {
			$mailer->Body = do_shortcode( $mailer->Body );
		}

		if ( $config['alt_body'] || $config['enable_history'] ) {
			$body = $mailer->Body;
			$btag = strpos( $body, '<body' );
			if ( false !== $btag ) {
				$bodystart = strpos( $body, '>', $btag );
				$bodytag   = substr( $body, $btag, $bodystart - $btag + 1 );

				[ $body, $junk ] = explode( '</body', $body );
				[ $junk, $body ] = explode( $bodytag, $body );
			}

			// remove all line breaks.
			$body = str_replace( "\n", '', $body );

			// images to alt tags.
			// example; <img src="/logo.png" alt="company logo" /> becomes  company logo.
			$body = preg_replace( "/<img.+alt=([\"'])(.+)(\\1).+>/U", "\\2", $body );

			// links to link-text+url.
			// example; <a href="http://nu.nl">Go to NU.nl</a> becomes:  Go to Nu.nl ( http://nu.nl ).
			$body = preg_replace( "/<a.+href=([\"'])(.+)(\\1).+>([^<]+)<\/a>/U", "\\4 (\\2)", $body );

			// End of headings to separate lines, preserve the tags, will be dealt with later.
			$body = preg_replace( '/(<h[1-6])/Ui', "\n\\1", $body );
			$body = preg_replace( '/(<\/h[1-6]>)/Ui', "\\1\n", $body );
			// End of block elements to separate lines, preserve the tags, will be dealt with later.
			$body = preg_replace( '/(<\/(p|table|div)>)/Ui', "\\1\n", $body );

			// remove all HTML except line breaks and line-break-ish.
			$body = strip_tags( $body, '<br><tr><li>' );

			// replace all forms of breaks, list items and table row endings to new-lines.
			$body = preg_replace( '/<br[\/ ]*>/Ui', "\n", $body );
			$body = preg_replace( '/<\/(li|tr)>/Ui', '</\1>' . "\n\n", $body );

			// remove all HTML.
			$body = strip_tags( $body, '' );

			// remove all carriage return symbols.
			$body = str_replace( "\r", '', $body );

			// convert non-breaking-space to regular space.
			$body = strtr( $body, [ '&nbsp;' => ' ' ] );

			// remove white-space at beginning and end of the lines.
			$body = explode( "\n", $body );
			foreach ( $body as $i => $line ) {
				$body[ $i ] = trim( $line );
			}
			$body = implode( "\n", $body );

			// remove newlines where more than two (two newlines make one blank line, remember that).
			$body = preg_replace( "/[\n]{2,}/", "\n\n", $body );

			// Neat lines.
			$body = wordwrap(
				$body,
				75,
				"\n",
				false
			);

			// set the alternate body.
			$mailer->AltBody = $body;

			if ( $config['do_shortcodes'] ) {
				$mailer->AltBody = do_shortcode( $mailer->AltBody );
			}
		}

		// Check if this is a debug request;.
		if ( wp_verify_nonce( $_POST['wpes-nonce'] ?? false, 'wp-email-essentials--settings' ) && $_POST && isset( $_POST['form_id'] ) && 'wp-email-essentials' === $_POST['form_id'] && __( 'Send sample mail', 'email-essentials' ) === $_POST['op'] ) {
			$mailer->Timeout   = 5;
			$mailer->SMTPDebug = 2;
		}

		$mailer->ContentType .= '; charset=' . $mailer->CharSet;

		$from = self::wp_mail_from();

		// S/MIME Signing .
		if ( $config['enable_smime'] ) {
			$id = self::get_smime_identity( $from );
			if ( $id ) {
				[ $crt, $key, $pass ] = $id;

				$mailer->sign( $crt, $key, $pass );
			}
		}

		// DKIM Signing .
		if ( $config['enable_dkim'] ) {
			$id = self::get_dkim_identity( $from );
			if ( $id ) {
				[ $crt, $key, $pass, $selector, $domain ] = $id;

				$mailer->DKIM_domain     = $domain;
				$mailer->DKIM_private    = $key;
				$mailer->DKIM_selector   = $selector;
				$mailer->DKIM_passphrase = $pass;
				$mailer->DKIM_identity   = $from;
			}
		}

		// DEBUG output .
		// @phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( wp_verify_nonce( $_POST['wpes-nonce'] ?? false, 'wp-email-essentials--settings' ) && $_POST && isset( $_POST['form_id'] ) && 'wp-email-essentials' === $_POST['form_id'] && __( 'Print debug output of sample mail', 'email-essentials' ) === $_POST['op'] ) {
			$mailer->SMTPDebug = true;
			print '<h2>' . esc_html__( 'Dump of PHP Mailer object', 'email-essentials' ) . '</h2><pre>';
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
			var_dumP( $mailer );
			exit;
		}

		// @phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Fix WordPress' stupid decision to show an url wrapped in < > ... I mean, c'mon!.
	 *
	 * @param string $html The HTML to fix.
	 *
	 * @return string
	 */
	private static function preserve_weird_url_display( $html ) {
		if ( preg_match( '/<(http(s)?:\/\/[^>]+)>/', $html, $m ) ) {
			$url = $m[1];
			if ( defined( 'WPES_CLEAN_LOGIN_RESET_URL' ) && true === WPES_CLEAN_LOGIN_RESET_URL ) {
				return str_replace( '<' . $url . '>', $url, $html );
			}

			return str_replace( '<' . $url . '>', '[' . $url . ']', $html );
		}

		return $html;
	}

	/**
	 * Convert a body to HTML, if not already HTML.
	 *
	 * @param string         $might_be_text The email body that might be text, might be HTML.
	 * @param string         $subject       The subject.
	 * @param WPES_PHPMailer $mailer        The PHP_Mailer object.
	 * @param string         $charset       A charset.
	 *
	 * @return mixed|string
	 */
	public static function maybe_convert_to_html( $might_be_text, $subject, $mailer, $charset = 'utf-8' ) {
		$html_preg      = '<(br|a|p|body|table|div|span|body|html)';
		$should_be_html = preg_match( "/$html_preg/", $might_be_text ) ? $might_be_text : nl2br( trim( $might_be_text ) );

		// should have some basic HTML now, otherwise, add a P.
		if ( ! preg_match( "/$html_preg/", $should_be_html ) ) {
			$should_be_html = '<p>' . $should_be_html . '</p>';
		}

		// now check for HTML envelope.
		if ( false === strpos( $should_be_html, '<html' ) ) {
			$should_be_html = self::build_html( $mailer, $subject, $should_be_html, $charset );
		}

		// Verify charset is defined.
		// <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"> .
		$find_charset = preg_match( '/http-equiv=.Content-Type.[^>]+charset=.?' . $charset . '/i', $should_be_html );
		if ( ! $find_charset ) {
			$find_wrong_charset = preg_match( '/http-equiv=.Content-Type.[^>]+charset=[^>]+>/i', $should_be_html, $m );
			if ( $find_wrong_charset ) {
				// change charset.
				$should_be_html = str_replace( $m[0], 'http-equiv="Content-Type" x="wrong charset detected" content="text/html; charset=' . $charset . '">', $should_be_html );
			} else {
				// add charset.
				$should_be_html = str_replace( '</head>', '<meta http-equiv="Content-Type" x="missing charset" content="text/html; charset=' . $charset . '"></head>', $should_be_html );
			}
		}

		return $should_be_html;
	}

	/**
	 * Build HTML for sending the email.
	 *
	 * @param WPES_PHPMailer $mailer         The mailer object.
	 * @param string         $subject        The subject.
	 * @param string         $should_be_html The email body which now should be HTML.
	 * @param string         $charset        The charset.
	 *
	 * @return string
	 */
	public static function build_html( $mailer, $subject, $should_be_html, $charset = 'utf-8' ) {
		// at this stage we will convert raw HTML part to a full HTML page.

		// you can define a file  wpes-email-template.php  in your theme to define the filters.
		locate_template( [ 'wpes-email-template.php' ], true );

		$subject = Plugin::apply_filters_deprecated( 'wpes_subject', [
			$subject,
			&$mailer,
		], '5.0.0', 'email_essentials_subject' );
		$subject = apply_filters_ref_array( 'email_essentials_subject', [ $subject, &$mailer ] );

		$head = '';

		if ( self::get_config()['is_html'] ) {

			$css = Plugin::apply_filters_deprecated( 'wpes_css', [ '', &$mailer ], '5.0.0', 'email_essentials_css' );
			$css = apply_filters_ref_array( 'email_essentials_css', [ $css, &$mailer ] );

			$head = '<title>' . $subject . '</title><style type="text/css">' . $css . '</style>';
			$head = Plugin::apply_filters_deprecated( 'wpes_head', [
				$head,
				&$mailer,
			], '5.0.0', 'email_essentials_head' );
			$head = apply_filters_ref_array( 'email_essentials_head', [ $head, &$mailer ] );

			$should_be_html = Plugin::apply_filters_deprecated( 'wpes_body', [
				$should_be_html,
				&$mailer,
			], '5.0.0', 'email_essentials_body' );
			$should_be_html = apply_filters_ref_array( 'email_essentials_body', [ $should_be_html, &$mailer ] );
			$should_be_html = htmlspecialchars_decode( htmlentities( $should_be_html ) );

		}

		return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '" />
<head>' . $head . '</head><body>' . $should_be_html . '</body></html>';
	}


	/**
	 * This is triggered when CF7 has option "send as html" on, but it interferes with the rest of WP_Email_Essentials.
	 *
	 * @optionalparam string      $header     WPCF7 Email header.
	 * @optionalparam \WPCF7_Mail $wpcf7_mail The WPCF7_Mail object.
	 *
	 * @return string
	 */
	public static function wpcf7_mail_html_header() {
		return '';
	}

	/**
	 * This is triggered when CF7 has option "send as html" on, but it interferes with the rest of WP_Email_Essentials.
	 *
	 * @optionalparam string      $footer     WPCF7 Email footer.
	 * @optionalparam \WPCF7_Mail $wpcf7_mail The WPCF7_Mail object.
	 *
	 * @return string
	 */
	public static function wpcf7_mail_html_footer() {
		return '';
	}

	/**
	 * Memory cell: the WP-Mail From: address.
	 *
	 * @param string $from The address to remember.
	 *
	 * @return mixed
	 */
	public static function wp_mail_from( $from = null ) {
		static $store;
		if ( $from ) {
			$store = $from;
		}

		return $store;
	}

	/**
	 * Memory cell: the WP-Mail From: name.
	 *
	 * @param string $from The name to remember.
	 *
	 * @return mixed
	 */
	public static function wp_mail_from_name( $from = null ) {
		static $store;
		if ( $from ) {
			$store = $from;
		}

		return $store;
	}

	/**
	 * Implementation of filter  wp_mail_from. Returns the remembered From address.
	 *
	 * @return string
	 */
	public static function filter_wp_mail_from() {
		return self::wp_mail_from();
	}

	/**
	 * Implementation of filter  wp_mail_from. Returns the remembered From name.
	 *
	 * @return string
	 */
	public static function filter_wp_mail_from_name() {
		return self::wp_mail_from_name();
	}

	/**
	 * Get the module configuration.
	 *
	 * @param bool $raw Get raw data?.
	 *
	 * @return array|mixed
	 */
	public static function get_config( $raw = false ) {
		$defaults = [
			'smtp'                 => false,
			'from_email'           => get_bloginfo( 'admin_email' ),
			'from_name'            => self::get_hostname_by_blogurl(),
			'is_html'              => true,
			'alt_body'             => true,
			'css_inliner'          => true,
			'enable_smime'         => false,
			'enable_dkim'          => false,
			'spf_lookup_enabled'   => false,
			'errors_to'            => get_bloginfo( 'admin_email' ),
			'content_precode'      => false,
			'SingleTo'             => true,
			'do_shortcodes'        => true,
			'enable_history'       => false,
			'enable_queue'         => false,
			'make_from_valid_when' => 'when_sender_invalid',
			'make_from_valid'      => 'default',
		];

		$defaults = apply_filters( 'email_essentials_defaults', Plugin::apply_filters_deprecated( 'wpes_defaults', [ $defaults ], '5.0.0', 'email_essentials_defaults' ), $defaults );

		$settings = get_option( 'wp-email-essentials', $defaults );
		if ( ! $raw ) {
			$settings = apply_filters( 'email_essentials_settings', Plugin::apply_filters_deprecated( 'wpes_settings', [ $settings ], '5.0.0', 'email_essentials_settings' ), $settings );
			if ( ! is_array( $settings ) ) {
				$settings = $defaults;
			}

			$settings['certificate_folder'] = $settings['certfolder'] ?? '';
			if ( '/' !== substr( $settings['certificate_folder'], 0, 1 ) ) {
				$settings['certificate_folder'] = rtrim( self::root_path(), '/' ) . '/' . $settings['certificate_folder'];
			}

			$settings['dkim_certificate_folder'] = $settings['dkimfolder'] ?? '';
			if ( '/' !== substr( $settings['dkim_certificate_folder'], 0, 1 ) ) {
				$settings['dkim_certificate_folder'] = rtrim( self::root_path(), '/' ) . '/' . $settings['dkim_certificate_folder'];
			}
		}

		$return = array_merge( $defaults, $settings );

		if ( $return['smtp'] && false !== strpos( $return['smtp']['host'], ':' ) ) {
			[ $wpes_host, $wpes_port ] = explode( ':', $return['smtp']['host'] );

			if ( is_numeric( $wpes_port ) ) {
				$return['smtp']['port'] = $wpes_port;
				$return['smtp']['host'] = $wpes_host;
			}
		}

		return $return;
	}

	/**
	 * Write new configuration data.
	 *
	 * @param array $values The values to write.
	 * @param bool  $raw    Write raw?.
	 *
	 * @return bool|void
	 */
	private static function set_config( $values, $raw = false ) {
		if ( $raw ) {
			return update_option( 'wp-email-essentials', $values );
		}

		$values   = stripslashes_deep( $values );
		$settings = self::get_config();
		if ( isset( $values['smtp-enabled'] ) && $values['smtp-enabled'] ) {
			$settings['smtp'] = [
				'secure'   => $values['secure'],
				'host'     => $values['host'],
				'port'     => $values['port'],
				'username' => $values['username'],
				'password' => ( str_repeat( '*', strlen( $values['password'] ) ) === $values['password'] && $settings['smtp'] ) ? $settings['smtp']['password'] : $values['password'],
			];

			if ( false !== strpos( $settings['smtp']['host'], ':' ) ) {
				[ $host, $port ] = explode( ':', $settings['smtp']['host'] );
				if ( is_numeric( $port ) ) {
					$settings['smtp']['port'] = $port;
					$settings['smtp']['host'] = $host;
				}
			}

			if ( $settings['smtp']['port'] <= 0 ) {
				$settings['smtp']['port'] = '';
			}
		} else {
			$settings['smtp'] = false;
		}
		$settings['from_name']          = array_key_exists( 'from_name', $values ) && $values['from_name'] ? trim( $values['from_name'] ) : $settings['from_name'];
		$settings['from_email']         = array_key_exists( 'from_email', $values ) && $values['from_email'] ? trim( $values['from_email'] ) : $settings['from_email'];
		$settings['timeout']            = array_key_exists( 'timeout', $values ) && $values['timeout'] ? $values['timeout'] : 5;
		$settings['is_html']            = array_key_exists( 'is_html', $values ) && $values['is_html'];
		$settings['css_inliner']        = array_key_exists( 'css_inliner', $values ) && $values['css_inliner'];
		$settings['content_precode']    = array_key_exists( 'content_precode', $values ) && $values['content_precode'] ? $values['content_precode'] : false;
		$settings['alt_body']           = array_key_exists( 'alt_body', $values ) && $values['alt_body'];
		$settings['do_shortcodes']      = array_key_exists( 'do_shortcodes', $values ) && $values['do_shortcodes'];
		$settings['SingleTo']           = array_key_exists( 'SingleTo', $values ) && $values['SingleTo'];
		$settings['spf_lookup_enabled'] = array_key_exists( 'spf_lookup_enabled', $values ) && $values['spf_lookup_enabled'];
		$settings['enable_history']     = array_key_exists( 'enable_history', $values ) && $values['enable_history'];
		$settings['enable_queue']       = array_key_exists( 'enable_queue', $values ) && $values['enable_queue'];

		$settings['enable_smime']         = array_key_exists( 'enable_smime', $values ) && $values['enable_smime'] ? '1' : '0';
		$settings['certfolder']           = array_key_exists( 'certfolder', $values ) && $values['certfolder'] ? $values['certfolder'] : '';
		$settings['enable_dkim']          = array_key_exists( 'enable_dkim', $values ) && $values['enable_dkim'] ? '1' : '0';
		$settings['dkimfolder']           = array_key_exists( 'dkimfolder', $values ) && $values['dkimfolder'] ? $values['dkimfolder'] : '';
		$settings['make_from_valid']      = array_key_exists( 'make_from_valid', $values ) && $values['make_from_valid'] ? $values['make_from_valid'] : '';
		$settings['make_from_valid_when'] = array_key_exists( 'make_from_valid_when', $values ) && $values['make_from_valid_when'] ? $values['make_from_valid_when'] : 'when_sender_invalid';
		$settings['errors_to']            = array_key_exists( 'errors_to', $values ) && $values['errors_to'] ? $values['errors_to'] : '';
		update_option( 'wp-email-essentials', $settings );
	}

	/**
	 * Update config from Migrations ONLY.
	 *
	 * @access RESTRICTED: Only allowed to be called internally, do not call this unless you know what you are doing.
	 *
	 * @param array $values New values.
	 */
	public static function update_config( $values ) {
		// Check referer ;).
		$trace  = debug_backtrace(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$caller = $trace[1];
		if (
			'class.migrations.php' === basename( $caller['file'] ) &&
			'migrate_from_' === substr( $caller['function'], 0, 13 ) &&
			Migrations::class === $caller['class']
		) {
			return self::set_config( $values );
		}
	}

	/**
	 * Get the hostname for the website.
	 *
	 * @return string
	 */
	public static function get_hostname_by_blogurl() {
		$url = get_bloginfo( 'url' );
		$url = wp_parse_url( $url );

		return $url['host'];
	}

	/**
	 * Decode an RFC2822 formatted email address to its components.
	 *
	 * @param string $rfc RFC2822 formatted email in format   "My Name" <my.email@address.com>    .
	 *
	 * @return array|false
	 */
	public static function rfc_decode( $rfc ) {
		$rfc = trim( $rfc );

		// $rfc might just be an email address
		if ( is_email( $rfc ) ) {
			return [
				'name'  => $rfc,
				'email' => $rfc,
			];
		}

		/**
		 * $rfc is not an email, the RFC format is:
		 * "Name Surname Anything here" <email@addr.ess>
		 * but quotes are optional...
		 * Name Surname Anything here <email@addr.ess>
		 * is considered valid as well
		 *
		 * Considering HTML, <email@addr.ess> is a tag, so we can strip that out with strip_tags
		 * and the remainder is the name-part.
		 */
		$name_part = wp_strip_all_tags( $rfc );
		// remove the name-part from the original and the email part is known.
		$email_part = preg_replace( '/' . preg_quote( $name_part, '/' ) . '/', '', $rfc, 1 );

		// strip illegal characters;.
		// the name part could have had escaped quotes (like "I have a quote \" here" <some@email.com> ).
		$name_part  = trim( stripslashes( $name_part ), "\n\t\r\" " );
		$email_part = trim( $email_part, "\n\t\r\"<> " );

		// verify :).
		if ( is_email( $email_part ) ) {
			return [
				'name'  => $name_part,
				'email' => $email_part,
			];
		}

		return false;
	}

	/**
	 * Split a comma separated list of RFC encoded email addresses into an array.
	 *
	 * @param string $string Comma separated list of email addresses.
	 *
	 * @return array
	 */
	public static function rfc_explode( $string ) {
		// safequard escaped quotes.
		$string = str_replace( '\\"', 'ESCAPEDQUOTE', $string );
		// get chunks.
		$exploded = [];
		$i        = 0;
		// this regexp will match any comma + a string behind it.
		// therefore, to fetch all elements, we need a dummy element at the end that will be ignored.
		$string .= ', dummy';
		while ( trim( $string ) && preg_match( '/(,)(([^"]|"[^"]*")*$)/', $string, $match ) ) {
			$i++;

			$matched_rest    = $match[0];
			$unmatched_first = str_replace( $matched_rest, '', $string );
			$string          = trim( $matched_rest, ', ' );
			$exploded[]      = str_replace( 'ESCAPEDQUOTE', '\\"', $unmatched_first );
		}

		return array_map( 'trim', $exploded );
	}

	/**
	 * Re-code email to RFC2822.
	 *
	 * @param string|string[2] $e The email, either a string, possibly in RFC2822 format, or an array with 'name' and 'email' .
	 *
	 * @return string
	 */
	private static function rfc_recode( $e ) {
		if ( ! is_array( $e ) ) {
			$e = self::rfc_decode( $e );
		}

		return self::rfc_encode( $e );
	}

	/**
	 * Encode an email to RFC2822.
	 *
	 * @param string[2] $email_array The email array consists of elements 'name' and 'email'.
	 *
	 * @return string
	 */
	public static function rfc_encode( $email_array ) {
		if ( ! $email_array['name'] ) {
			return $email_array['email'];
		}

		// this is the unescaped, unencasulated RFC, as WP 4.6 and higher want it.
		$email_array['name'] = trim( stripslashes( $email_array['name'] ), '"' );
		if ( version_compare( get_bloginfo( 'version' ), '4.5', '<=' ) ) {
			// this will escape all quotes and encapsulate with quotes, for 4.5 and older.
			$email_array['name'] = wp_json_encode( $email_array['name'] );
		}
		// so NO QUOTES HERE, they are there where needed.
		$return = trim( sprintf( '%s <%s>', $email_array['name'], $email_array['email'] ) );

		return $return;
	}

	/**
	 * Process settings when POSTed.
	 */
	public static function save_admin_settings() {
		$html = null;
		/**
		 * Save options for "Settings" pane..
		 */
		if ( wp_verify_nonce( $_POST['wpes-nonce'] ?? false, 'wp-email-essentials--settings' ) && isset( $_GET['page'] ) && 'wp-email-essentials' === $_GET['page'] && $_POST && isset( $_POST['form_id'] ) && 'wp-email-essentials' === $_POST['form_id'] ) {
			switch ( $_POST['op'] ) {
				case  __( 'Save settings', 'email-essentials' ):
					$config  = self::get_config();
					$host    = wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
					$host    = preg_replace( '/^www\d*\./', '', $host );
					$defmail = self::wp_mail_from( $_POST['settings']['from_email'] );
					if ( 'default' === $_POST['settings']['make_from_valid'] && ! self::i_am_allowed_to_send_in_name_of( $defmail ) ) {
						$_POST['settings']['make_from_valid'] = 'noreply';
					}
					self::set_config( $_POST['settings'] );
					set_transient( 'wpes_message', __( 'Settings saved.', 'email-essentials' ), 5 );
					wp_safe_redirect( remove_query_arg( 'wpes-nonce' ) );
					exit;
				case  __( 'Send sample mail', 'email-essentials' ):
					ob_start();
					self::$debug     = true;
					$send_email_to   = $_POST['send-test-email-to'] ?? false;
					$send_email_from = $_POST['send-test-email-from'] ?? false;
					if ( ! $send_email_to || ! is_email( $send_email_to ) ) {
						$send_email_to = get_option( 'admin_email', false );
					}
					if ( ! $send_email_to ) {
						self::$error = __( 'No email address to send to.', 'email-essentials' );
						break;
					}
					if ( ! $send_email_from || ! is_email( $send_email_from ) ) {
						$send_email_from = get_option( 'admin_email', false );
					}
					if ( ! $send_email_from ) {
						self::$error = __( 'No email address to send from.', 'email-essentials' );
						break;
					}
					update_option( 'wpes_last_test_sent_from', $send_email_from );
					update_option( 'wpes_last_test_sent_to', $send_email_to );

					$result      = wp_mail(
						$send_email_to,
						self::dummy_subject(),
						self::dummy_content(),
						[ 'X-Priority: 5', 'From: ' . $send_email_from ]
					);
					self::$debug = ob_get_clean();
					if ( $result ) {
						// translators: %s is the email address.
						self::$message = sprintf( __( 'Mail sent to %s', 'email-essentials' ), $send_email_to );
					} else {
						// translators: %s is the email address.
						self::$error = sprintf( __( 'Mail NOT sent to %s', 'email-essentials' ), $send_email_to );
					}
					break;
			}
		}

		/**
		 * Iframe content to show a sample email.
		 */
		// @phpcs:ignore WordPress.Security.NonceVerification.Missing -- not processing form content.
		if ( isset( $_GET['page'] ) && 'wp-email-essentials' === $_GET['page'] && isset( $_GET['iframe'] ) && 'content' === $_GET['iframe'] ) {
			$mailer          = new WPES_PHPMailer();
			$config          = self::get_config();
			$subject         = __( 'Sample email subject', 'email-essentials' );
			$mailer->Subject = $subject; // @phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer, sorry.
			$body            = self::dummy_content();
			switch ( true ) {
				case (bool) $config['is_html']:
					header( 'Content-Type: text/html; charset=utf-8' );
					$html = self::build_html( $mailer, $subject, $body, 'utf-8' );
					$html = self::cid_to_image( $html, $mailer );
					break;
				case ! $config['is_html']:
					header( 'Content-Type: text/plain; charset=utf-8' );
					$html = $body;
					break;
			}

			print $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- How to escape email content?.

			exit;
		}

		/**
		 * Save options for "alternative admins" panel
		 */
		if ( wp_verify_nonce( $_POST['wpes-nonce'] ?? false, 'wp-email-essentials--admins' ) && isset( $_GET['page'] ) && 'wpes-admins' === $_GET['page'] && $_POST && isset( $_POST['form_id'] ) && 'wpes-admins' === $_POST['form_id'] && __( 'Save settings', 'email-essentials' ) === $_POST['op'] ) {
			$keys = $_POST['settings']['keys'];
			$keys = array_filter(
				$keys,
				function ( $el ) {
					$els = explode( ',', $el );
					$els = array_map(
						function ( $el ) {
							return filter_var( $el, FILTER_VALIDATE_EMAIL );
						},
						$els
					);

					return implode( ',', $els );
				}
			);
			update_option( 'mail_key_admins', $keys );
			self::$message = __( 'Alternative Admins list saved.', 'email-essentials' );
			$regexps       = $_POST['settings']['regexp'];
			$list          = [];
			$__regex       = '/^\/[\s\S]+\/$/';
			foreach ( $regexps as $entry ) {
				if ( preg_match( $__regex, $entry['regexp'] ) ) {
					$list[ $entry['regexp'] ] = $entry['key'];
				}
			}
			update_option( 'mail_key_list', $list );
			self::$message .= ' ' . __( 'Subject-RegExp list saved.', 'email-essentials' );
		}

		/**
		 * Save options for "Moderators" panel.
		 */
		if ( wp_verify_nonce( $_POST['wpes-nonce'] ?? false, 'wp-email-essentials--moderators' ) && isset( $_GET['page'] ) && 'wpes-moderators' === $_GET['page'] && $_POST && isset( $_POST['form_id'] ) && 'wpes-moderators' === $_POST['form_id'] && __( 'Save settings', 'email-essentials' ) === $_POST['op'] ) {
			foreach ( $_POST['settings']['keys'] as $recipient => $_keys ) {
				foreach ( $_keys as $post_type => $keys ) {
					$_POST['settings']['keys'][ $recipient ][ $post_type ] = array_filter(
						$keys,
						function ( $el ) {
							$els = explode( ',', $el );
							$els = array_map(
								function ( $el ) {
									if ( ':blackhole:' === $el ) {
										return $el;
									}

									return filter_var( $el, FILTER_VALIDATE_EMAIL );
								},
								$els
							);

							return implode( ',', $els );
						}
					);
				}
			}
			update_option( 'mail_key_moderators', $_POST['settings']['keys'] );
			self::$message = __( 'Alternative Moderators list saved.', 'email-essentials' );
		}
	}

	/**
	 * Callback to the admin_menu action.
	 */
	public static function admin_menu() {
		add_menu_page(
			self::plugin_data()['LongName'],
			self::plugin_data()['Name'],
			'manage_options',
			'wp-email-essentials',
			[ self::class, 'admin_interface' ],
			'dashicons-email-alt'
		);

		add_submenu_page(
			'wp-email-essentials',
			self::plugin_data()['LongName'] . ' - ' . __( 'Alternative Admins', 'email-essentials' ),
			__( 'Alternative Admins', 'email-essentials' ),
			'manage_options',
			'wpes-admins',
			[
				self::class,
				'admin_interface_admins',
			]
		);

		add_submenu_page(
			'wp-email-essentials',
			self::plugin_data()['LongName'] . ' - ' . __( 'Alternative Moderators', 'email-essentials' ),
			__( 'Alternative Moderators', 'email-essentials' ),
			'manage_options',
			'wpes-moderators',
			[
				self::class,
				'admin_interface_moderators',
			]
		);
	}

	/**
	 * Template view
	 *
	 * @param string $tpl The template to load.
	 */
	public static function view( $tpl ) {
		// Sanitize path traversal.
		$tpl = basename( "./$tpl" );
		wp_enqueue_style( 'email-essentials' );
		wp_enqueue_script( 'email-essentials' );
		require __DIR__ . '/../templates/' . $tpl . '.php';
	}

	/**
	 * Load the settings template.
	 */
	public static function admin_interface() {
		self::view( 'admin-interface' );
	}

	/**
	 * Load the alternative admins template.
	 */
	public static function admin_interface_admins() {
		self::view( 'admin-admins' );
	}

	/**
	 * Load the moderators template.
	 */
	public static function admin_interface_moderators() {
		self::view( 'admin-moderators' );
	}

	/**
	 * Tests.
	 */
	public static function test() {
		// @phpcs:disable
		$test = self::rfc_decode( 'ik@remonpel.nl' );
		// should return array( 'name' => 'ik@remonpel.nl', 'email' => 'ik@remonpel.nl' )
		if ( $test['name'] == 'ik@remonpel.nl' && $test['email'] == 'ik@remonpel.nl' ) {
			echo "simple email address verified<br />\n";
		} else {
			echo "simple email address FAILED<br />\n";
		}

		$test = self::rfc_decode( 'Remon Pel <ik@remonpel.nl>' );
		// should return array( 'name' => 'Remon Pel', 'email' => 'ik@remonpel.nl' )
		if ( $test['name'] == 'Remon Pel' && $test['email'] == 'ik@remonpel.nl' ) {
			echo "RFC2822 no quotes email address verified<br />\n";
		} else {
			echo "RFC2822 no quotes email address FAILED<br />\n";
		}

		$test = self::rfc_decode( '"Remon Pel" <ik@remonpel.nl>' );
		// should return array( 'name' => 'Remon Pel', 'email' => 'ik@remonpel.nl' )
		if ( $test['name'] == 'Remon Pel' && $test['email'] == 'ik@remonpel.nl' ) {
			echo "RFC2822 with quotes email address verified<br />\n";
		} else {
			echo "RFC2822 with quotes email address FAILED<br />\n";
		}

		$test = self::rfc_decode( '    "   Remon Pel   " <ik@remonpel.nl>' );
		// should return array( 'name' => 'Remon Pel', 'email' => 'ik@remonpel.nl' )
		if ( $test['name'] == 'Remon Pel' && $test['email'] == 'ik@remonpel.nl' ) {
			echo "RFC2822 too many spaces - not valid RFC but still parses? verified<br />\n";
		} else {
			echo "RFC2822 too many spaces - not valid RFC but still parses? FAILED<br />\n";
		}

		exit;
		// @phpcs:enable
	}

	/**
	 * Generate dummy subject for sample email.
	 *
	 * @return string
	 */
	public static function dummy_subject() {
		return self::plugin_data()['Name'] . ' ' . __( 'Test-email', 'email-essentials' );
	}

	/**
	 * Generate dummy content for sample email.
	 *
	 * @return string
	 */
	public static function dummy_content() {
		$config = self::get_config();
		switch ( true ) {
			case (bool) $config['is_html']:
				return '<h1>Sample Email Body - HTML</h1>
<p>Some <a href="https://google.com/?s=random">råndôm</a> text Lorem Ipsum is <b>bold simply dummy</b> text of the <strong>strong printing and typesetting</strong> industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>
<h2>A header-2</h2>
<p>Some more text</p>
<h3>A header-3</h3>
<ul><li>A list - unordered, item 1</li><li>Item 2</li></ul>
<h4>A header-4</h4>
<ol><li>A list - ordered, item 1</li><li>Item 2</li></ol>';
			case ! $config['is_html']:
				return 'Sample Email Body - Plain text

Some råndôm text Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.

A header-2
Some more text

A header-3
A list
item 1
Item 2

A header-4
A list
item 1
Item 2
';
		}
	}

	/**
	 * For display purposes: fetch an ambedded image based on the CID and show it.
	 *
	 * @param string         $html   The HTML of the email.
	 * @param WPES_PHPMailer $mailer The PHP_Mailer object.
	 *
	 * @return string
	 */
	public static function cid_to_image( $html, $mailer ) {
		foreach ( $mailer->getAttachments() as $attachment ) {
			if ( $attachment[7] ) {
				// @phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- I ain't stupid...
				// @phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Yeah, cus that works on local files...
				$html = str_replace( 'cid:' . $attachment[7], 'data:' . $attachment[4] . ';' . $attachment[3] . ',' . base64_encode( file_get_contents( $attachment[0] ) ), $html );
				// @phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			}
		}

		return $html;
	}

	/**
	 * Display admin notices.
	 */
	public static function admin_notices() {
		$config = self::get_config();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- no form processing, just checking...
		$onpage = is_admin() && isset( $_GET['page'] ) && 'wp-email-essentials' === $_GET['page'];

		$from = $config['from_email'];
		if ( ! $from ) {
			$url = add_query_arg( 'page', 'wp-email-essentials', admin_url( 'tools.php' ) );
			if ( $onpage ) {
				$class = 'updated';
				// translators: %s: Plugin name.
				$message = sprintf( __( '%s is not yet configured. Please fill out the form below.', 'email-essentials' ), self::plugin_data()['Name'] );
				echo wp_kses_post( "<div class='$class'><p>$message</p></div>" );
			} else {
				$class = 'error';
				// translators: %1$s: Plugin name, %2$s: settings URL.
				$message = sprintf( __( '%1$s is not yet configured. Please go <a href="%2$s">here</a>.', 'email-essentials' ), self::plugin_data()['Name'], esc_attr( $url ) );
				echo wp_kses_post( "<div class='$class'><p>$message</p></div>" );
			}

			return;
		}

		// For devs; certfolder = setting, certificate_folder = real path;.
		if ( $config['enable_smime'] && isset( $config['certfolder'] ) && $config['certfolder'] && false !== strpos( realpath( $config['certificate_folder'] ), realpath( self::root_path() ) ) ) {
			$class = 'error';
			// translators: %s: a file path.
			$message = sprintf( __( 'The S/MIME certificate folder is inside the webspace. This is Extremely insecure. Please reconfigure, make sure the folder is outside the website-root %s.', 'email-essentials' ), self::root_path() );
			echo wp_kses_post( "<div class='$class'><p>$message</p></div>" );
		}

		if ( $config['enable_smime'] && $onpage && ! function_exists( 'openssl_pkcs7_sign' ) ) {
			$class   = 'error';
			$message = __( 'The openssl package for PHP is not installed, incomplete or broken. Please contact your hosting provider. S/MIME signing is NOT available.', 'email-essentials' );
			echo wp_kses_post( "<div class='$class'><p>$message</p></div>" );
		}

		if ( $config['enable_smime'] && $onpage && isset( $config['smtp']['host'] ) && ( false !== strpos( $config['smtp']['host'], 'mandrillapp' ) || false !== strpos( $config['smtp']['host'], 'sparkpostmail' ) ) && function_exists( 'openssl_pkcs7_sign' ) ) {
			$class   = 'error';
			$message = __( 'Services like MandrillApp or SparkPostMail will break S/MIME signing. Please use a different SMTP-service if signing is required.', 'email-essentials' );
			echo wp_kses_post( "<div class='$class'><p>$message</p></div>" );
		}

		// default mail identity existence.
		if ( $config['enable_smime'] && $onpage && ! self::get_smime_identity( $from ) ) {
			$rawset               = self::get_config( true );
			$set                  = $rawset['certfolder'];
			$rawset['certfolder'] = __DIR__ . '/.smime';
			self::set_config( $rawset );
			if ( self::get_smime_identity( $from ) ) {
				$class = 'error';
				// translators: %s: email address.
				$message = sprintf( __( 'There is no certificate for the default sender address <code>%s</code>. The required certificate is supplied with this plugin. Please copy it to the correct folder.', 'email-essentials' ), $from );
				echo wp_kses_post( "<div class='$class'><p>$message</p></div>" );
			} else {
				$class = 'error';
				// translators: %s: email address.
				$message = sprintf( __( 'There is no certificate for the default sender address <code>%s</code>. Start: <a href="https://www.comodo.com/home/email-security/free-email-certificate.php" target="_blank">here</a>.', 'email-essentials' ), $from );
				echo wp_kses_post( "<div class='$class'><p>$message</p></div>" );
			}

			$rawset['certfolder'] = $set;
			self::set_config( $rawset, true );
		}

		// For devs; dkimfolder = setting, dkim_certificate_folder = real path;.
		if ( ! empty( $config['enable_dkim'] ) && $config['enable_dkim'] && isset( $config['dkimfolder'] ) && $config['dkimfolder'] && false !== strpos( realpath( $config['dkim_certificate_folder'] ), realpath( self::root_path() ) ) ) {
			$class = 'error';
			// translators: %s: a file path.
			$message = sprintf( __( 'The DKIM certificate folder is inside the webspace. This is Extremely insecure. Please reconfigure, make sure the folder is outside the website-root %s.', 'email-essentials' ), self::root_path() );
			echo wp_kses_post( "<div class='$class'><p>$message</p></div>" );
		}

		// default mail identity existence.
		if ( ! empty( $config['enable_dkim'] ) && $config['enable_dkim'] && $onpage && ! self::get_dkim_identity( $from ) ) {
			$rawset               = self::get_config( true );
			$set                  = $rawset['dkimfolder'];
			$rawset['dkimfolder'] = $set;
			self::set_config( $rawset, true );
		}

		self::render_deprecation_notices( $onpage );
	}

	/**
	 * List all available S/MIME identities.
	 *
	 * @return array
	 */
	public static function list_smime_identities() {
		$c                  = self::get_config();
		$ids                = [];
		$certificate_folder = $c['certificate_folder'];
		if ( is_dir( $certificate_folder ) ) {
			$files = glob( $certificate_folder . '/*.crt' );
			foreach ( $files as $file ) {
				if ( is_file( $file ) && is_file( preg_replace( '/\.crt$/', '.key', $file ) ) ) {
					$ids[ basename( preg_replace( '/\.crt$/', '', $file ) ) ] = [
						$file,
						preg_replace( '/\.crt$/', '.key', $file ),
						trim( self::file_get_contents( preg_replace( '/\.crt$/', '.pass', $file ) ) ),
					];
				}
			}
		}

		return $ids;
	}

	/**
	 * Get S/MIME identity for a given email address.
	 *
	 * @param string $email The email address.
	 *
	 * @return false|mixed
	 */
	public static function get_smime_identity( $email ) {
		$ids = self::list_smime_identities();
		if ( isset( $ids[ $email ] ) ) {
			return $ids[ $email ];
		}

		return false;
	}

	/**
	 * List all available DKIM identities.
	 *
	 * @return array[]
	 */
	public static function list_dkim_identities() {
		$c                  = self::get_config();
		$ids                = [];
		$certificate_folder = $c['dkim_certificate_folder'];
		if ( is_dir( $certificate_folder ) ) {
			$files = glob( $certificate_folder . '/*.crt' );
			foreach ( $files as $file ) {
				if ( is_file( $file ) && is_file( preg_replace( '/\.crt$/', '.key', $file ) ) ) {
					$domain         = basename( preg_replace( '/\.crt$/', '', $file ) );
					$ids[ $domain ] = [
						$file,
						preg_replace( '/\.crt$/', '.key', $file ),
						trim( self::file_get_contents( preg_replace( '/\.crt$/', '.pass', $file ) ) ),
						trim( self::file_get_contents( preg_replace( '/\.crt$/', '.selector', $file ) ) ),
						$domain,
					];
				}
			}
		}

		return $ids;
	}

	/**
	 * Get a DKIM identity for a given email address.
	 *
	 * @param string $email The email address.
	 *
	 * @return false|array
	 */
	public static function get_dkim_identity( $email ) {
		$ids    = self::list_dkim_identities();
		$domain = explode( '@', '@' . $email );
		$domain = end( $domain );
		if ( isset( $ids[ $domain ] ) ) {
			return $ids[ $domain ];
		}

		return false;
	}

	/**
	 * Get the alternative recipient for sending a specific email to the site admin.
	 *
	 * @param array $email The WordPress email array with 'to', 'subject', 'message', 'headers' and 'attachments'.
	 *
	 * @return array
	 */
	public static function alternative_to( $email ) {
		$admin_emails = [ get_option( 'admin_email' ) ];
		if ( is_multisite() ) {
			$admin_emails[] = get_site_option( 'admin_email' );
		}
		self::log_message( "Detected admin-emails; " . implode( ', ', $admin_emails ) );

		// make sure we have a list of emails, not a single email.
		if ( ! is_array( $email['to'] ) ) {
			$email['to'] = self::rfc_explode( $email['to'] );
			self::log_message( 'Email to is not an array, but a string. RFC Exploding to ' . json_encode( $email['to'], 64 ) );
		}

		// find the admin address.
		$found_mail_item_number = -1;
		foreach ( $email['to'] as $i => $email_address ) {
			$email['to'][ $i ] = self::rfc_recode( $email['to'][ $i ] );

			$decoded = self::rfc_decode( $email_address );
			self::log_message( "Found a recipient ($i): " . json_encode( $decoded, 64 ) );
			if ( in_array( $decoded['email'], $admin_emails, true ) ) {
				self::log_message( "Found email is an admin email." );
				$found_mail_item_number = $i;
			}
		}
		if ( -1 === $found_mail_item_number ) {
			self::log_message( "No admin email found, no changes made. Continuing sending the email." );

			// not going to an admin.
			return $email;
		}

		// $to is our found admin addressee.
		$to = &$email['to'][ $found_mail_item_number ];
		$to = self::rfc_decode( $to );

		// this message is sent to the system admin.
		// we might want to send this to a different admin.
		$key = self::get_mail_key( $email['subject'] );
		if ( ! empty( $key ) ) {
			// we were able to determine a mailkey.
			$admins = get_option( 'mail_key_admins', [] );
			if ( isset( $admins[ $key ] ) && $admins[ $key ] ) {
				$the_admins = explode( ',', $admins[ $key ] );
				foreach ( $the_admins as $i => $the_admin ) {
					$the_admin = self::rfc_decode( $the_admin );
					if ( 0 === $i ) {
						if ( $the_admin['name'] === $the_admin['email'] && $to['name'] !== $to['email'] ) {
							// not rfc, just email, but the original TO has a real name.
							$the_admin['name'] = $to['name'];
						}
						$to = self::rfc_encode( $the_admin );
						self::log_message( "Changed the recipient to " . $to );
					} else {
						// extra.
						$email['to'][] = self::rfc_encode( $the_admin );
						self::log_message( "Added an extra recipient " . self::rfc_encode( $the_admin ) );
					}
				}

				return $email;
			}

			// known key, but no email set.
			// we revert to the DEFAULT admin_email, and prevent matching against subjects.
			if ( is_array( $to ) && array_key_exists( 'email', $to ) ) {
				$to = self::rfc_encode( $to );
				self::log_message( "No alternative admin email found for key $key, reverting to default admin email; " . $to );
			}

			return $email;
		}

		// perhaps we have a regexp?.
		$admin = self::mail_subject_match( $email['subject'] );
		if ( $admin ) {
			self::log_message( "Found a regexp match for the subject; {$email['subject']} -> $admin" );
			$the_admins = explode( ',', $admin );
			foreach ( $the_admins as $i => $the_admin ) {
				$the_admin = self::rfc_decode( $the_admin );
				if ( 0 === $i ) {
					if ( $the_admin['name'] === $the_admin['email'] && $to['name'] !== $to['email'] ) {
						// not rfc, just email, but the original TO has a real name.
						$the_admin['name'] = $to['name'];
					}
					$to = self::rfc_encode( $the_admin );
					self::log_message( "Changed the recipient to " . $to );
				} else {
					// extra.
					$email['to'][] = self::rfc_encode( $the_admin );
					self::log_message( "Added an extra recipient " . self::rfc_encode( $the_admin ) );
				}
			}

			return $email;
		}

		// sorry, we failed :( .
		$fails = get_option( 'mail_key_fails', [] );
		if ( $fails ) {
			$fails = array_combine( $fails, $fails );
		}
		$fails[ $email['subject'] ] = $email['subject'];
		$fails                      = array_filter(
			$fails,
			function ( $item ) {
				return ! self::mail_subject_match( $item ) && ! self::get_mail_key( $item );
			}
		);
		update_option( 'mail_key_fails', array_values( $fails ) );

		$to = self::rfc_encode( $to );

		return $email;
	}

	/**
	 * Memory call: a pingback is detected.
	 *
	 * @param null|mixed $set Set memory cell content.
	 *
	 * @return mixed
	 */
	public static function pingback_detected( $set = null ) {
		static $static;
		if ( null !== $set ) {
			$static = $set;
		}

		return $static;
	}

	/**
	 * Set the correct comment email recipient.
	 *
	 * @param string[] $email      An array of email addresses to receive a comment notification.
	 * @param int      $comment_id The comment (ID) in question.
	 *
	 * @return array|mixed
	 */
	public static function correct_comment_to( $email, $comment_id ) {
		$comment = get_comment( $comment_id );

		return self::correct_moderation_and_comments( $email, $comment, 'author' );
	}

	/**
	 * Set the correct moderation email recipient.
	 *
	 * @param string[] $email      An array of email addresses to receive a comment notification.
	 * @param int      $comment_id The comment (ID) in question.
	 *
	 * @return array|mixed
	 */
	public static function correct_moderation_to( $email, $comment_id ) {
		$comment = get_comment( $comment_id );

		return self::correct_moderation_and_comments( $email, $comment, 'moderator' );
	}

	/**
	 * Set the correct comment email recipient.
	 *
	 * @param string[]              $email   An array of email addresses to receive a comment notification.
	 * @param WP_Comment|array|null $comment Depends on $output value.
	 * @param string                $action  Which action are we doing now.
	 *
	 * @return array|mixed
	 */
	public static function correct_moderation_and_comments( $email, $comment, $action ) {

		$post_type = 'post';
		// idea: future version; allow setting per post-type.
		// trace back the post-type using: commentID -> comment -> post -> post-type.

		$c = get_option( 'mail_key_moderators', [] );
		if ( ! $c || ! is_array( $c ) ) {
			return $email;
		}

		$type = $comment->comment_type;
		$type = 'pingback' === $type || 'trackback' === $type ? 'pingback' : 'comment';

		if ( isset( $c[ $post_type ][ $action ][ $type ] ) && $c[ $post_type ][ $action ][ $type ] ) {
			// overrule ALL recipients
			// we do this at PHP_INT_MIN, so any and all plugins can overrule this; that is THEIR choice.
			$email = [ $c[ $post_type ][ $action ][ $type ] ];
			$email = array_filter(
				$email,
				function ( $el ) {
					return filter_var( $el, FILTER_VALIDATE_EMAIL );
				}
			);
		}

		return $email;
	}

	/**
	 * Get a mail-key identification tag for a know subject.
	 *
	 * @param string $subject The subject to inspect.
	 *
	 * @return string
	 */
	public static function get_mail_key( $subject ) {
		// got a filter/action name?
		$mail_key = self::current_mail_key();
		if ( $mail_key ) {
			self::current_mail_key( '*CLEAR*' );
			self::log( "$subject matched to $mail_key by filter/action" );
		} else {
			$mail_key = self::mail_subject_database( $subject );
			if ( $mail_key ) {
				self::log( "$subject matched to $mail_key by subject-matching known subjects" );
			}
		}

		return $mail_key;
	}

	/**
	 * List of know mail-tags
	 *
	 * @return string[]
	 */
	public static function mail_key_database() {
		// supported;.
		$wp_filters = [
			'automatic_updates_debug_email' => _x( 'Email after automatic update (debug)', 'mail key', 'email-essentials' ),
			'auto_core_update_email'        => _x( 'Email after automatic update', 'mail key', 'email-essentials' ),
			'recovery_mode_email'           => _x( 'Email after website crash', 'mail key', 'email-essentials' ),
		];

		// unsupported until added, @see wp_mail_key.patch, matched by subject, @see self::mail_subject_database.
		$unsupported_wp_filters = [
			'new_user_registration_admin_email' => _x( 'Email after new user registered', 'mail key', 'email-essentials' ),
			'password_lost_changed_email'       => _x( 'Email notification after user requests password reset', 'mail key', 'email-essentials' ),
			'password_reset_email'              => _x( 'Email notification after user reset their password', 'mail key', 'email-essentials' ),
			'password_changed_email'            => _x( 'Email notification after user changed their password', 'mail key', 'email-essentials' ),
			'wpes_email_test'                   => _x( 'Email test from WP Email Essentials', 'mail key', 'email-essentials' ),
		];

		return array_merge( $wp_filters, $unsupported_wp_filters );
	}

	/**
	 * List of known subjects.
	 *
	 * @param string $lookup A subject to lookup.
	 *
	 * @return false|string
	 */
	public static function mail_subject_database( $lookup ) {
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.LongIndexSpaceBeforeDoubleArrow -- PHPCS is messing up.
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned -- PHPCS is messing up.
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		// FULL TEXT LOOKUP.
		// @phpcs:disable WordPress.WP.I18n.MissingArgDomain
		// WordPress strings, do NOT use own text-domain here, this construction is here because these are WP translated strings.
		$keys = [
			sprintf( _x( '[%s] New User Registration', 'translators: ignore this.' ), $blogname ) => 'new_user_registration_admin_email',
			sprintf( _x( '[%s] Password Reset', 'translators: ignore this.' ), $blogname )        => 'password_reset_email',
			sprintf( _x( '[%s] Password Changed', 'translators: ignore this.' ), $blogname )      => 'password_changed_email',
			sprintf( _x( '[%s] Password Lost/Changed', 'translators: ignore this.' ), $blogname ) => 'password_lost_changed_email',
			self::dummy_subject()                                                                 => 'wpes_email_test',
		];
		// @phpcs:enable WordPress.WP.I18n.MissingArgDomain

		$key = $keys[ $lookup ] ?? '';

		if ( '' !== $key ) {
			return $key;
		}

		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.LongIndexSpaceBeforeDoubleArrow -- PHPCS is messing up.
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned -- PHPCS is messing up.
		return false;
	}

	/**
	 * Match a subject to a regular expression input by the admin.
	 *
	 * @param string $subject The subject to match.
	 *
	 * @return false|string
	 */
	public static function mail_subject_match( $subject ) {
		$store = get_option( 'mail_key_list', [] );
		foreach ( $store as $regexp => $mail_key ) {
			if ( preg_match( $regexp, $subject ) ) {
				return $mail_key;
			}
		}

		return false;
	}

	/**
	 * Hook into all known filters/actions known to be used prior to sending an email.
	 * this works on the mechanics that prior to sending an email, a filter or actions is hooked, a make-shift mail key
	 * actions and filters are equal to WordPress, but handled with or without return values.
	 */
	public static function mail_key_registrations() {
		foreach ( array_keys( self::mail_key_database() ) as $filter_name ) {
			add_filter( $filter_name, [ self::class, 'now_sending___' ] );
		}
	}

	/**
	 * Memory cell for current mail key.
	 *
	 * @param null|string $set The mail key currently in use.
	 *
	 * @return mixed
	 */
	private static function current_mail_key( $set = null ) {
		static $mail_key;
		if ( $set ) {
			if ( '*CLEAR*' === $set ) {
				$set = false;
			}
			$mail_key = $set;
		}

		return $mail_key;
	}

	/**
	 * Filter/action callback to set a mail key.
	 *
	 * @param mixed $value A value given by a filter to pass-thru.
	 *
	 * @return mixed
	 */
	public static function now_sending___( $value ) {
		self::current_mail_key( current_filter() );

		return $value;
	}

	/**
	 * Keep a log.
	 *
	 * Logs to system log or WP Debug log if WP_DEBUG is true.
	 * Log to Display requires WPES_DEBUG to be defined and true.
	 * Plugin local file logging requires presence of a file `log` in the plugin root folder.
	 *
	 * @param string $text The line to log.
	 */
	public static function log( $text ) {
		$text = trim( $text );

		// to enable logging, create a writable file "log" in the plugin dir.
		if ( defined( 'WPES_DEBUG' ) ) {
			print wp_kses_post( "LOG: $text\n" );

			return;
		}

		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG && defined( 'WPES_DEBUG' ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( "WP_Email_Essentials: $text" );

			return;
		}

		static $fp;
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		if ( file_exists( __DIR__ . '/../log' ) && is_writable( __DIR__ . '/../log' ) ) {
			if ( ! $fp ) {
				$fp = fopen( __DIR__ . '/../log', 'a' );
			}
			if ( $fp ) {
				// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- NO we want localized time.
				fwrite( $fp, date( 'r' ) . ' WP_Email_Essentials: ' . $text . "\n" );
			}
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_read_fopen
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_read_fwrite
	}

	/**
	 * Inject elements in existing admin panels.
	 */
	public static function maybe_inject_admin_settings() {
		$host = wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- no form processing, just checking...
		if ( 'options-general.php' === basename( $_SERVER['PHP_SELF'] ) && ! ( $_GET['page'] ?? '' ) ) {
			?>
			<script>
				jQuery("#admin_email,#new_admin_email").after('<p class="description"><?php
					// translators: %s: a URL.
					print wp_kses_post( sprintf( __( 'You can configure alternative administrators <a href="%s">here</a>.', 'email-essentials' ), add_query_arg( [ 'page' => 'wpes-admins' ], admin_url( 'admin.php' ) ) ) );
					?></p>');
			</script>
			<?php
		}

		$config = self::get_config();
		if ( ! isset( $config['make_from_valid'] ) ) {
			$config['make_from_valid'] = '';
		}
		switch ( $config['make_from_valid'] ) {
			case 'noreply':
				// translators: %1$s: a URL, %2$s: the plugin name, %3$s: website hostname.
				$text = sprintf( __( 'But <strong>please do not worry</strong>! <a href="%1$s" target="_blank">%2$s</a> will set <em class="noreply">noreply@%3$s</em> as sender and set <em>this email address</em> as Reply-To header.', 'email-essentials' ), admin_url( 'tools.php' ) . '?page=wp-email-essentials', self::plugin_data()['Name'], $host );
				break;
			case 'default':
				// translators: %1$s: a URL, %2$s: the plugin name, %3$s: email address.
				$text = sprintf( __( 'But <strong>please do not worry</strong>! <a href="%1$s" target="_blank">%2$s</a> will set <em class="default">%3$s</em> as sender and set <em>this email address</em> as Reply-To header.', 'email-essentials' ), admin_url( 'tools.php' ) . '?page=wp-email-essentials', self::plugin_data()['Name'], self::wp_mail_from( $config['from_email'] ) );
				break;
			case '-at-':
				// translators: %1$s: a URL, %2$s: the plugin name.
				$text = sprintf( __( 'But <strong>please do not worry</strong>! <a href="%1$s" target="_blank">%2$s</a> will set <em class="at-">example-email-at-youtserver-dot-com</em> as sender and set <em>this address</em> as Reply-To header.', 'email-essentials' ), admin_url( 'tools.php' ) . '?page=wp-email-essentials', self::plugin_data()['Name'] );
				break;
			default:
				// translators: %1$s: a URL, %2$s: the plugin name.
				$text = sprintf( __( 'You can fix this here, or you can let <a href="%1$s" target="_blank">%2$s</a> fix this automatically upon sending the email.', 'email-essentials' ), admin_url( 'tools.php' ) . '?page=wp-email-essentials', self::plugin_data()['Name'] );
				break;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- no form processing, just checking...
		if ( 'admin.php' === basename( $_SERVER['PHP_SELF'] ) && 'wpcf7' === ( $_GET['page'] ?? false ) ) {
			?>
			<script>
				jQuery(document).ready(function () {
					setTimeout(function () {
						var i = jQuery("#wpcf7-mail-sender,#wpcf7-mail-2-sender");
						if (i.length > 0) {
							var t = <?php print wp_json_encode( $text ); ?>,
								e = i.siblings('.config-error');

							if (e.length > 0) {
								if (e.is('ul')) {
									e.append('<li class="wpes-err-add">' + t + '</li>');
								} else {
									e.html(e.html() + '<br /><span class="wpes-err-add">' + t + '</span>');
								}
							}
						}
					}, 1000);

					var atdottify = function (rfc) {
						var email = getEmail(rfc);
						var newemail = email.replace('@', '-at-').replace(/\./g, '-dot-') + '@' + ((document.location.host).replace(/^www\./, ''));
						return rfc.replace(email, newemail);
					};

					var noreplyify = function (rfc) {
						var email = getEmail(rfc);
						var newemail = 'noreply' + '@' + ((document.location.host).replace(/^www\./, ''));
						return rfc.replace(email, newemail);
					};

					var defaultify = function (rfc) {
						var host = ((document.location.host).replace(/^www\./, ''));
						var email = getEmail(rfc);
						var newemail = <?php print wp_json_encode( self::wp_mail_from( $config['from_email'] ) ); ?>;
						if ((new RegExp('@' + host)).test(newemail))
							return rfc.replace(email, newemail);
						else
							return noreplyify(rfc);
					};

					var getEmail = function (rfc) {
						rfc = rfc.split('<');
						if (rfc.length < 2) {
							rfc.unshift('');
						}
						rfc = rfc[1].split('>');
						return rfc[0];
					};

					var i = jQuery("#wpcf7-mail-sender,#wpcf7-mail-2-sender");
					i.bind('keyup', function () {
						var e = jQuery(this).siblings('.config-error'), v = jQuery(this).val();
						if (e.length) {
							e.find('.wpes-err-add').find('em.default:nth(0)').text(noreplyify(v));
							e.find('.wpes-err-add').find('em.noreply:nth(0)').text(noreplyify(v));
							e.find('.wpes-err-add').find('em.at-:nth(0)').text(atdottify(v));
							e.find('.wpes-err-add').find('em:nth(1)').text(v);
						}
					}).trigger('keyup');

					jQuery(".wpes-err-add em").addClass('quote');
				});
			</script>
			<style>
				.wpes-err-add {

				}

				.wpes-err-add em {
					font-style: inherit;
				}

				.wpes-err-add em.quote {
					background: lightgray;
					font-family: monospace;
					font-weight: bold;
				}
			</style>
			<?php
		}
	}

	/**
	 * Get the IP using Ajax.
	 *
	 * @return void
	 */
	public static function ajax_get_ip() {
		print esc_attr( self::server_remote_addr() );
		exit;
	}

	/**
	 * This function takes a css-string and compresses it, removing unneccessary whitespace, colons, removing unneccessary px/em declarations etc.
	 *
	 * @param string $css The CSS to minify.
	 *
	 * @return string compressed css content
	 *
	 * @author Steffen Becker
	 */
	public static function minify_css( $css ) {
		// Before we minify, replace variables with their values.
		$css = self::evaluate_css_variables( $css );

		// some of the following functions to minimize the css-output are directly taken.
		// from the awesome CSS JS Booster: https://github.com/Schepp/CSS-JS-Booster .
		// all credits to Christian Schaefer: http://twitter.com/derSchepp .
		// remove comments.
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		// backup values within single or double quotes.
		preg_match_all( '/(\'[^\']*?\'|"[^"]*?")/ims', $css, $hit, PREG_PATTERN_ORDER );
		$j = count( $hit[1] );
		for ( $i = 0; $i < $j; $i++ ) {
			$css = str_replace( $hit[1][ $i ], '##########' . $i . '##########', $css );
		}
		// remove traling semicolon of selector's last property.
		$css = preg_replace( '/;[\s\r\n\t]*?}[\s\r\n\t]*/ims', "}\r\n", $css );
		// remove any whitespace between semicolon and property-name.
		$css = preg_replace( '/;[\s\r\n\t]*?([\r\n]?[^\s\r\n\t])/ims', ';$1', $css );
		// remove any whitespace surrounding property-colon.
		$css = preg_replace( '/[\s\r\n\t]*:[\s\r\n\t]*?([^\s\r\n\t])/ims', ':$1', $css );
		// remove any whitespace surrounding selector-comma.
		$css = preg_replace( '/[\s\r\n\t]*,[\s\r\n\t]*?([^\s\r\n\t])/ims', ',$1', $css );
		// remove any whitespace surrounding opening parenthesis.
		$css = preg_replace( '/[\s\r\n\t]*{[\s\r\n\t]*?([^\s\r\n\t])/ims', '{$1', $css );
		// remove any whitespace between numbers and units.
		$css = preg_replace( '/([\d\.]+)[\s\r\n\t]+(px|em|pt|%)/ims', '$1$2', $css );
		// shorten zero-values.
		$css = preg_replace( '/([^\d\.]0)(px|em|pt|%)/ims', '$1', $css );
		// constrain multiple whitespaces.
		$css = preg_replace( '/\p{Zs}+/ims', ' ', $css );
		// remove newlines.
		$css = str_replace( [ "\r\n", "\r", "\n" ], '', $css );
		// Restore backupped values within single or double quotes.
		$j = count( $hit[1] );
		for ( $i = 0; $i < $j; $i++ ) {
			$css = str_replace( '##########' . $i . '##########', $hit[1][ $i ], $css );
		}

		return $css;
	}

	/**
	 * Wrapper for php file_get_contents, simplified.
	 *
	 * @param string $filename The file to read.
	 *
	 * @return false|string
	 *
	 * @see \file_get_contents
	 */
	protected static function file_get_contents( $filename ) {
		try {
			// @phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
			// @phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$return_value = is_file( $filename ) && is_readable( $filename ) ? file_get_contents( $filename ) : false;
			// @phpcs:enable WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
			// @phpcs:enable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		} catch ( Exception $e ) {
			$return_value = false;
		}

		return $return_value;
	}

	/**
	 * Uniform title header for the templates.
	 *
	 * @param string $title_subtitle The addition to the title.
	 */
	public static function template_header( $title_subtitle ) {
		?>
		<h2 class="dashicons-before dashicons-email-alt">
			<?php print wp_kses_post( self::plugin_data()['LongName'] ); ?>
			<em><?php print wp_kses_post( self::plugin_data()['Version'] ); ?></em>
			<?php if ( '' !== $title_subtitle ) { ?>
				- <?php print esc_html( $title_subtitle ); ?>
			<?php } ?>
		</h2>
		<?php
	}

	/**
	 * Make a nice-size display format of a number of bytes.
	 *
	 * @param int $filesize The filesize.
	 *
	 * @return string
	 */
	public static function nice_size( $filesize ) {
		$filesize = absint( $filesize );
		if ( ! $filesize ) {
			return '';
		}
		$sizes = [ 'B', 'kB', 'MB', 'GB' ];
		// phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found -- Not allowed? Are you my mother???.
		while ( $filesize > 900 && count( $sizes ) > 1 ) {
			$filesize /= 1024;
			array_shift( $sizes );
		}
		$size = array_shift( $sizes );

		$digits = $filesize >= 100 ? 0 : 1;

		return sprintf( "%0.{$digits}f%s", $filesize, $size );
	}

	/**
	 * Returns the plugin version.
	 *
	 * @return string
	 */
	public static function get_wpes_version() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/plugin.php';
		}
		$plugin_path = dirname( __DIR__ ) . '/' . basename( self::SLUG );
		$plugin_data = get_plugin_data( $plugin_path );

		return $plugin_data['Version'];
	}

	/**
	 * start logging.
	 *
	 * @param mixed $pass_thru The value to pass through.
	 */
	public static function start_log( $pass_thru ) {
		$GLOBALS['wpes_log'] = [];

		return $pass_thru;
	}

	/**
	 * log a message.
	 *
	 * @param string $message The message to log.
	 */
	public static function log_message( $message ) {
		$GLOBALS['wpes_log'][] = $message;
	}

	/**
	 * get the log.
	 */
	public static function get_log() {
		return $GLOBALS['wpes_log'];
	}

	/**
	 * End the log
	 */
	public static function end_log() {
		unset( $GLOBALS['wpes_log'] );
	}

	/**
	 * Evaluate CSS variables in the given CSS string or array.
	 *
	 * @param string $css The CSS to evaluate.
	 *
	 * @return string
	 */
	private static function evaluate_css_variables( string $css ) {
		return CssVarEval::evaluate( $css );
	}

	/**
	 * Wrapper for deprecated classes, so we can show this in an admin notice.
	 *
	 * @param string $class_name  The name of the class being instantiated.
	 * @param string $version     The version of WordPress that deprecated the class.
	 * @param string $replacement Optional. The class or function that should have been called.
	 *                            Default empty string.
	 */
	public static function _deprecated_class( $class_name, $version, $replacement = '' ) {
		if ( function_exists( '_deprecated_class' ) ) {
			// Use the WordPress function if available.
			_deprecated_class( $class_name, $version, $replacement );
		}

		$tag = 'wpes_deprecated_classes_' . md5( $class_name );

		update_option( $tag, [ time(), $class_name, $version, $replacement ] );
	}

	/**
	 * Wrapper for deprecated functions, so we can show this in an admin notice.
	 *
	 * @param string $function_name The function that was called.
	 * @param string $version       The version of WordPress that deprecated the function.
	 * @param string $replacement   Optional. The function that should have been called. Default empty string.
	 */
	public static function _deprecated_function( $function_name, $version, $replacement = '' ) {
		if ( function_exists( '_deprecated_function' ) ) {
			// Use the WordPress function if available.
			_deprecated_function( $function_name, $version, $replacement );
		}
		$tag = 'wpes_deprecated_functions_' . md5( $function_name );

		update_option( $tag, [ time(), $function_name, $version, $replacement ] );
	}

	public static function apply_filters_deprecated( $hook_name, $args, $version, $replacement = '', $message = '' ) {
		if ( has_filter( $hook_name ) ) {
			$tag = 'wpes_deprecated_filters_' . md5( $hook_name );

			update_option( $tag, [ time(), $hook_name, $version, $replacement ] );

			if ( function_exists( 'apply_filters_deprecated' ) ) {
				// Use the WordPress function if available.
				return apply_filters_deprecated( $hook_name, $args, $version, $replacement, $message );
			}
		}

		return apply_filters_ref_array( $hook_name, $args );
	}

	/**
	 * Render Deprecation Notices.
	 *
	 * @param bool $on_wpes_page Whether we are on the WPES admin page.
	 *
	 * @return string
	 */
	public static function render_deprecation_notices( $on_wpes_page ) {
		// List all options that start with 'wpes_deprecated_classes_' or 'wpes_deprecated_functions_'.
		global $wpdb;
		$options   = $wpdb->get_col( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'wpes_deprecated_%'" );
		$dismissed = get_user_meta( get_current_user_id(), 'wpes_deprecation_notice_dismissed', true );
		$notices   = [];
		foreach ( $options as $option ) {
			// Get the option value.
			$option_value = get_option( $option, false );
			if ( ! $option_value ) {
				continue;
			}

			// Get the tag type.
			preg_match( '/^wpes_deprecated_(class|function|filter)e?s_(.*)$/', $option, $matches );
			$type = $matches[1] ?? 'unknown';

			// Get the time, version and replacement.
			list( $time, $foc, $version, $replacement ) = $option_value;
			$last_seen                  = human_time_diff( $time, time() );
			$more_recent_than_dismissed = ! $dismissed || $time > $dismissed;
			if ( ! $more_recent_than_dismissed ) {
				continue; // Skip if the notice was dismissed and not more recent.
			}

			// Prepare the notice.
			$notices[] = sprintf(
			// translators: %1$s: type (class/function/filter), %2$s: function/class/filter name, %3$s: time since last seen, %4$s: version, %5$s: replacement.
				__( 'Deprecated <strong>%1$s</strong> <code>%2$s</code> was seen %3$s ago. Replacement since version <strong>%4$s</strong> is <code>%5$s</code>.', 'email-essentials' ),
				esc_html( $type ),
				esc_html( $foc ),
				esc_html( $last_seen ),
				esc_html( $version ),
				$replacement ? esc_html( $replacement ) : __( 'unavailable', 'email-essentials' )
			);
		}
		if ( ! $notices ) {
			return;
		}
		$notices[] = sprintf( __( 'Email Essentials detected the use of deprecated functions or classes', 'email-essentials' ) );
		$notices   = array_reverse( $notices );
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			$notices[] = sprintf( __( 'Debug Logging is enabled in your site, please check the log for more information.', 'email-essentials' ) );
		} else {
			$notices[] = sprintf( __( 'Please enable debug logging in your site to see more information about these deprecated functions/classes/filters.', 'email-essentials' ) );
		}
		$notices = implode( '<br />', $notices );

		printf( '<div class="notice notice-warning is-dismissible wpes_deprecation_notice_dismissible"><p>%s</p></div>', $notices );

		add_action( 'admin_footer', [ self::class, 'dismiss_deprecation_notice_js' ] );
	}

	/**
	 * Dismiss the deprecation notice.
	 *
	 * @return void
	 */
	public static function dismiss_deprecation_notice() {
		update_user_meta( get_current_user_id(), 'wpes_deprecation_notice_dismissed', time() );
		wp_send_json_success();
	}

	/**
	 * Add the JavaScript to handle the dismissal of the deprecation notice.
	 *
	 * @return void
	 */
	public static function dismiss_deprecation_notice_js() {
		?>
		<script>
			jQuery(document).on('click', '.wpes_deprecation_notice_dismissible .notice-dismiss', function () {
				jQuery.post(ajaxurl, {
					action: 'dismiss_wpes_deprecation_notice'
				});
			});
		</script>
		<?php
	}

	private static $ip_trace = [];

	public static function start_ip_trace() {
		self::$ip_trace = [];
	}

	public static function ip_trace_step( $value ) {
		self::$ip_trace[] = $value;
	}

	public static function get_ip_trace() {
		return self::$ip_trace;
	}

	/**
	 * Check if the sender validation works.
	 *
	 * @return string enumeration of 'definitely', 'maybe', 'nope'.
	 */
	public static function sender_validation_works() {
		$config         = self::get_config();
		$wpes_smtp_host = $config['smtp']['host'] ?? false;
		if ( empty( $wpes_smtp_host ) ) {
			// no SMTP host, so we assume it works.
			return 'definitely';
		}
		if ( self::this_resolves_to_localhost( $wpes_smtp_host ) ) {
			// this is localhost, so we assume it works.
			return 'definitely';
		}

		// this is not localhost, but if it resolves, we can return a "maybe".
		if ( ( $ip = self::dns_get_record( $wpes_smtp_host, DNS_A | DNS_AAAA, true ) ) && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			// this is not localhost, but it resolves to a valid IP address.
			// The SPF check should also use that IP address for validation.
			return 'maybe';
		}

		// this is not localhost, and it does not resolve to a valid IP address.
		return 'nope';
	}

	public static function this_resolves_to_localhost( $host ) {
		$host = trim( $host );
		if ( empty( $host ) ) {
			return false;
		}
		self::ip_trace_step( $host );
		if ( 'localhost' === $host ) {
			return true;
		}
		if ( filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) === '127.0.0.1' ) {
			return true;
		}
		if ( filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) === '::1' ) {
			return true;
		}

		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			// this is an IP address, but it is not localhost as defined above.
			// so we assume it is not localhost.
			return false;
		}

		// this is a domain name, so we need to resolve it.
		$ip = self::dns_get_record_with_recurse( $host );
		if ( $ip ) {
			return self::this_resolves_to_localhost( $ip );
		}

		return false;
	}

	/**
	 * Get a remote address for an IP service. You can override with filter `email_essentials_ip_service` with parameter $type, or `email_essentials_ip_services` to change the array of services.
	 *
	 * @param string $type Enum ('ipv4', 'ipv6', 'dual-stack').
	 *                     ipv4 service should always return IPv4 or blank string/no data in case of failure.
	 *                     ipv6 service should always return IPv6 or blank string/no data in case of failure.
	 *                     dual-stack service should return both IPv6 if available, IPv4 if not.
	 *                     no service should return more than one IP address.
	 *                     The default service is `ip.acato.nl`, please see the readme for setting-up your own service.
	 *
	 * @return string
	 */
	private static function get_ip_service( $type ) {
		$services = [
			'ipv4'       => 'https://ip4.acato.nl',
			'ipv6'       => 'https://ip6.acato.nl',
			'dual-stack' => 'https://ip.acato.nl',
		];

		$services = apply_filters( 'email_essentials_ip_services', $services );

		return apply_filters( 'email_essentials_ip_service', $services[ $type ] ?? '', $type );
	}
}
