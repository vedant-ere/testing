<?php
/**
 * Person Shortcode.
 *
 * Shortcode: [person]
 * Displays persons in a simple grid layout.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Shortcodes;

use RT_Movie_Library\Traits\Singleton;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class Person_Shortcode.
 *
 * Handles the [person] shortcode to display a grid of persons with their careers.
 */
class Person_Shortcode {

	use Singleton;

	/**
	 * Registers shortcode.
	 */
	protected function __construct() {
		add_shortcode( 'person', array( $this, 'render' ) );
	}

	/**
	 * Renders the [person] shortcode output. Uses WP_Query to fetch persons based on career taxonomy filter and displays it.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( array $atts ): string {

		$atts = shortcode_atts(
			array(
				'career' => '',
			),
			$atts,
			'person'
		);

		$atts['career'] = sanitize_text_field( $atts['career'] );

		$args = array(
			'post_type'              => 'rt-person',
			'posts_per_page'         => 50,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
		);

		if ( ! empty( $atts['career'] ) ) {

			$field = ctype_digit( $atts['career'] ) ? 'term_id' : 'slug';

			$terms = ( 'term_id' === $field )
				? array( absint( $atts['career'] ) )
				: array( sanitize_title( $atts['career'] ) );

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filtering required for shortcode functionality.
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'rt-person-career',
					'field'    => $field,
					'terms'    => $terms,
				),
			);
		}

		$query = new WP_Query( $args );

		ob_start();

		if ( $query->have_posts() ) :
			?>
			<div class="rt-person-grid">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();

					$post_id = get_the_ID(); 
					?>
					<div class="rt-person-card">
						<?php if ( has_post_thumbnail( $post_id ) ) : ?>
							<div class="rt-person-avatar">
								<?php
								the_post_thumbnail(
									'thumbnail',
									array(
										'alt' => esc_attr( get_the_title( $post_id ) ), 
									)
								);
								?>
							</div>
						<?php else : ?>
							<div class="rt-person-avatar no-image">
								<?php esc_html_e( 'No Image', 'rt-movie-library' ); ?>
							</div>
						<?php endif; ?>

						<h4 class="rt-person-name">
							<?php echo esc_html( get_the_title( $post_id ) ); ?> 
						</h4>

						<p class="rt-person-career">
							<?php
							$careers = wp_get_post_terms(
								$post_id,
								'rt-person-career',
								array( 'fields' => 'names' )
							);

							if ( is_array( $careers ) ) {
								echo esc_html( implode( ', ', $careers ) ); 
							}
							?>
						</p>
					</div>
					<?php
				endwhile;
				?>
			</div>
			<?php
			wp_reset_postdata();
		else :
			?>
			<p><?php esc_html_e( 'No persons found.', 'rt-movie-library' ); ?></p>
			<?php
		endif;

		return ob_get_clean();
	}
}
