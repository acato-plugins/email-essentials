<?php // @phpcs:ignore Squiz.Commenting.FileComment.Missing,WordPress.Files.FileName.NotHyphenatedLowercase

/**
 * A normalised class for PHPMailer.
 *
 * @package WP_Email_Essentials
 */

namespace Acato\Email_Essentials;

use PHPMailer;

// phpcs:disable Generic.Classes.DuplicateClassName.Found

// We're not allowed to ensure PHPMailer is loaded, so we can only pray that WordPress has already done that for us.
// require_once ABSPATH . WPINC . '/class-phpmailer.php';

/**
 * A wrapper for the WP 5.4 and earlier version of PHPMailer
 */
class EEMailer extends PHPMailer {
	// The observant developer will note that there is no SingleTo patch here;
	// This is of course because old WordPress versions will not get an upgrade to the 6.0 version of PHPMailer that no longer has the SingleTo functionality.
	const Acato_Mailer_Version = 'pre-5.5';
}
