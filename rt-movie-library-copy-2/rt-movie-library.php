<?php
/**
 * Plugin Name: Movie Library
 * Description: Registers Movie & Person post types with related taxonomies.
 * Version: 1.0.0
 * Author: Vedant Ere
 * Text Domain: rt-movie-library
 * Requires PHP: 8.0
 *
 * @package RT_Movie_Library
 */

defined( 'ABSPATH' ) || exit;

define( 'RT_MOVIE_LIBRARY_PATH', plugin_dir_path( __FILE__ ) );
define( 'RT_MOVIE_LIBRARY_URL', plugin_dir_url( __FILE__ ) );
define( 'RT_MOVIE_LIBRARY_VERSION', '1.0.0' );

require_once RT_MOVIE_LIBRARY_PATH . 'includes/helpers/autoloader.php';

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain(
			'rt-movie-library',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

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
