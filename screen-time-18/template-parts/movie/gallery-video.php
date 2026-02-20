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
					<article class="movie-trailer-card" aria-label="<?php echo esc_attr( sprintf( __( 'Video %d', 'screen-time' ), $index + 1 ) ); ?>">
						<video width="384" height="246" controls preload="metadata" <?php echo ! empty( $item['thumb'] ) ? 'poster="' . esc_url( (string) $item['thumb'] ) . '"' : ''; ?>>
							<source src="<?php echo esc_url( (string) $item['url'] ); ?>" type="<?php echo esc_attr( (string) $item['mime'] ); ?>">
						</video>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>
