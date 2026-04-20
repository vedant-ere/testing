<?php
/**
 * PHPUnit bootstrap file
 *
 * @package RT_Movie_Library
 */

define( 'TESTS_PLUGIN_DIR', dirname( __FILE__, 3 ) );

// Determine correct location for plugins directory to use.
define( 'WP_PLUGIN_DIR', dirname( dirname( TESTS_PLUGIN_DIR ) ) );
define( 'WP_PHPUNIT__DIR', TESTS_PLUGIN_DIR . '/vendor/wp-phpunit/wp-phpunit/' );
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', TESTS_PLUGIN_DIR . '/vendor/yoast/phpunit-polyfills' );

// Load Composer dependencies if applicable.
if ( file_exists( TESTS_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
    require_once TESTS_PLUGIN_DIR . '/vendor/autoload.php';
}

$_test_root = WP_PHPUNIT__DIR;

require_once $_test_root . '/includes/functions.php';

/**
 * Load plugin in test env.
 *
 * @return void
 */
function rt_movie_library_unit_test_load_plugin_file() {
    require_once TESTS_PLUGIN_DIR . '/rt-movie-library.php';
}

tests_add_filter( 'muplugins_loaded', 'rt_movie_library_unit_test_load_plugin_file' );

require $_test_root . '/includes/bootstrap.php';
