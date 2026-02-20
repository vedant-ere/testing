<?php
/**
 * Front Page Template.
 *
 * Displays the home page layout for the ScreenTime theme.
 *
 * Sections rendered:
 * - Hero slider (featured / carousel movies)
 * - Upcoming Movies grid
 * - Trending Movies grid
 *
 * Movie data is fetched using label-based queries
 * via `screentime_get_movies_by_label()`.
 *
 * @package ScreenTime
 */

get_header();

/**
 * Query for upcoming movies.
 *
 * Returns up to 6 published `rt-movie` posts
 * assigned to the "upcoming" label.
 *
 * @var WP_Query $upcoming_query
 */
$upcoming_query = screentime_get_movies_by_label( 'upcoming', 6 );

/**
 * Query for trending movies.
 *
 * Returns up to 6 published `rt-movie` posts
 * assigned to the "trending" label.
 *
 * @var WP_Query $trending_query
 */
$trending_query = screentime_get_movies_by_label( 'trending', 6 );
?>

<main class="page-home">
	<?php
	/**
	 * Hero slider section.
	 *
	 * Renders the main carousel showcasing featured movies.
	 */
	get_template_part( 'template-parts/slider' );
	?>

	<section class="movie-section">
		<div class="container">
			<h2 class="section-title">
				<?php esc_html_e( 'Upcoming Movies', 'screen-time' ); ?>
			</h2>

			<div class="movie-grid movie-grid--scroll-mobile">
				<?php if ( $upcoming_query->have_posts() ) : ?>
					<?php while ( $upcoming_query->have_posts() ) : ?>
						<?php
						/**
						 * Upcoming movie card.
						 *
						 * Uses `template-parts/movie-card.php`
						 * to render a single movie item.
						 */
						$upcoming_query->the_post();
						get_template_part( 'template-parts/movie-card' );
						?>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No upcoming movies found.', 'screen-time' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="movie-section">
		<div class="container">
			<h2 class="section-title">
				<?php esc_html_e( 'Trending Now', 'screen-time' ); ?>
			</h2>

			<div class="movie-grid movie-grid--scroll-mobile">
				<?php if ( $trending_query->have_posts() ) : ?>
					<?php while ( $trending_query->have_posts() ) : ?>
						<?php
						/**
						 * Trending movie card.
						 *
						 * Uses `template-parts/movie-card.php`
						 * to render a single movie item.
						 */
						$trending_query->the_post();
						get_template_part( 'template-parts/movie-card' );
						?>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No trending movies found.', 'screen-time' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>
</main>

<?php
/**
 * Footer output.
 *
 * Closes the front page layout and loads the theme footer.
 */
get_footer();
