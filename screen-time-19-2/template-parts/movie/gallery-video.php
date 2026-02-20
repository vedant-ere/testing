<?php
/**
 * Movie video gallery section.
 *
 * Expected args:
 * - items (array<int, array<string, string|int>>)
 *
 * @package ScreenTime
 */

$items = isset( $args['items'] ) && is_array( $args['items'] ) ? $args['items'] : array();
?>

<?php if ( ! empty( $items ) ) : ?>
	<section class="movie-single-section" id="trailers">
		<div class="container">
			<h2 class="section-title"><?php esc_html_e( 'Trailer & Clips', 'screen-time' ); ?></h2>
			<div class="movie-trailer-grid">
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					$poster = ! empty( $item['thumb'] ) ? (string) $item['thumb'] : '';
					if ( '' === $poster ) {
						$fallback_index = ( $index % 3 ) + 1;
						$poster         = get_template_directory_uri() . '/assets/images/movies/trailer-' . $fallback_index . '.png';
					}
					?>
					<button
						type="button"
						class="movie-trailer-card"
						aria-label="<?php echo esc_attr( sprintf( __( 'Play trailer %d', 'screen-time' ), $index + 1 ) ); ?>"
						data-video-url="<?php echo esc_url( (string) $item['url'] ); ?>">
						<img
							src="<?php echo esc_url( $poster ); ?>"
							alt="<?php echo esc_attr( sprintf( __( 'Trailer %d', 'screen-time' ), $index + 1 ) ); ?>"
							width="384"
							height="246">
						<span class="movie-trailer-card__play" aria-hidden="true">▶</span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>
