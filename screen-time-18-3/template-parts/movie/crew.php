<?php
/**
 * Movie crew section.
 *
 * Expected args:
 * - producers (array<WP_Post>)
 * - writers (array<WP_Post>)
 * - actors (array<WP_Post>)
 * - actor_characters (array<int, string>)
 *
 * @package ScreenTime
 */

$producers        = isset( $args['producers'] ) && is_array( $args['producers'] ) ? $args['producers'] : array();
$writers          = isset( $args['writers'] ) && is_array( $args['writers'] ) ? $args['writers'] : array();
$actors           = isset( $args['actors'] ) && is_array( $args['actors'] ) ? $args['actors'] : array();
$actor_characters = isset( $args['actor_characters'] ) && is_array( $args['actor_characters'] ) ? $args['actor_characters'] : array();

if ( empty( $producers ) && empty( $writers ) && empty( $actors ) ) {
	return;
}
?>

<section class="movie-single-section" id="cast-crew">
	<div class="container">
		<div class="movie-single-section__heading">
			<h2 class="section-title"><?php esc_html_e( 'Cast & Crew', 'screen-time' ); ?></h2>
		</div>

		<?php if ( ! empty( $producers ) ) : ?>
			<p><strong><?php esc_html_e( 'Producers:', 'screen-time' ); ?></strong> <?php echo esc_html( implode( ', ', wp_list_pluck( $producers, 'post_title' ) ) ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $writers ) ) : ?>
			<p><strong><?php esc_html_e( 'Writers:', 'screen-time' ); ?></strong> <?php echo esc_html( implode( ', ', wp_list_pluck( $writers, 'post_title' ) ) ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $actors ) ) : ?>
			<div class="movie-cast-grid">
				<?php foreach ( $actors as $actor ) : ?>
					<?php
					$actor_name      = $actor->post_title;
					$actor_image_url = get_the_post_thumbnail_url( $actor->ID, 'medium' );
					$character_name  = isset( $actor_characters[ $actor->ID ] ) ? $actor_characters[ $actor->ID ] : '';
					?>
					<article class="movie-cast-card">
						<?php if ( ! empty( $actor_image_url ) ) : ?>
							<img src="<?php echo esc_url( $actor_image_url ); ?>" alt="<?php echo esc_attr( $actor_name ); ?>" width="280" height="248" loading="lazy">
						<?php endif; ?>
						<h3><?php echo esc_html( $actor_name ); ?></h3>
						<?php if ( ! empty( $character_name ) ) : ?>
							<p><?php echo esc_html( $character_name ); ?></p>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
