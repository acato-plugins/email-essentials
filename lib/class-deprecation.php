<?php // phpcs:disable -- We don't care about formatting/codestyle for this file. It will be removed in the near future.
// This file holds all classes in the deprecated namespace.
namespace WP_Email_Essentials;

defined( 'ABSPATH' ) || exit;

/**
 * This file is used to load the deprecated classes.
 * It is used to ensure that the old plugin can be replaced by the new plugin.
 * The old plugin will be replaced by the new plugin, if it is active.
 * This file is loaded by the autoloader in email-essentials.php.
 *
 * @since   6.0.0
 * @package Acato_Email_Essentials
 * @author  Remon Pel <remon@acato.nl>
 */
class CSS_Inliner extends \Acato\Email_Essentials\CSS_Inliner {
	public function __construct( $html, $css = false ) {
		\Acato\Email_Essentials\Plugin::_deprecated_class( 'WP_Email_Essentials\\CSS_Inliner', '5.0.0', 'Acato\\Email_Essentials\\CSS_Inliner' );

		parent::__construct( $html, $css );
	}
}

class CssToInlineStyles extends \Acato\Email_Essentials\CssToInlineStyles {
	public function __construct( $html = null, $css = null ) {
		\Acato\Email_Essentials\Plugin::_deprecated_class( 'WP_Email_Essentials\\CssToInlineStyles', '5.0.0', 'Acato\\Email_Essentials\\CssToInlineStyles' );

		parent::__construct( $html, $css );
	}
}

class CssVarEval extends \Acato\Email_Essentials\CssVarEval {
	public function __construct() {
		\Acato\Email_Essentials\Plugin::_deprecated_class( 'WP_Email_Essentials\\CssVarEval', '5.0.0', 'Acato\\Email_Essentials\\CssVarEval' );
	}

	public static function evaluate( $css ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\CssVarEval::evaluate', '5.0.0', 'Acato\\Email_Essentials\\CssVarEval::evaluate' );

		return parent::evaluate( $css );
	}

	public static function resolveCssVariables( $css ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\CssVarEval::resolveCssVariables', '5.0.0', 'Acato\\Email_Essentials\\CssVarEval::resolve_css_variables' );

		return parent::resolve_css_variables( $css );
	}
}

class History extends \Acato\Email_Essentials\History {
	public function __construct() {
		\Acato\Email_Essentials\Plugin::_deprecated_class( 'WP_Email_Essentials\\History', '5.0.0', 'Acato\\Email_Essentials\\History' );

		parent::__construct();
	}

	public static function instance() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\instance', '5.0.0', 'Acato\\Email_Essentials\\History::instance' );

		return parent::instance();
	}

	public static function shutdown() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\shutdown', '5.0.0', 'Acato\\Email_Essentials\\History::shutdown' );

		parent::shutdown();
	}

	public static function admin_menu() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\admin_menu', '5.0.0', 'Acato\\Email_Essentials\\History::admin_menu' );

		parent::admin_menu();
	}

	public static function admin_interface() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\admin_interface', '5.0.0', 'Acato\\Email_Essentials\\History::admin_interface' );

		parent::admin_interface();
	}

	public static function get_to_addresses( $phpmailer ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\get_to_addresses', '5.0.0', 'Acato\\Email_Essentials\\History::get_to_addresses' );

		return parent::get_to_addresses( $phpmailer );
	}

	public static function phpmailer_init( &$phpmailer ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\phpmailer_init', '5.0.0', 'Acato\\Email_Essentials\\History::phpmailer_init' );

		parent::phpmailer_init( $phpmailer );
	}

	public static function wp_mail( $data ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\wp_mail', '5.0.0', 'Acato\\Email_Essentials\\History::wp_mail' );

		return parent::wp_mail( $data );
	}

	public static function wp_mail_failed( $error ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\wp_mail_failed', '5.0.0', 'Acato\\Email_Essentials\\History::wp_mail_failed' );

		parent::wp_mail_failed( $error );
	}

	public static function wp_mail_succeeded() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\wp_mail_succeeded', '5.0.0', 'Acato\\Email_Essentials\\History::wp_mail_succeeded' );

		parent::wp_mail_succeeded();
	}

	public static function handle_tracker() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\handle_tracker', '5.0.0', 'Acato\\Email_Essentials\\History::handle_tracker' );

		return parent::handle_tracker();
	}
}

