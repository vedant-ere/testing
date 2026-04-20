<?php
/**
 * Plugin activation handler.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes;

use RT_Movie_Library\Classes\Roles\Movie_Manager_Role;
use RT_Movie_Library\Classes\Rewrite\Rewrite_Rules;
use RT_Movie_Library\Classes\Tmdb\Tmdb_Sync;

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
		Movie_Manager_Role::activate();
		Rewrite_Rules::flush_on_activate();
		Tmdb_Sync::schedule();
	}
}
