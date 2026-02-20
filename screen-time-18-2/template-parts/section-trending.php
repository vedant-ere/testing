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
		get_template_part( 'template-parts/content', 'movie-card' );
	endwhile;
	wp_reset_postdata();
endif;
