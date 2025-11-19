<?php // phpcs:ignore library_core_files -- False positive. We're not including our own version, we're wrapping the core version.
/**
 * Overloading the phpMailer object - When we want to block all outgoing email -- we're throttling email sending -- we use this class.
 *
 * @package Acato\Email_Essentials
 */

namespace Acato\Email_Essentials;

defined( 'ABSPATH' ) || exit;

/**
 * The class that allows a phpMailer object that cannot send an email.
 */
class Fake_Sender extends EEMailer {
	/**
	 * Overloaded method Send: this does NOT send an email ;) .
	 *
	 * @return bool
	 */
	public function send() {
		return true;
	}
}
