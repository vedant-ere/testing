<?php
/**
 * PHPUnit bootstrap file
 *
 * @package RT_Movie_Library
 */

define( 'TESTS_PLUGIN_DIR', dirname( __DIR__, 2 ) );

// Determine correct location for plugins directory to use.
define( 'WP_PLUGIN_DIR', dirname( dirname( TESTS_PLUGIN_DIR ) ) );
define( 'WP_PHPUNIT__DIR', TESTS_PLUGIN_DIR . '/vendor/wp-phpunit/wp-phpunit/' );
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', TESTS_PLUGIN_DIR . '/vendor/yoast/phpunit-polyfills' );

/**
 * Fail early with a clear message when WP test config path is missing.
 *
 * Raw phpunit runs often fail with vague missing-constant errors when
 * WP_PHPUNIT__TESTS_CONFIG is unset or points to a non-existent file.
 */
$tests_config_path = getenv( 'WP_PHPUNIT__TESTS_CONFIG' );
$tests_config_path = false === $tests_config_path ? '' : trim( $tests_config_path );

if ( '' === $tests_config_path || ! file_exists( $tests_config_path ) ) {
	echo "PHPUnit preflight failed: WP_PHPUNIT__TESTS_CONFIG is missing or invalid.\n"
		. "Current value is unset or points to a missing file.\n\n"
		. "Use the supported command from the plugin root:\n"
		. "  - npm run test:local\n\n"
		. "See TESTING.md for setup details.\n";

	exit( 1 );
}

// Load Composer dependencies if applicable.
if ( file_exists( TESTS_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
	require_once TESTS_PLUGIN_DIR . '/vendor/autoload.php';
}

require_once WP_PHPUNIT__DIR . '/includes/functions.php';

/**
 * Load plugin in test env.
 *
 * @return void
 */
function rt_movie_library_unit_test_load_plugin_file() {
	require_once TESTS_PLUGIN_DIR . '/rt-movie-library.php';
}

tests_add_filter( 'muplugins_loaded', 'rt_movie_library_unit_test_load_plugin_file' );

require WP_PHPUNIT__DIR . '/includes/bootstrap.php';
