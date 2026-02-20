<?php
/**
 * Person archive template.
 *
 * @package ScreenTime
 */

get_header();

$posts_limit  = 12;
$current_page = max( 1, absint( get_query_var( 'paged' ) ) );

$person_query = new WP_Query(
	array(
		'post_type'              => 'rt-person',
		'post_status'            => 'publish',
		'posts_per_page'         => $posts_limit,
		'paged'                  => $current_page,
		'ignore_sticky_posts'    => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
	)
);
?>
<main class="page-archive-person">
	<section class="movie-section">
		<div class="container">
			<h1 class="section-title section-title--person-archive"><?php esc_html_e( 'Celebrities', 'screen-time' ); ?></h1>

			<div class="person-list" aria-label="<?php esc_attr_e( 'Celebrities list', 'screen-time' ); ?>" data-person-list>
				<?php if ( $person_query->have_posts() ) : ?>
					<?php while ( $person_query->have_posts() ) : ?>
						<?php
						$person_query->the_post();

						$person_id      = get_the_ID();
						$careers        = screentime_get_person_career_names( $person_id );
						$birthdate_raw  = (string) get_post_meta( $person_id, 'rt-person-meta-basic-birth-date', true );
						$birthdate_text = '';
						$image_url      = get_the_post_thumbnail_url( $person_id, 'medium' );
						$bio_text       = get_the_excerpt();

						if ( '' !== $birthdate_raw ) {
							$birthdate_timestamp = strtotime( $birthdate_raw );
							if ( false !== $birthdate_timestamp ) {
								$birthdate_text = wp_date( get_option( 'date_format' ), $birthdate_timestamp );
							} else {
								$birthdate_text = $birthdate_raw;
							}
						}

						if ( '' === $bio_text ) {
							$bio_text = wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $person_id ) ), 36, '...' );
						}

						get_template_part(
							'template-parts/person-card',
							null,
							array(
								'name'  => get_the_title(),
								'role'  => ! empty( $careers ) ? $careers[0] : '',
								'dob'   => '' !== $birthdate_text
									? sprintf(
										/* translators: %s: birth date. */
										__( 'Born - %s', 'screen-time' ),
										$birthdate_text
									)
									: '',
								'bio'   => $bio_text,
								'image' => $image_url ? $image_url : 'assets/images/people/person-default.jpg',
								'link'  => get_permalink(),
							)
						);
						?>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php endif; ?>
			</div>

			<?php if ( $person_query->max_num_pages > 1 ) : ?>
				<div class="load-more-wrap">
					<button
						class="chip chip--outline"
						type="button"
						data-person-load-more
						data-next-page="2"
						data-max-pages="<?php echo esc_attr( (string) $person_query->max_num_pages ); ?>"
					>
						<?php esc_html_e( 'Load More', 'screen-time' ); ?>
					</button>
					<p class="sr-only" aria-live="polite" data-person-load-more-status></p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
