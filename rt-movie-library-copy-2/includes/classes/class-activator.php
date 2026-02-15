<?php
/**
 * Plugin activation handler.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Activator.
 *
 * Handles tasks to perform on plugin activation.
 */
class Activator {

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {

		// Ensure CPTs and taxonomies are registered once.
		Plugin::get_instance()->register();

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		flush_rewrite_rules( false );
	}
}
