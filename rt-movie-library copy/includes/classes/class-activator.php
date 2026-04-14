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
use RT_Movie_Library\Classes\Database\Movie_Meta_Table;
use RT_Movie_Library\Classes\Database\Person_Meta_Table;

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

		// Register table names on $wpdb now — the 'init' hook has not fired
		// yet during activation callbacks so we call the registration manually.
		rt_register_custom_tables();

		// Create (or upgrade) the custom meta tables.
		Movie_Meta_Table::create_table();
		Person_Meta_Table::create_table();

		// Store the initial DB schema version so the upgrade routine in
		// plugins_loaded does not re-run unnecessarily on the next load.
		update_option( 'rt_movie_library_db_version', RT_MOVIE_LIBRARY_DB_VERSION );
	}
}
