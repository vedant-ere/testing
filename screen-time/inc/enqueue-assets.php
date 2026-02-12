<?php
/**
 * Enqueue theme assets.
 *
 * @package ScreenTime
 */

add_action( 'wp_enqueue_scripts', 'screentime_enqueue_assets' );

/**
 * Enqueue styles and scripts.
 *
 * @return void
 */
function screentime_enqueue_assets() {
	wp_enqueue_style(
		'screentime-main',
		SCREENTIME_URI . '/assets/css/main.css',
		array(),
		SCREENTIME_VERSION
	);

	wp_enqueue_style(
		'screentime-components',
		SCREENTIME_URI . '/assets/css/components.css',
		array( 'screentime-main' ),
		SCREENTIME_VERSION
	);

	wp_enqueue_style(
		'screentime-layouts',
		SCREENTIME_URI . '/assets/css/layouts.css',
		array( 'screentime-main', 'screentime-components' ),
		SCREENTIME_VERSION
	);

	wp_enqueue_style(
		'screentime-responsive',
		SCREENTIME_URI . '/assets/css/responsive.css',
		array( 'screentime-main', 'screentime-components', 'screentime-layouts' ),
		SCREENTIME_VERSION
	);

	wp_enqueue_script(
		'screentime-main',
		SCREENTIME_URI . '/assets/js/main.js',
		array(),
		SCREENTIME_VERSION,
		true
	);

	if ( is_front_page() ) {
		wp_enqueue_script(
			'screentime-slider',
			SCREENTIME_URI . '/assets/js/slider.js',
			array(),
			SCREENTIME_VERSION,
			true
		);
	}
}
