<?php
/**
 * Filters deprecated in versions 5.0.0 and 6.0.0 are mapped to the new filter names.
 * This file adds filters to the new filter names, which call the deprecated filters in order.
 * This way, old code using the deprecated filters will still work.
 * The use of an old filter will trigger a deprecation notice.
 *
 * @since   6.0.0
 * @package Acato_Email_Essentials
 * @author  Remon Pel <remon@acato.nl>
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'acato_email_essentials_mail_is_throttled',
	function ( $is_throttled, $ip, $mails_recently_sent ) {
		// First check for 5.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated(
			'wpes_mail_is_throttled',
			[ $is_throttled, $ip, $mails_recently_sent ],
			'5.0.0',
			'acato_email_essentials_mail_is_throttled'
		);
		// Then check for 6.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated(
			'email_essentials_mail_is_throttled',
			[ $intermediate, $ip, $mails_recently_sent ],
			'6.0.0',
			'acato_email_essentials_mail_is_throttled'
		);

		return $intermediate;
	},
	-12345,
	3
);

add_filter(
	'acato_email_essentials_css',
	function ( $css, &$mailer ) {
		// First check for 5.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated(
			'wpes_css',
			[
				$css,
				&$mailer,
			],
			'5.0.0',
			'acato_email_essentials_css'
		);
		// Then check for 6.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated(
			'email_essentials_css',
			[
				$intermediate,
				&$mailer,
			],
			'6.0.0',
			'acato_email_essentials_css'
		);

		return $intermediate;
	},
	-12345,
	2
);

add_filter(
	'acato_email_essentials_subject',
	function ( $subject, &$mailer ) {
		// First check for 5.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated(
			'wpes_subject',
			[
				$subject,
				&$mailer,
			],
			'5.0.0',
			'acato_email_essentials_subject'
		);
		// Then check for 6.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated(
			'email_essentials_subject',
			[
				$intermediate,
				&$mailer,
			],
			'6.0.0',
			'acato_email_essentials_subject'
		);

		return $intermediate;
	},
	-12345,
	2
);

add_filter(
	'acato_email_essentials_body',
	function ( $should_be_html, &$mailer ) {
		// First check for 5.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated(
			'wpes_body',
			[
				$should_be_html,
				&$mailer,
			],
			'5.0.0',
			'acato_email_essentials_body'
		);
		// Then check for 6.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated(
			'email_essentials_body',
			[
				$intermediate,
				&$mailer,
			],
			'6.0.0',
			'acato_email_essentials_body'
		);

		return $intermediate;
	},
	-12345,
	2
);

add_filter(
	'acato_email_essentials_head',
	function ( $head, &$mailer ) {
		// First check for 5.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated(
			'wpes_head',
			[
				$head,
				&$mailer,
			],
			'5.0.0',
			'acato_email_essentials_head'
		);
		// Then check for 6.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated(
			'email_essentials_head',
			[
				$intermediate,
				&$mailer,
			],
			'6.0.0',
			'acato_email_essentials_head'
		);

		return $intermediate;
	},
	-12345,
	2
);

add_filter(
	'acato_email_essentials_defaults',
	function ( $defaults ) {
		// First check for 5.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'wpes_defaults', [ $defaults ], '5.0.0', 'acato_email_essentials_defaults' );
		// Then check for 6.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'email_essentials_defaults', [ $intermediate ], '6.0.0', 'acato_email_essentials_defaults' );

		return $intermediate;
	},
	-12345
);

add_filter(
	'acato_email_essentials_settings',
	function ( $settings ) {
		// First check for 5.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'wpes_settings', [ $settings ], '5.0.0', 'acato_email_essentials_settings' );
		// Then check for 6.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'email_essentials_settings', [ $intermediate ], '6.0.0', 'acato_email_essentials_settings' );

		return $intermediate;
	},
	-12345
);

add_filter(
	'acato_email_essentials_mail_throttle_time_window',
	function ( $time_window ) {
		// First check for 5.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'wpes_mail_throttle_time_window', [ $time_window ], '5.0.0', 'acato_email_essentials_mail_throttle_time_window' );
		// Then check for 6.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'email_essentials_mail_throttle_time_window', [ $intermediate ], '6.0.0', 'acato_email_essentials_mail_throttle_time_window' );

		return $intermediate;
	},
	-12345
);

add_filter(
	'acato_email_essentials_mail_throttle_max_count_per_time_window',
	function ( $count ) {
		// First check for 5.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'wpes_mail_throttle_max_count_per_time_window', [ $count ], '5.0.0', 'acato_email_essentials_mail_throttle_max_count_per_time_window' );
		// Then check for 6.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'email_essentials_mail_throttle_max_count_per_time_window', [ $intermediate ], '6.0.0', 'acato_email_essentials_mail_throttle_max_count_per_time_window' );

		return $intermediate;
	},
	-12345
);

add_filter(
	'acato_email_essentials_mail_throttle_batch_size',
	function ( $size ) {
		// First check for 5.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'wpes_mail_throttle_batch_size', [ $size ], '5.0.0', 'acato_email_essentials_mail_throttle_batch_size' );
		// Then check for 6.0.0 deprecated filter.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'email_essentials_mail_throttle_batch_size', [ $intermediate ], '6.0.0', 'acato_email_essentials_mail_throttle_batch_size' );

		return $intermediate;
	},
	-12345
);

add_filter(
	'acato_email_essentials_minify_css',
	function ( $css ) {
		// Check for 6.0.0 deprecated filter (no 5.0.0 version existed).
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'email_essentials_minify_css', [ $css ], '6.0.0', 'acato_email_essentials_minify_css' );

		return $intermediate;
	},
	-12345
);

add_filter(
	'acato_email_essentials_ip_services',
	function ( $services ) {
		// Check for 6.0.0 deprecated filter (no 5.0.0 version existed).
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'email_essentials_ip_services', [ $services ], '6.0.0', 'acato_email_essentials_ip_services' );

		return $intermediate;
	},
	-12345
);

add_filter(
	'acato_email_essentials_ip_service',
	function ( $service, $type ) {
		// Check for 6.0.0 deprecated filter (no 5.0.0 version existed).
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated(
			'email_essentials_ip_service',
			[
				$service,
				$type,
			],
			'6.0.0',
			'acato_email_essentials_ip_service'
		);

		return $intermediate;
	},
	-12345,
	2
);

add_filter(
	'acato_email_essentials_website_root_path',
	function ( $root_path ) {
		// Check for 6.0.0 deprecated filter (no 5.0.0 version existed).
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'email_essentials_website_root_path', [ $root_path ], '6.0.0', 'acato_email_essentials_website_root_path' );

		return $intermediate;
	},
	-12345
);

add_filter(
	'acato_email_essentials_development_tlds',
	function ( $tlds ) {
		// Check for 6.0.0 deprecated filter. This filter does not have a 5.0.0 deprecation as I forgot to add it then.
		$intermediate = \Acato\Email_Essentials\Plugin::apply_filters_deprecated( 'wpes_local_tlds', [ $tlds ], '6.0.0', 'acato_email_essentials_development_tlds' );

		return $intermediate;
	},
	-12345
);
