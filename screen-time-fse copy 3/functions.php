<?php
/**
 * Screen Time FSE — theme bootstrap.
 *
 * Defines theme-wide constants and loads modular include files.
 * Business logic lives in the relevant inc/ file; this file only bootstraps.
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

// ─── Theme Constants ─────────────────────────────────────────────────────────

if ( ! defined( 'SCREENTIME_FSE_VERSION' ) ) {
	define( 'SCREENTIME_FSE_VERSION', '1.0.0' );
}

if ( ! defined( 'SCREENTIME_FSE_PATH' ) ) {
	define( 'SCREENTIME_FSE_PATH', get_template_directory() );
}

if ( ! defined( 'SCREENTIME_FSE_URI' ) ) {
	define( 'SCREENTIME_FSE_URI', get_template_directory_uri() );
}

/**
 * Enqueue front-end styles for the theme.
 *
 * @return void
 */
function screentime_fse_enqueue_assets() {

	wp_enqueue_style(
		'screentime-fse-style',
		get_stylesheet_uri(),
		array(),
		SCREENTIME_FSE_VERSION
	);

	wp_enqueue_style(
		'screentime-fse-responsive',
		SCREENTIME_FSE_URI . '/assets/css/responsive.css',
		array( 'screentime-fse-style' ),
		SCREENTIME_FSE_VERSION
	);

	wp_enqueue_style(
		'screentime-mobile-bootstrap',
		SCREENTIME_FSE_URI . '/assets/css/mobile-bootstrap.css',
		array( 'screentime-fse-style' ),
		SCREENTIME_FSE_VERSION
	);

	wp_enqueue_style(
		'screentime-mobile-footer',
		SCREENTIME_FSE_URI . '/assets/css/mobile-footer.css',
		array( 'screentime-fse-style' ),
		SCREENTIME_FSE_VERSION
	);

	wp_enqueue_style(
		'screentime-mobile-only-header',
		SCREENTIME_FSE_URI . '/assets/css/mobile-only-header.css',
		array( 'screentime-fse-responsive' ),
		SCREENTIME_FSE_VERSION
	);

	wp_enqueue_script(
		'screentime-mobile-header-js',
		SCREENTIME_FSE_URI . '/assets/js/mobile-header.js',
		array(),
		SCREENTIME_FSE_VERSION,
		true
	);

	// Conditional Page Styles
	if ( is_singular( 'rt-movie' ) ) {
		wp_enqueue_style(
			'screentime-single-movie',
			SCREENTIME_FSE_URI . '/assets/css/pages/single-movie.css',
			array( 'screentime-fse-responsive' ),
			SCREENTIME_FSE_VERSION
		);
	} elseif ( is_post_type_archive( 'rt-movie' ) ) {
		wp_enqueue_style(
			'screentime-archive-movie',
			SCREENTIME_FSE_URI . '/assets/css/pages/archive.css',
			array( 'screentime-fse-responsive' ),
			SCREENTIME_FSE_VERSION
		);
	} elseif ( is_front_page() || is_home() ) {
		wp_enqueue_style(
			'screentime-home',
			SCREENTIME_FSE_URI . '/assets/css/pages/home.css',
			array( 'screentime-fse-responsive' ),
			SCREENTIME_FSE_VERSION
		);
	}
}

add_action( 'wp_enqueue_scripts', 'screentime_fse_enqueue_assets' );

/**
 * Enqueue block editor scripts.
 *
 * @return void
 */
function screentime_fse_editor_assets() {

	wp_enqueue_script(
		'screentime-block-variations',
		SCREENTIME_FSE_URI . '/assets/js/block-variations.js',
		array(
			'wp-blocks',
			'wp-dom-ready',
			'wp-edit-post',
			'wp-i18n',
		),
		SCREENTIME_FSE_VERSION,
		true
	);

	wp_set_script_translations(
		'screentime-block-variations',
		'screen-time-fse',
		SCREENTIME_FSE_PATH . '/languages'
	);
}

add_action(
	'enqueue_block_editor_assets',
	'screentime_fse_editor_assets'
);

/**
 * Register rt-movie basic meta keys so the Block Bindings API (core/post-meta source)
 * can read them in the Site Editor and on the frontend.
 */
function screentime_fse_register_movie_meta(): void {
	$meta_keys = array(
		'rt-movie-meta-basic-rating'         => 'string',
		'rt-movie-meta-basic-runtime'        => 'string',
		'rt-movie-meta-basic-release-date'   => 'string',
		'rt-movie-meta-basic-content-rating' => 'string',
	);

	foreach ( $meta_keys as $key => $type ) {
		register_post_meta(
			'rt-movie',
			$key,
			array(
				'type'         => $type,
				'single'       => true,
				'show_in_rest' => true,
			)
		);
	}
}
add_action( 'init', 'screentime_fse_register_movie_meta' );

/**
 * Make the Cast & Crew Query Loop dynamic on single rt-movie pages.
 *
 * Collects all person IDs from the movie's crew meta keys and injects
 * them as `post__in` so the Query Loop only shows people for that movie.
 *
 * @param array $query The parsed block query vars.
 * @return array       Modified query vars.
 */
function screentime_fse_filter_cast_query( array $query ): array {

	// Only act on the Cast & Crew query (rt-person post type) on a single movie page.
	if (
		! is_singular( 'rt-movie' ) ||
		empty( $query['post_type'] ) ||
		'rt-person' !== $query['post_type']
	) {
		return $query;
	}

	$movie_id = get_the_ID();

	// Collect person IDs from all crew meta keys.
	$crew_meta_keys = array(
		'rt-movie-meta-crew-director',
		'rt-movie-meta-crew-producer',
		'rt-movie-meta-crew-writer',
		'rt-movie-meta-crew-actor',
	);

	$person_ids = array();

	foreach ( $crew_meta_keys as $key ) {
		// Values are stored as a JSON array string e.g. [95,97,82] in a single meta row.
		$raw = get_post_meta( $movie_id, $key, true );
		if ( empty( $raw ) ) {
			continue;
		}
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			$person_ids = array_merge( $person_ids, array_map( 'intval', $decoded ) );
		}
	}

	$person_ids = array_unique( array_filter( $person_ids ) );

	if ( ! empty( $person_ids ) ) {
		$query['post__in'] = $person_ids;
	} else {
		// No crew found — return nothing rather than all persons.
		$query['post__in'] = array( 0 );
	}

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'screentime_fse_filter_cast_query', 10, 1 );

/**
 * Modify rt-movie post type arguments to use rt-movie as the archive slug.
 *
 * @param array  $args      Post type arguments.
 * @param string $post_type Post type name.
 * @return array
 */
function screentime_fse_modify_movie_slugs( $args, $post_type ) {
	if ( 'rt-movie' === $post_type ) {
		$args['has_archive'] = 'rt-movie';
		$args['rewrite']     = array(
			'slug'       => 'rt-movie',
			'with_front' => false,
		);
	}
	return $args;
}
add_filter( 'register_post_type_args', 'screentime_fse_modify_movie_slugs', 10, 2 );
