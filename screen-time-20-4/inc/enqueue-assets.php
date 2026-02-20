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
		screentime_asset_version( '/assets/css/global.css' )
	);

	wp_enqueue_style(
		'screentime-component-header',
		SCREENTIME_URI . '/assets/css/components/header.css',
		array( 'screentime-global' ),
		screentime_asset_version( '/assets/css/components/header.css' )
	);

	wp_enqueue_style(
		'screentime-component-hero-slider',
		SCREENTIME_URI . '/assets/css/components/hero-slider.css',
		array( 'screentime-global' ),
		screentime_asset_version( '/assets/css/components/hero-slider.css' )
	);

	wp_enqueue_style(
		'screentime-component-chip',
		SCREENTIME_URI . '/assets/css/components/chip.css',
		array( 'screentime-global' ),
		screentime_asset_version( '/assets/css/components/chip.css' )
	);

	wp_enqueue_style(
		'screentime-component-movie-card',
		SCREENTIME_URI . '/assets/css/components/movie-card.css',
		array( 'screentime-global' ),
		screentime_asset_version( '/assets/css/components/movie-card.css' )
	);

	wp_enqueue_style(
		'screentime-component-footer',
		SCREENTIME_URI . '/assets/css/components/footer.css',
		array( 'screentime-global' ),
		screentime_asset_version( '/assets/css/components/footer.css' )
	);

	// Additive polish layer loaded last to preserve existing stylesheet files.
	wp_enqueue_style(
		'screentime-component-ui-polish',
		SCREENTIME_URI . '/assets/css/components/ui-polish.css',
		array(
			'screentime-global',
			'screentime-component-header',
			'screentime-component-hero-slider',
			'screentime-component-chip',
			'screentime-component-movie-card',
			'screentime-component-footer',
		),
		screentime_asset_version( '/assets/css/components/ui-polish.css' )
	);

	// Context-aware page styles. Keep these conditionals mutually additive.
	if ( is_front_page() ) {
		wp_enqueue_style(
			'screentime-page-home',
			SCREENTIME_URI . '/assets/css/pages/home.css',
			array( 'screentime-global' ),
			screentime_asset_version( '/assets/css/pages/home.css' )
		);
	}

	if ( is_post_type_archive( 'rt-movie' ) || is_tax( 'rt-movie-genre' ) ) {
		wp_enqueue_style(
			'screentime-page-archive-movie',
			SCREENTIME_URI . '/assets/css/pages/archive-movie.css',
			array( 'screentime-global' ),
			screentime_asset_version( '/assets/css/pages/archive-movie.css' )
		);
	}

	if ( is_singular( 'rt-movie' ) ) {
		wp_enqueue_style(
			'screentime-page-single-movie',
			SCREENTIME_URI . '/assets/css/pages/single-movie.css',
			array( 'screentime-global' ),
			screentime_asset_version( '/assets/css/pages/single-movie.css' )
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query arg only toggles presentation mode.
		$view_mode         = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
		$is_cast_crew_view = 'cast-crew' === $view_mode;
		if ( $is_cast_crew_view ) {
			wp_enqueue_style(
				'screentime-page-movie-cast-crew',
				SCREENTIME_URI . '/assets/css/pages/archive-person.css',
				array( 'screentime-global' ),
				screentime_asset_version( '/assets/css/pages/archive-person.css' )
			);
		}
	}

	if ( is_post_type_archive( 'rt-person' ) ) {
		wp_enqueue_style(
			'screentime-page-archive-person',
			SCREENTIME_URI . '/assets/css/pages/archive-person.css',
			array( 'screentime-global' ),
			screentime_asset_version( '/assets/css/pages/archive-person.css' )
		);
	}

	if ( is_singular( 'rt-person' ) ) {
		wp_enqueue_style(
			'screentime-page-single-person',
			SCREENTIME_URI . '/assets/css/pages/single-person.css',
			array( 'screentime-global' ),
			screentime_asset_version( '/assets/css/pages/single-person.css' )
		);
	}

	if ( is_home() || is_page() || is_404() || is_search() ) {
		wp_enqueue_style(
			'screentime-page-index',
			SCREENTIME_URI . '/assets/css/pages/index.css',
			array( 'screentime-global' ),
			screentime_asset_version( '/assets/css/pages/index.css' )
		);
	}

	// Global behavior script used across header and shared interactions.
	wp_enqueue_script(
		'screentime-main',
		SCREENTIME_URI . '/assets/js/main.js',
		array(),
		screentime_asset_version( '/assets/js/main.js' ),
		true
	);

	wp_localize_script(
		'screentime-main',
		'screenTimeUi',
		array(
			'openMenuLabel'   => __( 'Open menu', 'screen-time' ),
			'closeMenuLabel'  => __( 'Close menu', 'screen-time' ),
			/* translators: %s: person name. */
			'portraitPattern' => __( '%s portrait', 'screen-time' ),
			'i18n'            => array(
				'loadMore'   => __( 'Load More', 'screen-time' ),
				'loading'    => __( 'Loading...', 'screen-time' ),
				'learnMore'  => __( 'Learn more', 'screen-time' ),
				'bornPrefix' => __( 'Born -', 'screen-time' ),
				'error'      => __( 'Unable to load more people right now.', 'screen-time' ),
				'noMore'     => __( 'No more people to load.', 'screen-time' ),
			),
		)
	);

	// Secondary behavior layer: focus trap and state hooks.
	wp_enqueue_script(
		'screentime-ui-polish',
		SCREENTIME_URI . '/assets/js/ui-polish.js',
		array( 'screentime-main' ),
		screentime_asset_version( '/assets/js/ui-polish.js' ),
		true
	);

	if ( is_post_type_archive( 'rt-person' ) ) {
		wp_localize_script(
			'screentime-main',
			'screenTimePersonArchive',
			array(
				'endpoint'      => rest_url( 'wp/v2/rt-person' ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'perPage'       => 12,
				'fallbackImage' => SCREENTIME_URI . '/assets/images/people/person-default.jpg',
				'i18n'          => array(
					'loadMore'   => __( 'Load More', 'screen-time' ),
					'loading'    => __( 'Loading...', 'screen-time' ),
					'learnMore'  => __( 'Learn more', 'screen-time' ),
					'bornPrefix' => __( 'Born -', 'screen-time' ),
					'error'      => __( 'Unable to load more people right now.', 'screen-time' ),
					'noMore'     => __( 'No more people to load.', 'screen-time' ),
				),
			)
		);
	}

	// Front-page-only slider behavior.
	if ( is_front_page() ) {
		wp_enqueue_script(
			'screentime-slider',
			SCREENTIME_URI . '/assets/js/slider.js',
			array(),
			screentime_asset_version( '/assets/js/slider.js' ),
			true
		);
	}
}
