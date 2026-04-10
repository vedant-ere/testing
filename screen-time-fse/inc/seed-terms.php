<?php
/**
 * Default term seeding — ensures homepage patterns resolve label slugs.
 *
 * The Query block patterns (hero-featured, upcoming-movies, trending-movies)
 * filter by the rt-movie-label taxonomy using the slugs "slider", "upcoming",
 * and "trending". This file creates those terms on init so that a fresh install
 * renders the homepage sections without any manual taxonomy setup.
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inserts the default rt-movie-label terms if they do not already exist.
 *
 * Runs at priority 30 — after the plugin registers the taxonomy (≈20).
 * Bails early when the taxonomy is not available (plugin deactivated).
 *
 * @since 1.0.0
 */
function screentime_fse_seed_label_terms() {
	if ( ! taxonomy_exists( 'rt-movie-label' ) ) {
		return;
	}

	$defaults = array( 'slider', 'upcoming', 'trending' );

	foreach ( $defaults as $slug ) {
		if ( ! term_exists( $slug, 'rt-movie-label' ) ) {
			wp_insert_term( ucfirst( $slug ), 'rt-movie-label', array( 'slug' => $slug ) );
		}
	}
}
add_action( 'init', 'screentime_fse_seed_label_terms', 30 );
