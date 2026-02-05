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
			),
			$atts,
			'movie'
		);

		foreach ( $atts as $key => $value ) {
			$atts[ $key ] = sanitize_text_field( $value );
		}

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
			'post_type'        => 'rt-movie',
			'posts_per_page'   => -1,
			'no_found_rows'    => true,
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- Needed to ensure accurate taxonomy filtering without third-party interference.
			'suppress_filters' => true,
		);

		if ( 1 < count( $tax_query ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filtering required for shortcode functionality.
			$args['tax_query'] = $tax_query;
		}

		$query = new WP_Query( $args );

		ob_start();

		if ( $query->have_posts() ) :
			?>
			<div class="rt-movie-grid">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();

					$post_id = get_the_ID();

					$release_date = get_post_meta(
						$post_id,
						'rt-movie-meta-basic-release-date',
						true
					);

					$directors_raw = get_post_meta(
						$post_id,
						'rt-movie-meta-crew-director',
						true
					);

					$directors = json_decode( $directors_raw, true );
					if ( ! is_array( $directors ) ) {
						$directors = array();
					}

					$actors_raw = get_post_meta(
						$post_id,
						'rt-movie-meta-crew-actor',
						true
					);

					$actors = json_decode( $actors_raw, true );
					if ( ! is_array( $actors ) ) {
						$actors = array();
					}
					?>
					<div class="rt-movie-card">

						<div class="rt-movie-poster">
							<?php
							if ( has_post_thumbnail( $post_id ) ) {
								the_post_thumbnail(
									'medium',
									array(
										'alt' => esc_attr( get_the_title( $post_id ) ),
									)
								);
							}
							?>
						</div>

						<div class="rt-movie-details">
							<h3><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>

							<div class="rt-movie-meta">

								<?php if ( ! empty( $release_date ) ) : ?>
									<p>
										<strong>Release:</strong>
										<span><?php echo esc_html( $release_date ); ?></span>
									</p>
								<?php endif; ?>

								<?php if ( ! empty( $directors ) ) : ?>
									<p>
										<strong>Director:</strong>
										<span class="rt-movie-person-badge">
											<?php echo esc_html( get_the_title( absint( $directors[0] ) ) ); ?>
										</span>
									</p>
								<?php endif; ?>

								<?php if ( ! empty( $actors ) ) : ?>
									<p>
										<strong>Stars:</strong>
										<span class="rt-movie-stars">
											<?php
											foreach ( array_slice( $actors, 0, 2 ) as $actor_id ) {
												echo '<span class="rt-movie-person-badge">'
													. esc_html( get_the_title( absint( $actor_id ) ) )
													. '</span>';
											}
											?>
										</span>
									</p>
								<?php endif; ?>

							</div>
						</div>

					</div>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>
			<?php
		else :
			?>
			<p>No movies found.</p>
			<?php
		endif;

		return ob_get_clean();
	}
}
