<?php
/**
 * Movie crew section.
 *
 * Expected args:
 * - movie_id (int)
 * - cards (array<int, array<string, string|int>>)
 *
 * @package ScreenTime
 */

$movie_id = isset( $args['movie_id'] ) ? absint( $args['movie_id'] ) : 0;
$cards    = isset( $args['cards'] ) && is_array( $args['cards'] ) ? $args['cards'] : array();
$cards    = array_slice( $cards, 0, 8 );

if ( empty( $cards ) ) {
	return;
}

$view_all_url = $movie_id > 0
	? add_query_arg( 'view', 'cast-crew', get_permalink( $movie_id ) )
	: '#';
?>

<section class="movie-single-section" id="cast-crew">
	<div class="container">
		<div class="movie-single-section__heading">
			<h2 class="section-title--page"><?php esc_html_e( 'Cast & Crew', 'screen-time' ); ?></h2>
			<a href="<?php echo esc_url( $view_all_url ); ?>" class="movie-single-section__view-all movie-single-section__view-all--top"><?php esc_html_e( 'View All', 'screen-time' ); ?> →</a>
		</div>

		<div class="movie-cast-grid">
			<?php foreach ( $cards as $card ) : ?>
				<?php $person_link = ! empty( $card['link'] ) ? (string) $card['link'] : '#'; ?>
				<article class="movie-cast-card">
					<?php if ( ! empty( $card['image'] ) ) : ?>
						<a href="<?php echo esc_url( $person_link ); ?>">
							<img src="<?php echo esc_url( (string) $card['image'] ); ?>" alt="<?php echo esc_attr( (string) $card['name'] ); ?>" width="280" height="248" loading="lazy">
						</a>
					<?php endif; ?>
					<h3><a href="<?php echo esc_url( $person_link ); ?>"><?php echo esc_html( (string) $card['name'] ); ?></a></h3>
				</article>
			<?php endforeach; ?>
		</div>

		<a href="<?php echo esc_url( $view_all_url ); ?>" class="movie-single-section__view-all movie-single-section__view-all--bottom"><?php esc_html_e( 'View All', 'screen-time' ); ?> →</a>
	</div>
</section>
