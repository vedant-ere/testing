<?php
/**
 * Asset enqueueing — front-end stylesheets and scripts.
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns a cache-busting version string based on the file's mtime.
 *
 * Falls back to SCREENTIME_FSE_VERSION when the file does not exist on
 * disk (e.g. staging/production deploys without source files).
 *
 * @param string $relative_path Theme-relative path, with or without leading slash.
 * @return string
 * @since 1.0.0
 */
function screentime_fse_asset_version( $relative_path ) {
	$abs = SCREENTIME_FSE_PATH . '/' . ltrim( (string) $relative_path, '/' );
	return file_exists( $abs ) ? (string) filemtime( $abs ) : SCREENTIME_FSE_VERSION;
}

/**
 * Enqueues front-end stylesheets and scripts.
 *
 * Load order:
 *  1. Google Fonts (external, no ?ver= suffix)
 *  2. global.css  — design tokens + reset utilities (depends on fonts)
 *  3. index.css  — imports all scoped component styles (depends on global)
 *  4. navigation.js — mobile menu + search panel (deferred, footer)
 *
 * @since 1.0.0
 */
function screentime_fse_enqueue_assets() {
	// Google Fonts — null version prevents WP appending ?ver=.
	wp_enqueue_style(
		'screen-time-fse-fonts',
		'https://fonts.googleapis.com/css2?family=Big+Shoulders:opsz,wght@10..72,100..900&family=Heebo:wght@100..900&display=swap',
		array(),
		null
	);

	// Global design-token + reset stylesheet.
	wp_enqueue_style(
		'screen-time-fse-global',
		SCREENTIME_FSE_URI . '/assets/css/global.css',
		array( 'screen-time-fse-fonts' ),
		screentime_fse_asset_version( 'assets/css/global.css' )
	);

	// Component styles (imports all scoped CSS files).
	wp_enqueue_style(
		'screen-time-fse-components',
		SCREENTIME_FSE_URI . '/assets/css/index.css',
		array( 'screen-time-fse-global' ),
		screentime_fse_asset_version( 'assets/css/index.css' )
	);

	// Mobile nav + search panel — only needed when the header renders.
	wp_enqueue_script(
		'screen-time-fse-navigation',
		SCREENTIME_FSE_URI . '/assets/js/navigation.js',
		array(),
		screentime_fse_asset_version( 'assets/js/navigation.js' ),
		array( 'strategy' => 'defer', 'in_footer' => true )
	);
}
add_action( 'wp_enqueue_scripts', 'screentime_fse_enqueue_assets' );
