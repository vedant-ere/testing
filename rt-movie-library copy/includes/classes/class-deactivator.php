<?php
/**
 * Plugin deactivation handler.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes;

use RT_Movie_Library\Classes\Roles\Movie_Manager_Role;
use RT_Movie_Library\Classes\Rewrite\Rewrite_Rules;
use RT_Movie_Library\Classes\Tmdb\Tmdb_Sync;

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
		Movie_Manager_Role::deactivate();
		Tmdb_Sync::unschedule();
		Rewrite_Rules::flush_on_deactivate();
	}
}
