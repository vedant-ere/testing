<?php
/**
 * Person Recommendations Widget.
 *
 * Displays a grid of related persons above the footer on single person pages.
 * Card layout and data-fetching mirror the person archive page.
 *
 * Shared form() and update() behaviour lives in
 * Screentime_Recommendations_Widget_Base.
 *
 * @package ScreenTime
 */

/**
 * Class Screentime_Person_Recommendations_Widget
 *
 * Concrete recommendation widget for rt-person posts.
 */
class Screentime_Person_Recommendations_Widget extends Screentime_Recommendations_Widget_Base {

	/**
	 * Constructor.
	 *
	 * Registers the widget with a unique id_base, display name, and description.
	 */
	public function __construct() {
		parent::__construct(
			'screentime_person_recommendations',
			__( 'Person Recommendations', 'screen-time' ),
			array(
				'description'                 => __( 'Displays related persons above the footer on single person pages.', 'screen-time' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	// ── Base class abstract implementations ──────────────────────────────

	/**
	 * Returns the default taxonomy slug for person recommendations.
	 *
	 * @return string
	 */
	protected function get_default_taxonomy(): string {
		return 'rt-person-career';
	}

	/**
	 * Returns taxonomy slug → label pairs for the admin form select.
	 * A single entry causes the base class to render the select as disabled.
	 *
	 * @return array<string,string>
	 */
	protected function get_taxonomy_labels(): array {
		return array(
			'rt-person-career' => __( 'Career', 'screen-time' ),
		);
	}

	// ── Front-end rendering ──────────────────────────────────────────────

	/**
	 * Renders the widget front-end output.
	 *
	 * Outputs a section with a heading and a person-list built from
	 * person-card template parts — identical markup to the person archive.
	 *
	 * @param array $args     Sidebar context args. Not used; widget owns all markup.
	 * @param array $instance Saved widget settings for this instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {

		if ( ! is_singular( 'rt-person' ) ) {
			return;
		}

		global $post;

		$title    = sanitize_text_field( $instance['title'] ?? '' );
		$count    = max( 1, min( 12, absint( $instance['count'] ?? 3 ) ) );
		$taxonomy = sanitize_key( $instance['taxonomy'] ?? 'rt-person-career' );

		if ( ! in_array( $taxonomy, $this->get_allowed_taxonomies(), true ) ) {
			$taxonomy = 'rt-person-career';
		}

		$post_id = absint( $post->ID );
		$terms   = get_the_terms( $post_id, $taxonomy );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}

		// Try each term individually so the first term with related posts wins;
		// a single multi-term tax_query would dilute relevance.
		$query = null;

		foreach ( $terms as $term ) {
			$candidate = new WP_Query(
				array(
					'post_type'              => 'rt-person',
					'post_status'            => 'publish',
					'posts_per_page'         => $count,
					// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Excluding a single post (the current one) from a small bounded query.
					'post__not_in'           => array( $post_id ),
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'update_post_meta_cache' => true,
					'update_post_term_cache' => true,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for taxonomy-based person relationship.
					'tax_query'              => array(
						array(
							'taxonomy' => $taxonomy,
							'field'    => 'term_id',
							'terms'    => $term->term_id,
						),
					),
				)
			);

			if ( $candidate->have_posts() ) {
				$query        = $candidate;
				$matched_term = $term;
				break;
			}

			wp_reset_postdata();
		}

		if ( null === $query ) {
			return;
		}

		$taxonomy_labels = $this->get_taxonomy_labels();
		$taxonomy_label  = $taxonomy_labels[ $taxonomy ] ?? '';
		?>
		<section class="movie-section widget-recommendations widget-recommendations--person">
			<div class="container">
				<?php if ( '' !== $title ) : ?>
					<h2 class="section-title--page"><?php echo esc_html( $title ); ?></h2>
				<?php endif; ?>

				<?php if ( '' !== $taxonomy_label && isset( $matched_term ) ) : ?>
					<p class="widget-recommendations__subtext">
						<?php
						printf(
							/* translators: 1: taxonomy label (e.g. Career), 2: term name (e.g. Actor). */
							esc_html__( 'Recommended by %1$s: %2$s', 'screen-time' ),
							esc_html( $taxonomy_label ),
							esc_html( $matched_term->name )
						);
						?>
					</p>
				<?php endif; ?>

				<div class="person-list" aria-label="<?php esc_attr_e( 'Recommended celebrities', 'screen-time' ); ?>">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();

						$person_id      = get_the_ID();
						$careers        = screentime_get_person_career_names( $person_id );
						$birthdate_raw  = (string) get_post_meta( $person_id, 'rt-person-meta-basic-birth-date', true );
						$birthdate_text = '';
						$image_url      = '';
						$bio_text       = get_the_excerpt();
						$thumbnail_id   = get_post_thumbnail_id( $person_id );

						if ( $thumbnail_id ) :
							$image_url = wp_get_attachment_image_url( $thumbnail_id, 'medium' );

							if ( ! $image_url ) :
								$image_url = wp_get_attachment_image_url( $thumbnail_id, 'full' );
							endif;
						endif;

						if ( '' !== $birthdate_raw ) :
							$birthdate_timestamp = strtotime( $birthdate_raw );
							if ( false !== $birthdate_timestamp ) :
								$birthdate_text = wp_date( get_option( 'date_format' ), $birthdate_timestamp );
							else :
								$birthdate_text = $birthdate_raw;
							endif;
						endif;

						if ( '' === $bio_text ) :
							$bio_text = wp_trim_words(
								wp_strip_all_tags( (string) get_post_field( 'post_content', $person_id ) ),
								36,
								'...'
							);
						endif;

						$role = '';
						if ( ! empty( $careers ) ) {
							$role = $careers[0];
						}

						$dob = '';
						if ( '' !== $birthdate_text ) {
							$dob = sprintf(
								/* translators: %s: birth date. */
								__( 'Born - %s', 'screen-time' ),
								$birthdate_text
							);
						}

						get_template_part(
							'template-parts/person-card',
							null,
							array(
								'name'  => get_the_title(),
								'role'  => $role,
								'dob'   => $dob,
								'bio'   => $bio_text,
								'image' => $image_url,
								'link'  => get_permalink(),
							)
						);
					endwhile;
					?>
				</div>
			</div>
		</section>
		<?php

		wp_reset_postdata();
	}
}
