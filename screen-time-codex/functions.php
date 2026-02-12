<?php
/**
 * Screen Time Theme bootstrap.
 *
 * @package ScreenTime
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SCREENTIME_VERSION' ) ) {
	define( 'SCREENTIME_VERSION', '1.0.0' );
}

if ( ! defined( 'SCREENTIME_PATH' ) ) {
	define( 'SCREENTIME_PATH', get_template_directory() );
}

if ( ! defined( 'SCREENTIME_URI' ) ) {
	define( 'SCREENTIME_URI', get_template_directory_uri() );
}

require_once SCREENTIME_PATH . '/inc/theme-setup.php';
require_once SCREENTIME_PATH . '/inc/enqueue-assets.php';
require_once SCREENTIME_PATH . '/inc/template-functions.php';
