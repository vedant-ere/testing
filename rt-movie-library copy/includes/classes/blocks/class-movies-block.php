<?php
/**
 * Server-side renderer for the `rt-movie-library/movies` block.
 *
 * This file owns query construction, filter application, and final HTML output
 * for the Movies list block. It is intentionally dynamic (`save = null`) so
 * published pages always reflect current movie/person/taxonomy data instead of
 * storing stale markup in post content. The implementation also batches person
 * lookups to avoid N+1 query patterns while rendering director/star labels.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Blocks;

use RT_Movie_Library\Traits\Singleton;
use WP_Post;
use WP_Query;
use RT_Movie_Library\Classes\Database\Meta_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movies_Block
 *
 * Handles server-side rendering for the Movies block.
 */
class Movies_Block {

	use Singleton;

	/**
	 * Constructor.
	 */
	protected function __construct() {}

	/**
	 * Render the movies block.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render( array $attributes ): string {
		$count        = $this->sanitize_count( $attributes['count'] ?? 6 );
		$director_id  = absint( $attributes['directorId'] ?? 0 );
		$genre_id     = absint( $attributes['genreId'] ?? 0 );
		$label_id     = absint( $attributes['labelId'] ?? 0 );
		$language_id  = absint( $attributes['languageId'] ?? 0 );
		$post_ids     = $this->sanitize_post_ids( $attributes['_post_ids'] ?? array() );
		$show_heading = ! isset( $attributes['_show_heading'] ) || (bool) $attributes['_show_heading'];

		$query_args = array(
			'post_type'              => 'rt-movie',
			'post_status'            => array( 'publish' ),
			'posts_per_page'         => $count,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		);

		if ( ! empty( $post_ids ) ) {
			$query_args['post__in'] = $post_ids;
			$query_args['orderby']  = 'post__in';
		}

		$tax_query = $this->build_tax_query(
			$director_id,
			$genre_id,
			$label_id,
			$language_id
		);

		if ( ! empty( $tax_query ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- User-selected taxonomy filters require tax_query.
			$query_args['tax_query'] = $tax_query;
		}

		$movies_query = new WP_Query( $query_args );

		if ( ! $movies_query->have_posts() ) {
			return '<p class="rt-movie-empty">' .
				esc_html__( 'No movies found.', 'rt-movie-library' ) .
				'</p>';
		}

		// Batch pre-fetch all required meta to prevent N+1 queries.
		$movie_ids = wp_list_pluck( $movies_query->posts, 'ID' );
		Meta_Repository::prefetch(
			$movie_ids,
			array(
				'rt-movie-meta-basic-release-date',
				'rt-movie-meta-basic-runtime',
			)
		);

		return $this->render_markup(
			$movies_query->posts,
			$show_heading
		);
	}

	/**
	 * Sanitize count attribute.
	 *
	 * @param mixed $count Raw count value.
	 * @return int
	 */
	private function sanitize_count( $count ): int {
		$count = absint( $count );

		if ( $count < 1 ) {
			return 6;
		}

		return min( 24, $count );
	}

	/**
	 * Sanitize explicit post ID list.
	 *
	 * @param mixed $post_ids Raw post IDs.
	 * @return array<int>
	 */
	private function sanitize_post_ids( $post_ids ): array {
		if ( ! is_array( $post_ids ) ) {
			return array();
		}

		$sanitized = array_values( array_filter( array_map( 'absint', $post_ids ) ) );

		return array_values( array_unique( $sanitized ) );
	}

