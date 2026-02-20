<?php
/**
 * Movie metadata row for single template.
 *
 * Expected args:
 * - rating (string)
 * - year (string)
 * - content_rating (string)
 * - runtime (string)
 * - genre_terms (array<int, WP_Term>)
 * - genres (array<int, string>) Fallback for static labels.
 * - languages (array)
 *
 * @package ScreenTime
 */

$rating         = isset( $args['rating'] ) ? (string) $args['rating'] : '';
$release_year   = isset( $args['year'] ) ? (string) $args['year'] : '';
$content_rating = isset( $args['content_rating'] ) ? (string) $args['content_rating'] : '';
$runtime        = isset( $args['runtime'] ) ? (string) $args['runtime'] : '';
$genre_terms    = isset( $args['genre_terms'] ) && is_array( $args['genre_terms'] ) ? $args['genre_terms'] : array();
$genres         = isset( $args['genres'] ) && is_array( $args['genres'] ) ? $args['genres'] : array();
$languages      = isset( $args['languages'] ) && is_array( $args['languages'] ) ? $args['languages'] : array();
?>

<p class="movie-single-hero__rating-row">
	<?php if ( ! empty( $rating ) ) : ?>
		<span class="movie-single-hero__star" aria-hidden="true">★</span>
		<strong><?php echo esc_html( $rating ); ?>/10</strong>
	<?php endif; ?>
	<?php if ( ! empty( $release_year ) ) : ?>
		<span aria-hidden="true">•</span><span><?php echo esc_html( $release_year ); ?></span>
	<?php endif; ?>
	<?php if ( ! empty( $content_rating ) ) : ?>
		<span aria-hidden="true">•</span><span><?php echo esc_html( $content_rating ); ?></span>
	<?php endif; ?>
	<?php if ( ! empty( $runtime ) ) : ?>
		<span aria-hidden="true">•</span><span><?php echo esc_html( strtoupper( $runtime ) ); ?></span>
	<?php endif; ?>
</p>

<?php if ( ! empty( $genre_terms ) ) : ?>
	<ul class="movie-single-hero__genres" aria-label="<?php esc_attr_e( 'Genres', 'screen-time' ); ?>">
		<?php foreach ( $genre_terms as $genre_term ) : ?>
			<?php
			$genre_link = get_term_link( $genre_term );
			if ( is_wp_error( $genre_link ) ) {
				continue;
			}
			?>
			<li
				data-genre-link="<?php echo esc_url( $genre_link ); ?>"
				role="link"
				tabindex="0"
				aria-label="<?php echo esc_attr( $genre_term->name ); ?>"
			>
				<?php echo esc_html( $genre_term->name ); ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php elseif ( ! empty( $genres ) ) : ?>
	<ul class="movie-single-hero__genres" aria-label="<?php esc_attr_e( 'Genres', 'screen-time' ); ?>">
		<?php foreach ( $genres as $genre ) : ?>
			<li><?php echo esc_html( $genre ); ?></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<?php if ( ! empty( $languages ) ) : ?>
	<p class="movie-single-hero__directors"><strong><?php esc_html_e( 'Language:', 'screen-time' ); ?></strong> <?php echo esc_html( implode( ', ', $languages ) ); ?></p>
<?php endif; ?>
