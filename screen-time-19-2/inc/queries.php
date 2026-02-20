<?php
/**
 * Query helper functions.
 *
 * @package ScreenTime
 */

/**
 * Returns a movie query by label term slug.
 *
 * @param string $term_slug Term slug in rt-movie-label taxonomy.
 * @param int    $limit     Number of posts.
 * @return WP_Query
 */
function screentime_get_movies_by_label( $term_slug, $limit = 6 ) {
	$args = array(
		'post_type'              => 'rt-movie',
		'post_status'            => 'publish',
		'posts_per_page'         => max( 1, absint( $limit ) ),
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
		'tax_query'              => array(
			array(
				'taxonomy' => 'rt-movie-label',
				'field'    => 'slug',
				'terms'    => sanitize_key( $term_slug ),
			),
		),
	);

	return new WP_Query( $args );
}
