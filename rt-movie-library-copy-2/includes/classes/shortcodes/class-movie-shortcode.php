<?php
/**
 * Movie Shortcode.
 *
 * Shortcode: [movie]
 *
 * Displays list of movies with:
 * - Title
 * - Poster
 * - Release Date
 * - Director
 * - First two Actors
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Shortcodes;

use RT_Movie_Library\Traits\Singleton;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Shortcode.
 * Purpose: Handles the [movie] shortcode to display movies.
 */
class Movie_Shortcode {


	use Singleton;

	/**
	 * Registers shortcode.
	 */
	protected function __construct() {
		add_shortcode( 'movie', array( $this, 'render' ) );
	}

	/**
	 * Renders the [movie] shortcode output uses WP_Query to fetch movies based on
	 * taxonomy filters: person, genre, label, language.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output of the movie grid.
	 */
	public function render( array $atts ): string {

		$atts = shortcode_atts(
			array(
				'person'   => '',
				'genre'    => '',
				'label'    => '',
				'language' => '',
				'per_page' => 20,
				'page'     => 1,
			),
			$atts,
			'movie'
		);

		foreach ( $atts as $key => $value ) {
			$atts[ $key ] = sanitize_text_field( $value );
		}

		$atts['per_page'] = max( 1, min( 50, absint( $atts['per_page'] ) ) );
		$atts['page']     = max( 1, absint( $atts['page'] ) );

		$tax_query = array( 'relation' => 'AND' );

		$taxonomy_map = array(
			'rt-movie-genre'    => 'genre',
			'rt-movie-label'    => 'label',
			'rt-movie-language' => 'language',
			'_rt-movie-person'  => 'person',
		);

		foreach ( $taxonomy_map as $taxonomy => $attr ) {
			if ( empty( $atts[ $attr ] ) ) {
				continue;
			}
			if ( 'person' === $attr ) {
				$shadow_slug = $this->resolve_person_shadow_slug( $atts[ $attr ] );
				if ( $shadow_slug ) {
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'slug',
						'terms'    => array( $shadow_slug ),
					);
				}
				continue;
			}

			$field = ctype_digit( $atts[ $attr ] ) ? 'term_id' : 'slug';

			$terms = ( 'term_id' === $field )
				? array( absint( $atts[ $attr ] ) )
				: array( sanitize_title( $atts[ $attr ] ) );

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => $field,
				'terms'    => $terms,
			);
		}

		$args = array(
			'post_type'              => 'rt-movie',
			'posts_per_page'         => $atts['per_page'],
			'paged'                  => $atts['page'],
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- Needed to ensure accurate taxonomy filtering without third-party interference.
			'suppress_filters'       => true,
		);

		if ( 1 < count( $tax_query ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filtering required for shortcode functionality.
			$args['tax_query'] = $tax_query;
		}

		$query = new WP_Query( $args );

		$movies      = array();
		$person_ids  = array();

		foreach ( $query->posts as $post ) {
			$post_id = $post->ID;

			$directors = json_decode( (string) get_post_meta( $post_id, 'rt-movie-meta-crew-director', true ), true );
			$actors    = json_decode( (string) get_post_meta( $post_id, 'rt-movie-meta-crew-actor', true ), true );

			$directors = is_array( $directors ) ? array_map( 'absint', $directors ) : array();
			$actors    = is_array( $actors ) ? array_map( 'absint', $actors ) : array();

			$person_ids = array_merge( $person_ids, $directors, $actors );

			$movies[] = array(
				'id'           => $post_id,
				'title'        => get_the_title( $post_id ),
				'permalink'    => get_permalink( $post_id ),
				'release_date' => get_post_meta( $post_id, 'rt-movie-meta-basic-release-date', true ),
				'directors'    => $directors,
				'actors'       => $actors,
			);
		}

		$person_titles = array();
		$person_ids    = array_values( array_unique( array_filter( $person_ids ) ) );

		if ( ! empty( $person_ids ) ) {
			$people = new WP_Query(
				array(
					'post_type'              => 'rt-person',
					'post__in'               => $person_ids,
					'posts_per_page'         => count( $person_ids ),
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'suppress_filters'       => true,
				)
			);

			foreach ( $people->posts as $person ) {
				$person_titles[ $person->ID ] = $person->post_title;
			}
		}

		ob_start();

		if ( ! empty( $movies ) ) :
			?>
			<div class="rt-movie-grid">
				<?php foreach ( $movies as $movie ) : ?>
					<div class="rt-movie-card">

						<div class="rt-movie-poster">
							<a href="<?php echo esc_url( $movie['permalink'] ); ?>">
								<?php
								echo get_the_post_thumbnail(
									$movie['id'],
									'medium',
									array(
										'alt' => esc_attr( $movie['title'] ),
									)
								);
								?>
							</a>
						</div>

						<div class="rt-movie-details">
							<h3>
								<a href="<?php echo esc_url( $movie['permalink'] ); ?>">
									<?php echo esc_html( $movie['title'] ); ?>
								</a>
							</h3>

							<div class="rt-movie-meta">

								<?php if ( ! empty( $movie['release_date'] ) ) : ?>
									<p>
										<strong><?php esc_html_e( 'Release', 'rt-movie-library' ); ?>:</strong>
										<span><?php echo esc_html( $movie['release_date'] ); ?></span>
									</p>
								<?php endif; ?>

								<?php if ( ! empty( $movie['directors'] ) ) : ?>
									<p>
										<strong><?php esc_html_e( 'Director', 'rt-movie-library' ); ?>:</strong>
										<span class="rt-movie-person-badge">
											<?php echo esc_html( $person_titles[ absint( $movie['directors'][0] ) ] ?? '' ); ?>
										</span>
									</p>
								<?php endif; ?>

								<?php if ( ! empty( $movie['actors'] ) ) : ?>
									<p>
										<strong><?php esc_html_e( 'Stars', 'rt-movie-library' ); ?>:</strong>
										<span class="rt-movie-stars">
											<?php
											foreach ( array_slice( $movie['actors'], 0, 2 ) as $actor_id ) {
												$label = $person_titles[ absint( $actor_id ) ] ?? '';
												if ( '' === $label ) {
													continue;
												}
												printf(
													'<span class="rt-movie-person-badge">%s</span>',
													esc_html( $label )
												);
											}
											?>
										</span>
									</p>
								<?php endif; ?>

							</div>
						</div>

					</div>
				<?php endforeach; ?>
			</div>
			<?php
		else :
			?>
			<p><?php esc_html_e( 'No movies found.', 'rt-movie-library' ); ?></p>
			<?php
		endif;

		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Resolve shadow taxonomy slug for a person filter, with simple in-request cache.
	 *
	 * @param string $person_attr Person attribute value.
	 * @return string|null
	 */
	private function resolve_person_shadow_slug( string $person_attr ): ?string {
		$key = sanitize_title( $person_attr );
		if ( '' === $key ) {
			return null;
		}

		static $cache = array();

		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		$person_query = new WP_Query(
			array(
				'post_type'      => 'rt-person',
				'name'           => $key,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $person_query->posts ) ) {
			$cache[ $key ] = null;
			return null;
		}

		$person_id   = $person_query->posts[0];
		$person_post = get_post( $person_id );

		if ( ! $person_post ) {
			$cache[ $key ] = null;
			return null;
		}

		$shadow_slug   = sanitize_title( $person_post->post_name . '-' . $person_id );
		$cache[ $key ] = $shadow_slug;

		return $shadow_slug;
	}
}
