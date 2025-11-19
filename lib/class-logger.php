<?php
/**
 * Logger class file.
 *
 * @since   6.0.0
 * @package Acato_Email_Essentials
 * @author  Remon Pel <remon@acato.nl>
 */

namespace Acato\Email_Essentials;

/**
 * Logger class for logging messages to a file.
 *
 * This is a simple static logger that stores log messages in an array, this is to replace the use of a Global variable.
 */
class Logger {
	/**
	 * Holds the log messages.
	 *
	 * @var string[]
	 */
	private static $log;

	/**
	 * Clears the log messages.
	 */
	public static function clear() {
		self::$log = [];
	}

	/**
	 * Logs a message.
	 *
	 * @param string $message The message to log.
	 */
	public static function log( $message ) {
		self::$log[] = $message;
	}

	/**
	 * Retrieves the log messages.
	 *
	 * @return string[] The logged messages.
	 */
	public static function get() {
		return self::$log;
	}
}
