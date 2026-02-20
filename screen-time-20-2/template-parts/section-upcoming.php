<?php
/**
 * Upcoming movies section template part.
 *
 * @package ScreenTime
 */

$upcoming_query = screentime_get_movies_by_label( 'upcoming', 6 );

if ( $upcoming_query->have_posts() ) :
	while ( $upcoming_query->have_posts() ) :
		$upcoming_query->the_post();
		get_template_part( 'template-parts/content', 'movie-card' );
	endwhile;
	wp_reset_postdata();
endif;
