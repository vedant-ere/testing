<?php
/**
 * Server-side renderer for the `rt-movie-library/persons` block.
 *
 * This file is responsible for querying and rendering person cards with the
 * same visual system used by the Screen Time person archive. Runtime rendering
 * keeps career filters and profile data live on each request while preserving
 * a consistent archive-like frontend experience across block placements.
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
 * Class Persons_Block
 *
 * Handles server-side rendering for the Persons block.
 */
class Persons_Block {

	use Singleton;

	/**
	 * Constructor.
	 */
	protected function __construct() {}

	/**
	 * Render persons list block.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render( array $attributes ): string {
		$count        = $this->sanitize_count( $attributes['count'] ?? 6 );
		$career_id    = absint( $attributes['careerId'] ?? 0 );
		$person_ids   = $this->sanitize_post_ids( $attributes['_post_ids'] ?? array() );
		$show_heading = ! isset( $attributes['_show_heading'] ) || (bool) $attributes['_show_heading'];

		$query_args = array(
			'post_type'              => 'rt-person',
			'post_status'            => array( 'publish' ),
			'posts_per_page'         => $count,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		);

		if ( ! empty( $person_ids ) ) {
			$query_args['post__in'] = $person_ids;
			$query_args['orderby']  = 'post__in';
		}

		if ( $career_id > 0 ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filtering is core block behavior.
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'rt-person-career',
					'field'    => 'term_id',
					'terms'    => array( $career_id ),
				),
			);
		}

		$persons_query = new WP_Query( $query_args );

		if ( ! $persons_query->have_posts() ) {
			return '<p class="rt-person-empty">' .
				esc_html__( 'No persons found.', 'rt-movie-library' ) .
				'</p>';
		}

		// ── High Performance: eliminate N+1 by pre-fetching meta for all results. ──
		$person_ids = wp_list_pluck( $persons_query->posts, 'ID' );
		Meta_Repository::prefetch(
			$person_ids,
			array(
				'rt-person-meta-basic-birth-date',
			)
		);

		return $this->render_markup( $persons_query->posts, $show_heading );
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
	 * Render frontend persons grid.
	 *
	 * @param array<WP_Post> $persons Person posts.
	 * @param bool           $show_heading Whether archive heading should render.
	 * @return string
	 */
	private function render_markup( array $persons, bool $show_heading ): string {
		ob_start();
		?>
		<div class="page-archive-person rt-block-archive-bridge">
			<section class="movie-section">
				<div class="container">
					<?php if ( $show_heading ) : ?>
						<h2 class="section-title section-title--person-archive">
							<a href="<?php echo esc_url( get_post_type_archive_link( 'rt-person' ) ); ?>">
								<?php esc_html_e( 'Celebrities', 'rt-movie-library' ); ?>
							</a>
						</h2>
					<?php endif; ?>
					<div class="person-list" aria-label="<?php esc_attr_e( 'Persons list', 'rt-movie-library' ); ?>">
						<?php foreach ( $persons as $person_post ) : ?>
							<?php
							$person_id      = (int) $person_post->ID;
							$careers        = wp_get_post_terms( $person_id, 'rt-person-career', array( 'fields' => 'names' ) );
							$primary_career = ( is_array( $careers ) && ! empty( $careers ) ) ? (string) $careers[0] : '';
							$birthdate_raw  = (string) Meta_Repository::get( $person_id, 'rt-person-meta-basic-birth-date', true );
							$birthdate_text = '';
							$image_url      = get_the_post_thumbnail_url( $person_id, 'medium' );
							$bio_text       = get_the_excerpt( $person_id );

							if ( '' !== $birthdate_raw ) {
								$birthdate_timestamp = strtotime( $birthdate_raw );

								if ( false !== $birthdate_timestamp ) {
									$birthdate_text = wp_date( get_option( 'date_format' ), $birthdate_timestamp );
								} else {
									$birthdate_text = $birthdate_raw;
								}
							}

							if ( '' === $bio_text ) {
								$bio_text = wp_trim_words(
									wp_strip_all_tags( (string) get_post_field( 'post_content', $person_id ) ),
									36,
									'...'
								);
							}
							?>
							<article class="person-card">
								<a
									class="person-card__overlay-link"
									href="<?php echo esc_url( get_permalink( $person_id ) ); ?>"
									aria-label="<?php echo esc_attr( get_the_title( $person_id ) ); ?>"
								></a>
								<?php if ( $image_url ) : ?>
									<img
										class="person-card__image"
										src="<?php echo esc_url( $image_url ); ?>"
										alt="<?php echo esc_attr( get_the_title( $person_id ) ); ?>"
										loading="lazy"
									>
								<?php endif; ?>
								<div class="person-card__content">
									<h3 class="person-card__name">
										<?php echo esc_html( get_the_title( $person_id ) ); ?>
										<?php if ( '' !== $primary_career ) : ?>
											<span class="person-card__role">(<?php echo esc_html( $primary_career ); ?>)</span>
										<?php endif; ?>
									</h3>
									<p class="person-card__dob">
										<?php
										echo esc_html(
											'' !== $birthdate_text
												? sprintf(
													/* translators: %s: birth date. */
													__( 'Born - %s', 'rt-movie-library' ),
													$birthdate_text
												)
												: ''
										);
										?>
									</p>
									<p class="person-card__excerpt"><?php echo esc_html( $bio_text ); ?></p>
									<a class="person-card__link" href="<?php echo esc_url( get_permalink( $person_id ) ); ?>">
										<?php esc_html_e( 'Learn more', 'rt-movie-library' ); ?>
										<span aria-hidden="true">→</span>
									</a>
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
