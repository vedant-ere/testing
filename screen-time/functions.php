<?php
/**
 * Screen Time theme bootstrap.
 *
 * Defines reusable theme constants and loads modular theme setup files.
 *
 * @package ScreenTime
 */

defined( 'ABSPATH' ) || exit;

// Use a single version constant for cache-busting local assets.
if ( ! defined( 'SCREENTIME_VERSION' ) ) {
	define( 'SCREENTIME_VERSION', '1.0.0' );
}

// Absolute path used for including internal PHP modules.
if ( ! defined( 'SCREENTIME_PATH' ) ) {
	define( 'SCREENTIME_PATH', get_template_directory() );
}

// Public URI used for loading styles, scripts, and media assets.
if ( ! defined( 'SCREENTIME_URI' ) ) {
	define( 'SCREENTIME_URI', get_template_directory_uri() );
}

// Load theme modules in a predictable order during bootstrap.
require_once SCREENTIME_PATH . '/inc/theme-setup.php';
require_once SCREENTIME_PATH . '/inc/enqueue-assets.php';
require_once SCREENTIME_PATH . '/inc/book-cpt.php';
require_once SCREENTIME_PATH . '/inc/template-functions.php';