class IP extends \Acato\Email_Essentials\IP {
	public function __construct() {
		\Acato\Email_Essentials\Plugin::_deprecated_class( 'WP_Email_Essentials\\IP', '5.0.0', 'Acato\\Email_Essentials\\IP' );
	}

	public static function ip4_match_cidr( $ip, $cidr ) {
	}

	public static function is_4( $ip ) {
	}

	public static function is_4_cidr( $cidr ) {
	}

	public static function a_4_is_4( $ip1, $ip2 ) {
	}

	public static function is_6( $ip ) {
	}

	public static function a_6_is_6( $ip1, $ip2 ) {
	}

	public static function expand_ip6( $ip ) {
	}

	public static function explode_ip6( $ip ) {
	}

	public static function ip6_match_cidr( $ip, $cidr ) {
	}
}

class Migrations extends \Acato\Email_Essentials\Migrations {
	public function __construct() {
		\Acato\Email_Essentials\Plugin::_deprecated_class( 'WP_Email_Essentials\\Migrations', '5.0.0', 'Acato\\Email_Essentials\\Migrations' );
	}

	public static function plugin_activation_hook() {
	}

	public static function migrate_from_smtp_connect() {
	}

	public static function migrate_from_post_smtp() {
	}
}

class Plugin extends \Acato\Email_Essentials\Plugin {
	public function __construct() {
		\Acato\Email_Essentials\Plugin::_deprecated_class( 'WP_Email_Essentials\\Plugin', '5.0.0', 'Acato\\Email_Essentials\\Plugin' );

		parent::__construct();
	}