	/**
	 * Build taxonomy filters from block attributes.
	 *
	 * @param int $director_id Director person post ID.
	 * @param int $genre_id Genre term ID.
	 * @param int $label_id Label term ID.
	 * @param int $language_id Language term ID.
	 * @return array<int|string,mixed>
	 */
	private function build_tax_query(
		int $director_id,
		int $genre_id,
		int $label_id,
		int $language_id
	): array {
		$tax_query = array();

		if ( $genre_id > 0 ) {
			$tax_query[] = array(
				'taxonomy' => 'rt-movie-genre',
				'field'    => 'term_id',
				'terms'    => array( $genre_id ),
			);
		}

		if ( $label_id > 0 ) {
			$tax_query[] = array(
				'taxonomy' => 'rt-movie-label',
				'field'    => 'term_id',
				'terms'    => array( $label_id ),
			);
		}

		if ( $language_id > 0 ) {
			$tax_query[] = array(
				'taxonomy' => 'rt-movie-language',
				'field'    => 'term_id',
				'terms'    => array( $language_id ),
			);
		}

		if ( $director_id > 0 ) {
			$shadow_slug = $this->get_director_shadow_slug( $director_id );

			if ( '' !== $shadow_slug ) {
				$tax_query[] = array(
					'taxonomy' => '_rt-movie-person',
					'field'    => 'slug',
					'terms'    => array( $shadow_slug ),
				);
			}
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		return $tax_query;
	}

	/**
	 * Resolve shadow taxonomy slug for a director person ID.
	 *
	 * Using the shadow taxonomy keeps filtering aligned with existing data model
	 * and avoids introducing meta-query joins for relationship matching.
	 *
	 * @param int $director_id Director post ID.
	 * @return string
	 */
	private function get_director_shadow_slug( int $director_id ): string {
		$person_post = get_post( $director_id );

		if ( ! $person_post instanceof WP_Post || 'rt-person' !== $person_post->post_type ) {
			return '';
		}

		return sanitize_title( $person_post->post_name . '-' . $director_id );
	}

	/**
	 * Render HTML markup for queried movies.
	 *
	 * @param array<WP_Post> $movies Movies.
	 * @param bool           $show_heading Whether archive heading should render.
	 * @return string
	 */
	private function render_markup(
		array $movies,
		bool $show_heading
	): string {
		ob_start();
		?>
		<div class="page-archive-movie rt-block-archive-bridge">
			<section class="movie-section">
				<div class="container">
					<?php if ( $show_heading ) : ?>
						<h2 class="section-title">
							<a href="<?php echo esc_url( get_post_type_archive_link( 'rt-movie' ) ); ?>">
								<?php esc_html_e( 'Movies', 'rt-movie-library' ); ?>
							</a>
						</h2>
					<?php endif; ?>
					<div class="movie-grid" aria-label="<?php esc_attr_e( 'Movies grid', 'rt-movie-library' ); ?>">
						<?php foreach ( $movies as $movie_post ) : ?>
							<?php
							$movie_id     = (int) $movie_post->ID;
							$release_date = (string) Meta_Repository::get( $movie_id, 'rt-movie-meta-basic-release-date', true );
							$runtime_raw  = (int) Meta_Repository::get( $movie_id, 'rt-movie-meta-basic-runtime', true );

							// Format runtime from minutes.
							$runtime_label = __( 'N/A', 'rt-movie-library' );

							if ( $runtime_raw > 0 ) {
								$rt_hrs  = (int) floor( $runtime_raw / 60 );
								$rt_mins = $runtime_raw % 60;

								if ( $rt_hrs > 0 && $rt_mins > 0 ) {
									/* translators: 1: hours, 2: minutes. */
									$runtime_label = sprintf( __( '%1$d hr %2$d min', 'rt-movie-library' ), $rt_hrs, $rt_mins );
								} elseif ( $rt_hrs > 0 ) {
									/* translators: %d: hours. */
									$runtime_label = sprintf( __( '%d hr', 'rt-movie-library' ), $rt_hrs );
								} else {
									/* translators: %d: minutes. */
									$runtime_label = sprintf( __( '%d min', 'rt-movie-library' ), $rt_mins );
								}
							}

							// Format release date with wp_date() for proper localization.
							$release_label = '';

							if ( '' !== $release_date ) {
								$timestamp = strtotime( $release_date );

								if ( false !== $timestamp ) {
									/* translators: %s: formatted release date. */
									$release_label = sprintf( __( 'Release %s', 'rt-movie-library' ), wp_date( 'jS M Y', $timestamp ) );
								}
							}

							$thumbnail_url = get_the_post_thumbnail_url( $movie_id, 'screentime-movie-card' );

							if ( ! $thumbnail_url ) {
								$thumbnail_url = get_the_post_thumbnail_url( $movie_id, 'medium' );
							}
							?>
							<article class="movie-card">
								<div class="movie-card__poster">
									<?php if ( $thumbnail_url ) : ?>
										<a href="<?php echo esc_url( get_permalink( $movie_id ) ); ?>">
											<img
												src="<?php echo esc_url( $thumbnail_url ); ?>"
												alt="<?php echo esc_attr( get_the_title( $movie_id ) ); ?>"
												loading="lazy"
											>
										</a>
									<?php endif; ?>
								</div>
								<div class="movie-card__content">
									<div class="movie-card__row">
										<h3 class="movie-card__title">
											<a href="<?php echo esc_url( get_permalink( $movie_id ) ); ?>">
												<?php echo esc_html( get_the_title( $movie_id ) ); ?>
											</a>
										</h3>
										<p class="movie-card__runtime">
											<?php echo esc_html( $runtime_label ); ?>
										</p>
									</div>
									<?php if ( '' !== $release_label ) : ?>
										<p class="movie-card__release-date">
											<?php echo esc_html( $release_label ); ?>
										</p>
									<?php endif; ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
