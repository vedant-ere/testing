<?php
/**
 * Plugin Name: Movie Library
 * Description: Registers Movie & Person post types with related taxonomies.
 * Version: 1.0.0
 * Author: Vedant Ere
 * Text Domain: rt-movie-library
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 *
 * @package RT_Movie_Library
 */

defined( 'ABSPATH' ) || exit;

define( 'RT_MOVIE_LIBRARY_PATH', plugin_dir_path( __FILE__ ) );
define( 'RT_MOVIE_LIBRARY_URL', plugin_dir_url( __FILE__ ) );
define( 'RT_MOVIE_LIBRARY_VERSION', '1.0.0' );

/**
 * The current custom-table schema version.
 * Bump this (e.g. '1.1.0') whenever you change the table structure.
 * The upgrade routine below will detect the version mismatch on the
 * next page load and re-run dbDelta() automatically.
 */
define( 'RT_MOVIE_LIBRARY_DB_VERSION', '1.0.0' );

require_once RT_MOVIE_LIBRARY_PATH . 'includes/helpers/autoloader.php';

add_action( 'init', 'rt_register_custom_tables', 1 );
// Ensure custom tables are registered early for WP CLI and other operations that may run before 'init'.
add_action( 'switch_blog', 'rt_register_custom_tables' );

/**
 * Registers custom table names to the $wpdb global.
 *
 * Ensures that other parts of the plugin can access the custom tables
 * using $wpdb->rt_movie_meta and $wpdb->rt_person_meta.
 *
 * @return void
 */
function rt_register_custom_tables(): void {
	global $wpdb;
	$wpdb->rt_movie_meta  = $wpdb->prefix . 'rt_movie_meta';
	$wpdb->rt_person_meta = $wpdb->prefix . 'rt_person_meta';
}


add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain(
			'rt-movie-library',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		// DB upgrade check — runs on every page load but only performs work
		// when the installed schema version is behind the current version.
		// This handles sites that update the plugin without reactivating it.
		$installed = get_option( 'rt_movie_library_db_version', '0.0.0' );
		if ( version_compare( $installed, RT_MOVIE_LIBRARY_DB_VERSION, '<' ) ) {
			rt_register_custom_tables(); // Ensure $wpdb properties are set.
			RT_Movie_Library\Classes\Database\Movie_Meta_Table::create_table();
			RT_Movie_Library\Classes\Database\Person_Meta_Table::create_table();
			update_option( 'rt_movie_library_db_version', RT_MOVIE_LIBRARY_DB_VERSION );
		}

		RT_Movie_Library\Classes\Tmdb\Tmdb_Sync::get_instance();
		RT_Movie_Library\Classes\Rewrite\Rewrite_Rules::get_instance();
		RT_Movie_Library\Classes\Plugin::get_instance();
	}
);

register_activation_hook(
	__FILE__,
	array( RT_Movie_Library\Classes\Activator::class, 'activate' )
);

register_deactivation_hook(
	__FILE__,
	array( RT_Movie_Library\Classes\Deactivator::class, 'deactivate' )
);
