<?php
/**
 * Lightweight error logger for the RT Movie Library plugin.
 *
 * Centralises the single phpcs:ignore needed for error_log() so that
 * every call site stays clean.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 */
class Logger {

	/**
	 * Plugin log prefix for easy grep filtering in debug.log.
	 *
	 * @var string
	 */
	private const PREFIX = 'RT Movie Library';

	/**
	 * Write a message to the PHP error log when WP_DEBUG_LOG is enabled.
	 *
	 * @param string $message Human-readable description of the failure.
	 * @param string $context Optional context label (e.g. 'tmdb', 'role', 'cron').
	 * @return void
	 */
	public static function error( string $message, string $context = '' ): void {
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		$label = '' !== $context
			? sprintf( '[%s:%s]', self::PREFIX, $context )
			: sprintf( '[%s]', self::PREFIX );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional: plugin diagnostic logging gated behind WP_DEBUG_LOG.
		error_log( $label . ' ' . $message );
	}
}
