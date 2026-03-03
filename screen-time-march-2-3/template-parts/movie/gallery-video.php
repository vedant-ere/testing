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
			<h2 class="section-title--page"><?php esc_html_e( 'Trailer & Clips', 'screen-time' ); ?></h2>
			<div class="movie-trailer-grid">
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					$video_url = isset( $item['url'] ) ? (string) $item['url'] : '';
					$poster    = screentime_get_video_thumbnail_url(
						$video_url,
						isset( $item['thumb'] ) ? (string) $item['thumb'] : ''
					);

					/* translators: %d: trailer number. */
					$play_trailer_label = sprintf( __( 'Play trailer %d', 'screen-time' ), $index + 1 );
					/* translators: %d: trailer number. */
					$trailer_label = sprintf( __( 'Trailer %d', 'screen-time' ), $index + 1 );
					?>
					<button
						type="button"
						class="movie-trailer-card"
						aria-label="<?php echo esc_attr( $play_trailer_label ); ?>"
						data-video-url="<?php echo esc_url( $video_url ); ?>">
						<?php if ( '' !== $poster ) : ?>
							<img
								src="<?php echo esc_url( $poster ); ?>"
								alt="<?php echo esc_attr( $trailer_label ); ?>"
								width="384"
								height="246">
						<?php endif; ?>
						<span class="movie-trailer-card__play" aria-hidden="true">▶</span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>