	public static function plugin_data() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\plugin_data', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\plugin_data' );

		return parent::plugin_data();
	}

	public static function init() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\init', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\init' );

		parent::init();
	}

	public static function jit_overload_phpmailer( $passthru ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\jit_overload_phpmailer', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\jit_overload_phpmailer' );

		return parent::jit_overload_phpmailer( $passthru );
	}

	public static function plugin_actions( $links, $file ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\plugin_actions', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\plugin_actions' );

		return parent::plugin_actions( $links, $file );
	}

	public static function root_path() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\root_path', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\root_path' );

		return parent::root_path();
	}

	public static function suggested_safe_path_for( $item ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\suggested_safe_path_for', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\suggested_safe_path_for' );

		return parent::suggested_safe_path_for( $item );
	}

	public static function path_is_in_web_root( $path ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\path_is_in_web_root', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\path_is_in_web_root' );

		return parent::path_is_in_web_root( $path );
	}

	public static function get_wp_subdir() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_wp_subdir', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_wp_subdir' );

		return parent::get_wp_subdir();
	}

	public static function nice_path( $path ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\nice_path', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\nice_path' );

		return parent::nice_path( $path );
	}

	public static function get_wordpress_default_emailaddress() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_wordpress_default_emailaddress', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_wordpress_default_emailaddress' );

		return parent::get_wordpress_default_emailaddress();
	}

	public static function correct_comment_from( $mail_headers, $comment_id ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\correct_comment_from', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\correct_comment_from' );

		return parent::correct_comment_from( $mail_headers, $comment_id );
	}

	public static function correct_cfdb_form_data_ip( $cf7 ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\correct_cfdb_form_data_ip', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\correct_cfdb_form_data_ip' );

		return parent::correct_cfdb_form_data_ip( $cf7 );
	}

	public static function server_remote_addr( $return_htaccess_variable = false ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\server_remote_addr', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\server_remote_addr' );

		return parent::server_remote_addr( $return_htaccess_variable );
	}

	public static function action_wp_mail( $wp_mail ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\action_wp_mail', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\action_wp_mail' );

		return parent::action_wp_mail( $wp_mail );
	}

	public static function patch_wp_mail( $wp_mail ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\patch_wp_mail', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\patch_wp_mail' );

		return parent::patch_wp_mail( $wp_mail );
	}

	public static function a_valid_from( $invalid_from, $method ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\a_valid_from', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\a_valid_from' );

		return parent::a_valid_from( $invalid_from, $method );
	}

	public static function get_domain( $email ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_domain', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_domain' );

		return parent::get_domain( $email );
	}

	public static function get_spf( $email, $fix = false, $as_html = false ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_spf', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_spf' );

		return parent::get_spf( $email, $fix, $as_html );
	}

	public static function get_spf_v1( $email, $fix = false, $as_html = false ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_spf_v1', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_spf_v1' );

		return parent::get_spf_v1( $email, $fix, $as_html );
	}

	public static function get_spf_v2( $email, $fix = false, $as_html = false ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_spf_v2', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_spf_v2' );

		return parent::get_spf_v2( $email, $fix, $as_html );
	}

	public static function i_am_allowed_to_send_in_name_of( $email ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\i_am_allowed_to_send_in_name_of', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\i_am_allowed_to_send_in_name_of' );

		return parent::i_am_allowed_to_send_in_name_of( $email );
	}

	public static function get_sending_ip( $force_ip4 = false ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_sending_ip', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_sending_ip' );

		return parent::get_sending_ip( $force_ip4 );
	}

	public static function validate_ip_listed_in_spf( $domain, $ip ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\validate_ip_listed_in_spf', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\validate_ip_listed_in_spf' );

		return parent::validate_ip_listed_in_spf( $domain, $ip );
	}

	public static function dns_get_record( $lookup, $filter, $single_output = null ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\dns_get_record', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\dns_get_record' );

		return parent::dns_get_record( $lookup, $filter, $single_output );
	}

	public static function this_email_matches_website_domain( $email ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\this_email_matches_website_domain', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\this_email_matches_website_domain' );

		return parent::this_email_matches_website_domain( $email );
	}

	public static function action_phpmailer_init( &$mailer ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\action_phpmailer_init', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\action_phpmailer_init' );

		parent::action_phpmailer_init( $mailer );
	}

	public static function maybe_convert_to_html( $might_be_text, $subject, $mailer, $charset = 'utf-8' ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\maybe_convert_to_html', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\maybe_convert_to_html' );

		return parent::maybe_convert_to_html( $might_be_text, $subject, $mailer, $charset );
	}

	public static function build_html( $mailer, $subject, $should_be_html, $charset = 'utf-8' ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\build_html', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\build_html' );

		return parent::build_html( $mailer, $subject, $should_be_html, $charset );
	}

	public static function wpcf7_mail_html_header() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\wpcf7_mail_html_header', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\wpcf7_mail_html_header' );

		return parent::wpcf7_mail_html_header();
	}

	public static function wpcf7_mail_html_footer() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\wpcf7_mail_html_footer', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\wpcf7_mail_html_footer' );

		return parent::wpcf7_mail_html_footer();
	}

	public static function wp_mail_from( $from = null ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\wp_mail_from', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\wp_mail_from' );

		return parent::wp_mail_from( $from = null );
	}

	public static function wp_mail_from_name( $from = null ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\wp_mail_from_name', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\wp_mail_from_name' );

		return parent::wp_mail_from_name( $from = null );
	}

	public static function filter_wp_mail_from() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\filter_wp_mail_from', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\filter_wp_mail_from' );

		return parent::filter_wp_mail_from();
	}

	public static function filter_wp_mail_from_name() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\filter_wp_mail_from_name', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\filter_wp_mail_from_name' );

		return parent::filter_wp_mail_from_name();
	}

	public static function get_config( $raw = false ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_config', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_config' );

		return parent::get_config( $raw = false );
	}

	public static function update_config( $values ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\update_config', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\update_config' );

		return parent::update_config( $values );
	}

	public static function get_hostname_by_blogurl() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_hostname_by_blogurl', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_hostname_by_blogurl' );

		return parent::get_hostname_by_blogurl();
	}

	public static function rfc_decode( $rfc ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\rfc_decode', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\rfc_decode' );

		return parent::rfc_decode( $rfc );
	}

	public static function rfc_explode( $rfc_email_string ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\rfc_explode', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\rfc_explode' );

		return parent::rfc_explode( $rfc_email_string );
	}

	public static function rfc_encode( $email_array ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\rfc_encode', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\rfc_encode' );

		return parent::rfc_encode( $email_array );
	}

	public static function save_admin_settings() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\save_admin_settings', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\save_admin_settings' );

		parent::save_admin_settings();
	}

	public static function admin_menu() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\admin_menu', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\admin_menu' );

		parent::admin_menu();
	}

	public static function view( $tpl ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\view', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\view' );

		parent::view( $tpl );
	}

	public static function admin_interface() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\admin_interface', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\admin_interface' );

		parent::admin_interface();
	}

	public static function admin_interface_admins() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\admin_interface_admins', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\admin_interface_admins' );

		parent::admin_interface_admins();
	}

	public static function admin_interface_moderators() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\admin_interface_moderators', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\admin_interface_moderators' );

		parent::admin_interface_moderators();
	}

	public static function test() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\test', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\test' );

		parent::test();
	}

	public static function dummy_subject() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\dummy_subject', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\dummy_subject' );

		return parent::dummy_subject();
	}

	public static function dummy_content() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\dummy_content', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\dummy_content' );

		return parent::dummy_content();
	}

	public static function cid_to_image( $html, $mailer ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\cid_to_image', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\cid_to_image' );

		return parent::cid_to_image( $html, $mailer );
	}

	public static function admin_notices() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\admin_notices', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\admin_notices' );

		parent::admin_notices();
	}

	public static function list_smime_identities() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\list_smime_identities', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\list_smime_identities' );

		return parent::list_smime_identities();
	}

	public static function get_smime_identity( $email ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_smime_identity', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_smime_identity' );

		return parent::get_smime_identity( $email );
	}

	public static function list_dkim_identities() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\list_dkim_identities', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\list_dkim_identities' );

		return parent::list_dkim_identities();
	}

	public static function get_dkim_identity( $email ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_dkim_identity', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_dkim_identity' );

		return parent::get_dkim_identity( $email );
	}

	public static function alternative_to( $email ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\alternative_to', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\alternative_to' );

		return parent::alternative_to( $email );
	}

	public static function pingback_detected( $set = null ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\pingback_detected', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\pingback_detected' );

		return parent::pingback_detected( $set = null );
	}

	public static function correct_comment_to( $email, $comment_id ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\correct_comment_to', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\correct_comment_to' );

		return parent::correct_comment_to( $email, $comment_id );
	}

	public static function correct_moderation_to( $email, $comment_id ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\correct_moderation_to', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\correct_moderation_to' );

		return parent::correct_moderation_to( $email, $comment_id );
	}

	public static function correct_moderation_and_comments( $email, $comment, $action ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\correct_moderation_and_comments', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\correct_moderation_and_comments' );

		return parent::correct_moderation_and_comments( $email, $comment, $action );
	}

	public static function get_mail_key( $subject ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_mail_key', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_mail_key' );

		return parent::get_mail_key( $subject );
	}

	public static function mail_key_database() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\mail_key_database', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\mail_key_database' );

		return parent::mail_key_database();
	}

	public static function mail_subject_database( $lookup ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\mail_subject_database', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\mail_subject_database' );

		return parent::mail_subject_database( $lookup );
	}

	public static function mail_subject_match( $subject ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\mail_subject_match', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\mail_subject_match' );

		return parent::mail_subject_match( $subject );
	}

	public static function mail_key_registrations() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\mail_key_registrations', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\mail_key_registrations' );

		parent::mail_key_registrations();
	}

	public static function now_sending___( $value ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\now_sending___', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\now_sending___' );

		return parent::now_sending___( $value );
	}

	public static function log( $text ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\log', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\log' );

		parent::log( $text );
	}

	public static function maybe_inject_admin_settings() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\maybe_inject_admin_settings', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\maybe_inject_admin_settings' );

		parent::maybe_inject_admin_settings();
	}

	public static function ajax_get_ip() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\ajax_get_ip', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\ajax_get_ip' );

		parent::ajax_get_ip();
	}

	public static function minify_css( $css ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\minify_css', '5.0.0', 'apply_filters(\'email_essentials_minify_css\', $css)' );

		return parent::minify_css( $css );
	}

	public static function template_header( $title_subtitle ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\template_header', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\template_header' );

		parent::template_header( $title_subtitle );
	}

	public static function nice_size( $filesize ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\nice_size', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\nice_size' );

		return parent::nice_size( $filesize );
	}

	public static function get_wpes_version() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_wpes_version', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_wpes_version' );

		return parent::get_wpes_version();
	}

	public static function start_log( $pass_thru ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\start_log', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\start_log' );

		return parent::start_log( $pass_thru );
	}

	public static function log_message( $message ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\log_message', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\log_message' );

		parent::log_message( $message );
	}

	public static function get_log() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_log', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\get_log' );

		return parent::get_log();
	}

	public static function end_log() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\end_log', '5.0.0', 'Acato\\Email_Essentials\\Plugin\\end_log' );

		parent::end_log();
	}
}

