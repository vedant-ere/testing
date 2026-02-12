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
		'screentime-global',
		SCREENTIME_URI . '/assets/css/global.css',
		array(),
		SCREENTIME_VERSION
	);

	if ( is_front_page() ) {
		wp_enqueue_style(
			'screentime-page-home',
			SCREENTIME_URI . '/assets/css/pages/home.css',
			array( 'screentime-global' ),
			SCREENTIME_VERSION
		);
	}

	if ( is_post_type_archive( 'rt-movie' ) ) {
		wp_enqueue_style(
			'screentime-page-archive-movie',
			SCREENTIME_URI . '/assets/css/pages/archive-movie.css',
			array( 'screentime-global' ),
			SCREENTIME_VERSION
		);
	}

	if ( is_singular( 'rt-movie' ) ) {
		wp_enqueue_style(
			'screentime-page-single-movie',
			SCREENTIME_URI . '/assets/css/pages/single-movie.css',
			array( 'screentime-global' ),
			SCREENTIME_VERSION
		);
	}

	if ( is_post_type_archive( 'rt-person' ) ) {
		wp_enqueue_style(
			'screentime-page-archive-person',
			SCREENTIME_URI . '/assets/css/pages/archive-person.css',
			array( 'screentime-global' ),
			SCREENTIME_VERSION
		);
	}

	if ( is_singular( 'rt-person' ) ) {
		wp_enqueue_style(
			'screentime-page-single-person',
			SCREENTIME_URI . '/assets/css/pages/single-person.css',
			array( 'screentime-global' ),
			SCREENTIME_VERSION
		);
	}

	if ( is_home() || is_page() || is_404() || is_search() ) {
		wp_enqueue_style(
			'screentime-page-index',
			SCREENTIME_URI . '/assets/css/pages/index.css',
			array( 'screentime-global' ),
			SCREENTIME_VERSION
		);
	}

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
