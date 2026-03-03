<?php
/**
 * Movie card template part.
 *
 * Supports two modes:
 * 1) Dynamic loop mode for rt-movie posts.
 * 2) Static args mode used by earlier templates.
 *
 * @package ScreenTime
 */

$movie_title    = '';
$movie_runtime  = '';
$movie_subtitle = '';
$movie_image    = '';
$movie_link     = '#';
$movie_genres   = array();

if ( ! empty( $args ) && is_array( $args ) ) {
	$movie_title    = isset( $args['title'] ) ? (string) $args['title'] : '';
	$movie_runtime  = isset( $args['runtime'] ) ? (string) $args['runtime'] : '';
	$movie_subtitle = isset( $args['subtitle'] ) ? (string) $args['subtitle'] : '';
	$movie_link     = isset( $args['link'] ) ? (string) $args['link'] : '#';
	$movie_genres   = ( isset( $args['genre_terms'] ) && is_array( $args['genre_terms'] ) ) ? $args['genre_terms'] : array();

	if ( ! empty( $args['image_url'] ) ) {
		$movie_image = (string) $args['image_url'];
	} elseif ( ! empty( $args['image'] ) ) {
		$movie_image = trailingslashit( get_template_directory_uri() ) . ltrim( (string) $args['image'], '/' );
	}
} elseif ( 'rt-movie' === get_post_type() ) {
	$movie_post_id  = get_the_ID();
	$movie_title    = get_the_title();
	$movie_runtime  = screentime_get_movie_runtime_label( $movie_post_id );
	$movie_subtitle = screentime_get_movie_release_label( $movie_post_id );
	$movie_image    = screentime_get_movie_image_url( $movie_post_id, 'screentime-movie-card', false );
	$movie_link     = get_permalink( $movie_post_id );
}

if ( empty( $movie_title ) ) {
	$movie_title = __( 'Movie title', 'screen-time' );
}

if ( empty( $movie_runtime ) ) {
	$movie_runtime = __( 'N/A', 'screen-time' );
}

if ( empty( $movie_subtitle ) ) {
	$movie_subtitle = __( 'Details unavailable', 'screen-time' );
}
?>
<article class="movie-card">
	<div class="movie-card__poster">
		<?php if ( ! empty( $movie_image ) ) : ?>
			<a href="<?php echo esc_url( $movie_link ); ?>">
				<img src="<?php echo esc_url( $movie_image ); ?>" alt="<?php echo esc_attr( $movie_title ); ?>" width="384" height="411" loading="lazy">
			</a>
		<?php endif; ?>
	</div>
	<div class="movie-card__content">
		<div class="movie-card__row">
			<h3 class="movie-card__title"><a href="<?php echo esc_url( $movie_link ); ?>"><?php echo esc_html( $movie_title ); ?></a></h3>
			<p class="movie-card__runtime"><?php echo esc_html( $movie_runtime ); ?></p>
		</div>
		<?php if ( ! empty( $movie_genres ) ) : ?>
			<p class="movie-card__subtitle">
				<?php foreach ( $movie_genres as $genre_index => $genre_term ) : ?>
					<?php
					if ( ! ( $genre_term instanceof WP_Term ) ) {
						continue;
					}

					$genre_link = get_term_link( $genre_term );
					if ( is_wp_error( $genre_link ) ) {
						continue;
					}
					?>
					<?php if ( $genre_index > 0 ) : ?>
						<span aria-hidden="true"> • </span>
					<?php endif; ?>
					<a href="<?php echo esc_url( $genre_link ); ?>"><?php echo esc_html( $genre_term->name ); ?></a>
				<?php endforeach; ?>
			</p>
		<?php else : ?>
			<p class="movie-card__subtitle"><?php echo esc_html( $movie_subtitle ); ?></p>
		<?php endif; ?>
	</div>
</article>
