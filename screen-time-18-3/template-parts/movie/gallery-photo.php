<?php
/**
 * Movie photo gallery section.
 *
 * Expected args:
 * - items (array<int, array<string, string|int>>)
 *
 * @package ScreenTime
 */

$items = isset( $args['items'] ) && is_array( $args['items'] ) ? $args['items'] : array();
?>

<?php if ( ! empty( $items ) ) : ?>
	<section class="movie-single-section" id="snapshots">
		<div class="container">
			<h2 class="section-title"><?php esc_html_e( 'Snapshots', 'screen-time' ); ?></h2>
			<div class="movie-snapshot-grid">
				<?php foreach ( $items as $index => $item ) : ?>
					<img src="<?php echo esc_url( (string) $item['url'] ); ?>" alt="<?php echo esc_attr( ! empty( $item['alt'] ) ? (string) $item['alt'] : sprintf( __( 'Snapshot %d', 'screen-time' ), $index + 1 ) ); ?>" width="592" height="419" loading="lazy">
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>
