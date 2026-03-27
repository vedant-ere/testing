<?php
/**
 * Movie Recommendations Widget.
 *
 * Displays a grid of related movies above the footer on single movie pages.
 * Widget options:
 *   - Title       : Section heading (e.g. "Related Movies").
 *   - Count       : Number of movies to show (1–12, default 3).
 *   - Taxonomy    : Relationship criterion — one of the movie taxonomies.
 *
 * Shared form() and update() behaviour lives in
 * Screentime_Recommendations_Widget_Base.
 *
 * @package ScreenTime
 */

/**
 * Class Screentime_Movie_Recommendations_Widget
 *
 * Concrete recommendation widget for rt-movie posts.
 */
class Screentime_Movie_Recommendations_Widget extends Screentime_Recommendations_Widget_Base {

	/**
	 * Constructor.
	 *
	 * Registers the widget with a unique id_base, display name.
	 */
	public function __construct() {
		parent::__construct(
			'screentime_movie_recommendations',
			__( 'Movie Recommendations', 'screen-time' ),
			array(
				'description'                 => __( 'Displays related movies above the footer on single movie pages.', 'screen-time' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	// ── Base class abstract implementations ──────────────────────────────

	/**
	 * Returns the default taxonomy slug for movie recommendations.
	 *
	 * @return string
	 */
	protected function get_default_taxonomy(): string {
		return 'rt-movie-genre';
	}

	/**
	 * Returns taxonomy slug → label pairs for the admin form select.
	 *
	 * @return array<string,string>
	 */
	protected function get_taxonomy_labels(): array {
		return array(
			'rt-movie-genre'        => __( 'Genre', 'screen-time' ),
			'rt-movie-label'        => __( 'Label', 'screen-time' ),
			'rt-movie-language'     => __( 'Language', 'screen-time' ),
			'rt-production-company' => __( 'Production Company', 'screen-time' ),
			'rt-movie-tag'          => __( 'Tag', 'screen-time' ),
		);
	}

	// ── Front-end rendering ──────────────────────────────────────────────

	/**
	 * Renders the widget front-end output.
	 *
	 * Outputs a section with a heading and a movie-grid built from
	 * movie-card template parts.
	 *
	 * @param array $args     Sidebar context args. Not used; widget owns all markup.
	 * @param array $instance Saved widget settings for this instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {

		if ( ! is_singular( 'rt-movie' ) ) {
			return;
		}

		global $post;

		$title    = sanitize_text_field( $instance['title'] ?? '' );
		$count    = max( 1, min( 12, absint( $instance['count'] ?? 3 ) ) );
		$taxonomy = sanitize_key( $instance['taxonomy'] ?? 'rt-movie-genre' );

		if ( ! in_array( $taxonomy, $this->get_allowed_taxonomies(), true ) ) {
			$taxonomy = 'rt-movie-genre';
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
					'post_type'              => 'rt-movie',
					'post_status'            => 'publish',
					'posts_per_page'         => $count,
					// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Excluding a single post (the current one) from a small bounded query.
					'post__not_in'           => array( $post_id ),
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'update_post_meta_cache' => true,
					'update_post_term_cache' => true,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for taxonomy-based movie relationship.
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
		<section class="movie-section widget-recommendations widget-recommendations--movie">
			<div class="container">
				<?php if ( '' !== $title ) : ?>
					<h2 class="section-title--page"><?php echo esc_html( $title ); ?></h2>
				<?php endif; ?>

				<?php if ( '' !== $taxonomy_label && isset( $matched_term ) ) : ?>
					<p class="widget-recommendations__subtext">
						<?php
						printf(
							/* translators: 1: taxonomy label (e.g. Genre), 2: term name (e.g. Action). */
							esc_html__( 'Recommended by %1$s: %2$s', 'screen-time' ),
							esc_html( $taxonomy_label ),
							esc_html( $matched_term->name )
						);
						?>
					</p>
				<?php endif; ?>

				<div class="movie-grid">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();

						$movie_post_id = get_the_ID();
						$subtitle      = screentime_get_movie_genre_label( $movie_post_id, 2, ' • ' );

						if ( '' === $subtitle ) {
							$subtitle = screentime_get_movie_release_label( $movie_post_id );
						}

						get_template_part(
							'template-parts/movie-card',
							null,
							array(
								'title'       => get_the_title( $movie_post_id ),
								'runtime'     => screentime_get_movie_runtime_label( $movie_post_id ),
								'subtitle'    => $subtitle,
								'genre_terms' => screentime_get_movie_genre_terms( $movie_post_id, 2 ),
								'image_url'   => screentime_get_movie_image_url( $movie_post_id, 'screentime-movie-card', false ),
								'link'        => get_permalink( $movie_post_id ),
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
