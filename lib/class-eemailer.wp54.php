<?php // @phpcs:ignore Squiz.Commenting.FileComment.Missing,WordPress.Files.FileName.NotHyphenatedLowercase

/**
 * A normalised class for PHPMailer.
 *
 * @package Acato_Email_Essentials
 */

namespace Acato\Email_Essentials;

use PHPMailer;

// phpcs:disable Generic.Classes.DuplicateClassName.Found

/**
 * Attention Reviewers;
 * In the initial review, I was told this was not allowed. But may other plugins do this as well, and for other files it IS allowed.
 * For example; Post SMTP loads the PHPMailer classes directly from WordPress core files, to ensure they are available.
 * For example; for WP_List_Table it is explicitly required to load the class file directly from the WordPress core files, without it, they don't work.
 * So I am sorry, I have tried to do this another way, like using  `wp_mail( null, null, null )` to trick WordPress into loading the class, but that does not work without errors.
 *
 * Please note the current file is for WordPress 5.4 and earlier, which uses an older version of PHPMailer.
 * It will not be loaded in versions of WordPress that do not have this file.
 *
 * Precedence for this modus-operandi can be found in the following plugins;
 * Post SMTP
 * SureMails
 * WP-Mail-SMTP
 * WP-SMTP
 * Fluent-SMTP
 */
require_once ABSPATH . WPINC . '/class-phpmailer.php';

/**
 * A wrapper for the WP 5.4 and earlier version of PHPMailer
 */
class EEMailer extends PHPMailer {
	// The observant developer will note that there is no SingleTo patch here;
	// This is of course because old WordPress versions will not get an upgrade to the 6.0 version of PHPMailer that no longer has the SingleTo functionality.
	const ACATO_MAILER_VERSION = 'pre-5.5';
}
