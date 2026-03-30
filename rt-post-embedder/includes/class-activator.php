<?php
/**
 * Plugin activation handler.
 *
 * @package RT_Post_Embedder
 */

namespace RT_Post_Embedder;

defined( 'ABSPATH' ) || exit;

/**
 * Class Activator
 */
class Activator {

	/**
	 * Run activation tasks.
	 *
	 * @return void
	 */
	public static function activate(): void {
		Custom_Posts_Cpt::get_instance()->register();
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- Needed so CPT routes work right after activation.
		flush_rewrite_rules( false );
	}
}
