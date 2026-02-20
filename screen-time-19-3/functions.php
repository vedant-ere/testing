<?php
/**
 * Screen Time theme bootstrap.
 *
 * Defines shared constants and loads modular theme files in a stable order.
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

/**
 * Returns a cache-busting version using file modified time.
 *
 * Falls back to the theme version constant if the file is not found.
 *
 * @param string $relative_path Theme-relative file path beginning with "/".
 * @return string
 */
function screentime_asset_version( $relative_path ) {
	$normalized_path = '/' . ltrim( (string) $relative_path, '/' );
	$absolute_path   = SCREENTIME_PATH . $normalized_path;

	if ( file_exists( $absolute_path ) ) {
		return (string) filemtime( $absolute_path );
	}

	return SCREENTIME_VERSION;
}

require_once SCREENTIME_PATH . '/inc/theme-setup.php';
require_once SCREENTIME_PATH . '/inc/enqueue-assets.php';
require_once SCREENTIME_PATH . '/inc/customizer.php';
require_once SCREENTIME_PATH . '/inc/book-cpt.php';
require_once SCREENTIME_PATH . '/inc/template-functions.php';
require_once SCREENTIME_PATH . '/inc/rest.php';