class Queue extends \Acato\Email_Essentials\Queue {
	public function __construct() {
		\Acato\Email_Essentials\Plugin::_deprecated_class( 'WP_Email_Essentials\\Queue', '5.0.0', 'Acato\\Email_Essentials\\Queue' );

		parent::__construct();
	}

	public static function instance() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\instance', '5.0.0', 'Acato\\Email_Essentials\\Queue\\instance' );

		return parent::instance();
	}

	public static function wp_mail( $mail_data ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\wp_mail', '5.0.0', 'Acato\\Email_Essentials\\Queue\\wp_mail' );

		return parent::wp_mail( $mail_data );
	}

	public static function get_time_window() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_time_window', '5.0.0', 'Acato\\Email_Essentials\\Queue\\get_time_window' );

		return parent::get_time_window();
	}

	public static function get_max_count_per_time_window() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_max_count_per_time_window', '5.0.0', 'Acato\\Email_Essentials\\Queue\\get_max_count_per_time_window' );

		return parent::get_max_count_per_time_window();
	}

	public static function get_batch_size() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_batch_size', '5.0.0', 'Acato\\Email_Essentials\\Queue\\get_batch_size' );

		return parent::get_batch_size();
	}

	public static function get_mail_priority( $mail_array ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_mail_priority', '5.0.0', 'Acato\\Email_Essentials\\Queue\\get_mail_priority' );

		return parent::get_mail_priority( $mail_array );
	}

	public static function processed_mail_headers( $headers ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\processed_mail_headers', '5.0.0', 'Acato\\Email_Essentials\\Queue\\processed_mail_headers' );

		return parent::processed_mail_headers( $headers );
	}

	public static function scheduled_task() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\scheduled_task', '5.0.0', 'Acato\\Email_Essentials\\Queue\\scheduled_task' );

		parent::scheduled_task();
	}

	public static function send_one_email() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\send_one_email', '5.0.0', 'Acato\\Email_Essentials\\Queue\\send_one_email' );

		parent::send_one_email();
	}

	public static function maybe_send_batch() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\maybe_send_batch', '5.0.0', 'Acato\\Email_Essentials\\Queue\\maybe_send_batch' );

		parent::maybe_send_batch();
	}

	public static function send_batch() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\send_batch', '5.0.0', 'Acato\\Email_Essentials\\Queue\\send_batch' );

		parent::send_batch();
	}

	public static function send_now( $id ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\send_now', '5.0.0', 'Acato\\Email_Essentials\\Queue\\send_now' );

		parent::send_now( $id );
	}

	public static function get_status( $mail_id ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_status', '5.0.0', 'Acato\\Email_Essentials\\Queue\\get_status' );

		return parent::get_status( $mail_id );
	}

	public static function is_status( $mail_id, $status ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\is_status', '5.0.0', 'Acato\\Email_Essentials\\Queue\\is_status' );

		return parent::is_status( $mail_id, $status );
	}

	public static function stop_mail( &$phpmailer ) {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\stop_mail', '5.0.0', 'Acato\\Email_Essentials\\Queue\\stop_mail' );

		parent::stop_mail( $phpmailer );
	}

	public static function admin_menu() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\admin_menu', '5.0.0', 'Acato\\Email_Essentials\\Queue\\admin_menu' );

		parent::admin_menu();
	}

	public static function admin_interface() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\admin_interface', '5.0.0', 'Acato\\Email_Essentials\\Queue\\admin_interface' );

		parent::admin_interface();
	}

	public static function get_queue_count() {
		\Acato\Email_Essentials\Plugin::_deprecated_function( 'WP_Email_Essentials\\Queue\\get_queue_count', '5.0.0', 'Acato\\Email_Essentials\\Queue\\get_queue_count' );

		return parent::get_queue_count();
	}
}

class Fake_Sender extends \Acato\Email_Essentials\Fake_Sender {
	public function __construct( $exceptions = null ) {
		\Acato\Email_Essentials\Plugin::_deprecated_class( 'WP_Email_Essentials\\Fake_Sender', '5.0.0', 'Acato\\Email_Essentials\\Fake_Sender' );

		parent::__construct( $exceptions );
	}
}

class WPES_PHPMailer extends \Acato\Email_Essentials\EEMailer {
	public function __construct( $exceptions = null ) {
		\Acato\Email_Essentials\Plugin::_deprecated_class( 'WP_Email_Essentials\\EEMailer', '5.0.0', 'Acato\\Email_Essentials\\EEMailer' );

		parent::__construct( $exceptions );
	}
}

class WPES_Queue_List_Table extends \Acato\Email_Essentials\Queue_List_Table {
	public function __construct() {
		\Acato\Email_Essentials\Plugin::_deprecated_class( 'WP_Email_Essentials\\WPES_Queue_List_Table', '5.0.0', 'Acato\\Email_Essentials\\Queue_List_Table' );

		parent::__construct();
	}
}
