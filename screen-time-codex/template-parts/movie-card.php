<?php
/**
 * Static movie card.
 *
 * @package ScreenTime
 */

$movie_title    = isset( $args['title'] ) ? $args['title'] : 'Movie title';
$movie_runtime  = isset( $args['runtime'] ) ? $args['runtime'] : '1 hr 14 min';
$movie_subtitle = isset( $args['subtitle'] ) ? $args['subtitle'] : 'Release: 12 Dec 2022';
$movie_image    = isset( $args['image'] ) ? $args['image'] : 'assets/images/movies/movie-default.jpg';
?>
<article class="movie-card">
	<div class="movie-card__poster">
		<img src="<?php echo esc_url( get_template_directory_uri() . '/' . $movie_image ); ?>" alt="<?php echo esc_attr( $movie_title ); ?> poster" width="384" height="411">
	</div>
	<div class="movie-card__content">
		<div class="movie-card__row">
			<h3 class="movie-card__title"><?php echo esc_html( $movie_title ); ?></h3>
			<p class="movie-card__runtime"><?php echo esc_html( $movie_runtime ); ?></p>
		</div>
		<p class="movie-card__subtitle"><?php echo esc_html( $movie_subtitle ); ?></p>
	</div>
</article>
