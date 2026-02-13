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
 * Loads global component assets first, then conditionally loads page-level
 * styles based on the current query context to avoid unnecessary CSS payload.
 *
 * @return void
 */
function screentime_enqueue_assets() {
	// Shared base stylesheet for layout, resets, and utility classes.
	wp_enqueue_style(
		'screentime-global',
		SCREENTIME_URI . '/assets/css/global.css',
		array(),
		SCREENTIME_VERSION
	);

	wp_enqueue_style(
		'screentime-component-header',
		SCREENTIME_URI . '/assets/css/components/header.css',
		array( 'screentime-global' ),
		SCREENTIME_VERSION
	);

	wp_enqueue_style(
		'screentime-component-hero-slider',
		SCREENTIME_URI . '/assets/css/components/hero-slider.css',
		array( 'screentime-global' ),
		SCREENTIME_VERSION
	);

	wp_enqueue_style(
		'screentime-component-chip',
		SCREENTIME_URI . '/assets/css/components/chip.css',
		array( 'screentime-global' ),
		SCREENTIME_VERSION
	);

	wp_enqueue_style(
		'screentime-component-movie-card',
		SCREENTIME_URI . '/assets/css/components/movie-card.css',
		array( 'screentime-global' ),
		SCREENTIME_VERSION
	);

	wp_enqueue_style(
		'screentime-component-footer',
		SCREENTIME_URI . '/assets/css/components/footer.css',
		array( 'screentime-global' ),
		SCREENTIME_VERSION
	);

	// Context-aware page styles. Keep these conditionals mutually additive.
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

	// Global behavior script used across header and shared interactions.
	wp_enqueue_script(
		'screentime-main',
		SCREENTIME_URI . '/assets/js/main.js',
		array(),
		SCREENTIME_VERSION,
		true
	);

	// Front-page-only slider behavior.
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
