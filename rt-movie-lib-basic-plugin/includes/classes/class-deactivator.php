<?php
/**
 * Plugin deactivation handler.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Deactivator.
 *
 * Handles tasks to perform on plugin deactivation.
 */
class Deactivator {

	/**
	 * Run on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		flush_rewrite_rules( false );
	}
}
