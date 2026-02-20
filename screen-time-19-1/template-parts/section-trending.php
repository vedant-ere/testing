<?php
/**
 * Trending movies section template part.
 *
 * @package ScreenTime
 */

$trending_query = screentime_get_movies_by_label( 'trending', 6 );

if ( $trending_query->have_posts() ) :
	while ( $trending_query->have_posts() ) :
		$trending_query->the_post();
		$post_id = get_the_ID();
		$subtitle = screentime_get_movie_genre_label( $post_id, 2, ' • ' );

		if ( '' === $subtitle ) {
			$subtitle = screentime_get_movie_release_label( $post_id );
		}

		get_template_part(
			'template-parts/movie-card',
			null,
			array(
				'title'     => get_the_title( $post_id ),
				'runtime'   => screentime_get_movie_runtime_label( $post_id ),
				'subtitle'  => $subtitle,
				'image_url' => screentime_get_movie_image_url( $post_id, 'screentime-movie-card', false ),
				'link'      => get_permalink( $post_id ),
			)
		);
	endwhile;
	wp_reset_postdata();
endif;
