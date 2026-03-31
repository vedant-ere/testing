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

		$movie_ids = wp_list_pluck( $movies_query->posts, 'ID' );

		$director_map = $this->decode_crew_meta( $movie_ids, 'rt-movie-meta-crew-director' );
		$actor_map    = $this->decode_crew_meta( $movie_ids, 'rt-movie-meta-crew-actor' );

		$person_ids       = $this->collect_person_ids( $movie_ids, $director_map, $actor_map );
		$person_title_map = $this->batch_fetch_person_titles( $person_ids );

		return $this->render_markup(
			$movies_query->posts,
			$director_map,
			$actor_map,
			$person_title_map,
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
	 * Decode JSON crew meta into per-movie person IDs map.
	 *
	 * @param array<int> $movie_ids Movie IDs.
	 * @param string     $meta_key Crew meta key.
	 * @return array<int,array<int>>
	 */
	private function decode_crew_meta( array $movie_ids, string $meta_key ): array {
		$decoded_map = array();

		foreach ( $movie_ids as $movie_id ) {
			$raw_value = get_post_meta( $movie_id, $meta_key, true );
			$decoded   = is_string( $raw_value ) ? json_decode( $raw_value, true ) : array();

			if ( ! is_array( $decoded ) ) {
				$decoded_map[ $movie_id ] = array();
				continue;
			}

			$decoded_map[ $movie_id ] = array_values(
				array_filter( array_map( 'absint', $decoded ) )
			);
		}

		return $decoded_map;
	}

	/**
	 * Collect person IDs required for render labels.
	 *
	 * We only fetch first director and first two actors per movie because those
	 * are the only names displayed in this block UI.
	 *
	 * @param array<int>            $movie_ids Movie IDs.
	 * @param array<int,array<int>> $director_map Directors map.
	 * @param array<int,array<int>> $actor_map Actors map.
	 * @return array<int>
	 */
	private function collect_person_ids(
		array $movie_ids,
		array $director_map,
		array $actor_map
	): array {
		$person_ids = array();

		foreach ( $movie_ids as $movie_id ) {
			$director_ids = $director_map[ $movie_id ] ?? array();
			$actor_ids    = $actor_map[ $movie_id ] ?? array();

			if ( ! empty( $director_ids ) ) {
				$person_ids[] = $director_ids[0];
			}

			$person_ids = array_merge(
				$person_ids,
				array_slice( $actor_ids, 0, 2 )
			);
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $person_ids ) ) ) );
	}

	/**
	 * Fetch person titles for required IDs in one query.
	 *
	 * @param array<int> $person_ids Person IDs.
	 * @return array<int,string>
	 */
	private function batch_fetch_person_titles( array $person_ids ): array {
		if ( empty( $person_ids ) ) {
			return array();
		}

		$persons_query = new WP_Query(
			array(
				'post_type'              => 'rt-person',
				'post__in'               => $person_ids,
				'post_status'            => 'publish',
				'posts_per_page'         => count( $person_ids ),
				'orderby'                => 'post__in',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$title_map = array();

		foreach ( $persons_query->posts as $person_post ) {
			$title_map[ (int) $person_post->ID ] = (string) $person_post->post_title;
		}

		return $title_map;
	}

	/**
	 * Render HTML markup for queried movies.
	 *
	 * @param array<WP_Post>        $movies Movies.
	 * @param array<int,array<int>> $director_map Directors map.
	 * @param array<int,array<int>> $actor_map Actors map.
	 * @param array<int,string>     $person_title_map Person title map.
	 * @param bool                  $show_heading Whether archive heading should render.
	 * @return string
	 */
	private function render_markup(
		array $movies,
		array $director_map,
		array $actor_map,
		array $person_title_map,
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
					<div class="movie-grid">
						<?php foreach ( $movies as $movie_post ) : ?>
							<?php
							$movie_id      = (int) $movie_post->ID;
							$release_date  = (string) get_post_meta( $movie_id, 'rt-movie-meta-basic-release-date', true );
							$director_ids  = $director_map[ $movie_id ] ?? array();
							$actor_ids     = array_slice( $actor_map[ $movie_id ] ?? array(), 0, 2 );
							$director_name = ! empty( $director_ids ) ? ( $person_title_map[ $director_ids[0] ] ?? '' ) : '';

							$actor_names = array();

							foreach ( $actor_ids as $actor_id ) {
								$actor_name = $person_title_map[ $actor_id ] ?? '';

								if ( '' !== $actor_name ) {
									$actor_names[] = $actor_name;
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
											<?php echo esc_html( '' !== $release_date ? $release_date : __( 'N/A', 'rt-movie-library' ) ); ?>
										</p>
									</div>

									<?php if ( '' !== $director_name ) : ?>
										<p class="movie-card__subtitle">
										<?php
										printf(
											/* translators: %s: director name. */
											esc_html__( 'Director: %s', 'rt-movie-library' ),
											esc_html( $director_name )
										);
										?>
									</p>
								<?php endif; ?>

									<?php if ( ! empty( $actor_names ) ) : ?>
										<p class="movie-card__subtitle">
										<?php
										printf(
											/* translators: %s: actor names list. */
											esc_html__( 'Stars: %s', 'rt-movie-library' ),
											esc_html( implode( ', ', $actor_names ) )
										);
										?>
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
