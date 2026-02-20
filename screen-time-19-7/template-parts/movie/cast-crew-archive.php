<?php
/**
 * Full cast and crew archive view for a movie.
 *
 * Expected args:
 * - cards (array<int, array<string, string|int>>)
 *
 * @package ScreenTime
 */

$cards         = isset( $args['cards'] ) && is_array( $args['cards'] ) ? $args['cards'] : array();
$visible_cards = array_slice( $cards, 0, 12 );
$hidden_cards  = array_slice( $cards, 12 );
?>
<section class="movie-section" id="cast-crew">
	<div class="container">
		<h1 class="section-title section-title--person-archive"><?php esc_html_e( 'Cast & Crew', 'screen-time' ); ?></h1>

		<div class="person-list" aria-label="<?php esc_attr_e( 'Cast and crew list', 'screen-time' ); ?>">
			<?php foreach ( $visible_cards as $card ) : ?>
				<?php
				$role_text = isset( $card['role'] ) ? (string) $card['role'] : '';
				$dob       = isset( $card['birth'] ) && '' !== (string) $card['birth']
					? sprintf(
						/* translators: %s: birth date. */
						__( 'Born - %s', 'screen-time' ),
						(string) $card['birth']
					)
					: '';

				get_template_part(
					'template-parts/person-card',
					null,
					array(
						'name'  => isset( $card['name'] ) ? (string) $card['name'] : '',
						'role'  => $role_text,
						'dob'   => $dob,
						'bio'   => isset( $card['excerpt'] ) ? (string) $card['excerpt'] : '',
						'class' => 'person-card',
						'image' => isset( $card['image'] ) && '' !== (string) $card['image']
							? (string) $card['image']
							: 'assets/images/people/person-default.jpg',
						'link'  => isset( $card['link'] ) ? (string) $card['link'] : '#',
					)
				);
				?>
			<?php endforeach; ?>

			<?php foreach ( $hidden_cards as $card ) : ?>
				<?php
				$role_text = isset( $card['role'] ) ? (string) $card['role'] : '';
				$dob       = isset( $card['birth'] ) && '' !== (string) $card['birth']
					? sprintf(
						/* translators: %s: birth date. */
						__( 'Born - %s', 'screen-time' ),
						(string) $card['birth']
					)
					: '';

				get_template_part(
					'template-parts/person-card',
					null,
					array(
						'name'  => isset( $card['name'] ) ? (string) $card['name'] : '',
						'role'  => $role_text,
						'dob'   => $dob,
						'bio'   => isset( $card['excerpt'] ) ? (string) $card['excerpt'] : '',
						'class' => 'person-card person-card--hidden',
						'image' => isset( $card['image'] ) && '' !== (string) $card['image']
							? (string) $card['image']
							: 'assets/images/people/person-default.jpg',
						'link'  => isset( $card['link'] ) ? (string) $card['link'] : '#',
					)
				);
				?>
			<?php endforeach; ?>
		</div>

		<?php if ( ! empty( $hidden_cards ) ) : ?>
			<div class="load-more-wrap">
				<button class="chip chip--outline" type="button" data-cast-crew-load-more><?php esc_html_e( 'Load More', 'screen-time' ); ?></button>
			</div>
		<?php endif; ?>
	</div>
</section>
