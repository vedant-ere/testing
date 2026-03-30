<?php
/**
 * Block registration and frontend rendering.
 *
 * @package RT_Post_Embedder
 */

namespace RT_Post_Embedder;

use RT_Post_Embedder\Traits\Singleton;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class Block_Registrar
 */
class Block_Registrar {

	use Singleton;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	private const BLOCK_NAME = 'rt-post-embedder/post-embedder';

	/**
	 * Register hooks.
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register block type from build metadata.
	 *
	 * @return void
	 */
	public function register(): void {
		$build_path = RT_POST_EMBEDDER_PATH . 'build/post-embedder';

		if ( ! file_exists( $build_path . '/block.json' ) ) {
			return;
		}

		register_block_type(
			$build_path,
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Render block output.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render( array $attributes ): string {
		$embedded_posts = $attributes['embeddedPosts'] ?? array();

		if ( ! is_array( $embedded_posts ) || empty( $embedded_posts ) ) {
			return '';
		}

		$source_posts = $this->get_source_post_map( $embedded_posts );

		ob_start();
		?>
		<div class="rt-pe-frontend">
			<?php foreach ( $embedded_posts as $embedded_post ) : ?>
				<?php
				if ( ! is_array( $embedded_post ) ) {
					continue;
				}

				$post_id         = absint( $embedded_post['postId'] ?? 0 );
				$source_post     = $source_posts[ $post_id ] ?? null;
				$source_missing  = $post_id > 0 && ! ( $source_post instanceof WP_Post );
				$image_left      = ! isset( $embedded_post['imageLeft'] ) || (bool) $embedded_post['imageLeft'];
				$show_excerpt    = ! isset( $embedded_post['showExcerpt'] ) || (bool) $embedded_post['showExcerpt'];
				$show_date       = ! isset( $embedded_post['showDate'] ) || (bool) $embedded_post['showDate'];
				$title           = sanitize_text_field( (string) ( $embedded_post['title'] ?? '' ) );
				$excerpt         = (string) ( $embedded_post['excerpt'] ?? '' );
				$date_raw        = (string) ( $embedded_post['date'] ?? '' );
				$thumbnail_url   = esc_url_raw( (string) ( $embedded_post['thumbnailUrl'] ?? '' ) );
				$thumbnail_id    = absint( $embedded_post['thumbnailId'] ?? 0 );
				$fallback_title  = $source_post instanceof WP_Post ? get_the_title( $source_post ) : '';
				$permalink       = $source_post instanceof WP_Post ? get_permalink( $source_post ) : '';
				$display_title   = '' !== $title ? $title : $fallback_title;
				$display_excerpt = '' !== $excerpt ? $excerpt : ( $source_post instanceof WP_Post ? get_the_excerpt( $source_post ) : '' );

				if ( '' === $thumbnail_url && $thumbnail_id > 0 ) {
					$resolved_thumbnail = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
					$thumbnail_url      = is_string( $resolved_thumbnail ) ? $resolved_thumbnail : '';
				}

				if ( '' === $thumbnail_url && $source_post instanceof WP_Post ) {
					$source_thumbnail = get_post_thumbnail_id( $source_post->ID );
					if ( $source_thumbnail > 0 ) {
						$resolved_thumbnail = wp_get_attachment_image_url( $source_thumbnail, 'medium' );
						$thumbnail_url      = is_string( $resolved_thumbnail ) ? $resolved_thumbnail : '';
					}
				}

				$display_date = $this->format_display_date( $date_raw, $source_post );
				$date_attr    = $this->format_datetime_attr( $date_raw, $source_post );
				$grid_class   = $image_left ? 'rt-pe-grid rt-pe-grid--image-left' : 'rt-pe-grid rt-pe-grid--image-right';
				?>
				<article class="rt-pe-item">
					<div class="<?php echo esc_attr( $grid_class ); ?>">
						<?php if ( $image_left ) : ?>
							<?php $this->render_image_column( $thumbnail_url, $display_title, $permalink ); ?>
						<?php endif; ?>

						<div class="rt-pe-grid__content">
							<h3 class="rt-pe-item__title">
								<?php if ( '' !== $permalink ) : ?>
									<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $display_title ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $display_title ); ?>
								<?php endif; ?>
							</h3>

							<?php if ( $show_date && '' !== $display_date ) : ?>
								<time class="rt-pe-item__date" datetime="<?php echo esc_attr( $date_attr ); ?>">
									<?php echo esc_html( $display_date ); ?>
								</time>
							<?php endif; ?>

							<?php if ( $show_excerpt && '' !== $display_excerpt ) : ?>
								<div class="rt-pe-item__excerpt">
									<?php echo wp_kses_post( wpautop( $display_excerpt ) ); ?>
								</div>
							<?php endif; ?>

							<?php if ( $source_missing ) : ?>
								<p class="rt-pe-item__missing-source">
									<?php esc_html_e( 'Source post is unavailable, showing the last saved snapshot.', 'rt-post-embedder' ); ?>
								</p>
							<?php endif; ?>
						</div>

						<?php if ( ! $image_left ) : ?>
							<?php $this->render_image_column( $thumbnail_url, $display_title, $permalink ); ?>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render image column.
	 *
	 * @param string $thumbnail_url Thumbnail URL.
	 * @param string $title         Item title.
	 * @param string $permalink     Source permalink.
	 * @return void
	 */
	private function render_image_column( string $thumbnail_url, string $title, string $permalink ): void {
		if ( '' === $thumbnail_url ) {
			?>
			<div class="rt-pe-grid__image rt-pe-grid__image--placeholder" aria-hidden="true"></div>
			<?php
			return;
		}
		?>
		<div class="rt-pe-grid__image">
			<?php if ( '' !== $permalink ) : ?>
				<a href="<?php echo esc_url( $permalink ); ?>">
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				</a>
			<?php else : ?>
				<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Resolve source posts in one query.
	 *
	 * @param array<int, mixed> $embedded_posts Embedded post data.
	 * @return array<int, WP_Post>
	 */
	private function get_source_post_map( array $embedded_posts ): array {
		$post_ids = $this->extract_source_post_ids( $embedded_posts );

		if ( empty( $post_ids ) ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'any',
				'post__in'               => $post_ids,
				'post_status'            => get_post_stati( array(), 'names' ),
				'posts_per_page'         => count( $post_ids ),
				'no_found_rows'          => true,
				'orderby'                => 'post__in',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$map = array();
		foreach ( $query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$map[ (int) $post->ID ] = $post;
			}
		}

		return $map;
	}

	/**
	 * Extract unique source post IDs.
	 *
	 * @param array<int, mixed> $embedded_posts Embedded post data.
	 * @return int[]
	 */
	private function extract_source_post_ids( array $embedded_posts ): array {
		$post_ids = array();

		foreach ( $embedded_posts as $embedded_post ) {
			if ( ! is_array( $embedded_post ) ) {
				continue;
			}

			$post_id = absint( $embedded_post['postId'] ?? 0 );
			if ( $post_id > 0 ) {
				$post_ids[] = $post_id;
			}
		}

		return array_values( array_unique( $post_ids ) );
	}

	/**
	 * Format display date.
	 *
	 * @param string       $date_raw   Saved date value.
	 * @param WP_Post|null $source_post Source post fallback.
	 * @return string
	 */
	private function format_display_date( string $date_raw, ?WP_Post $source_post ): string {
		$timestamp = strtotime( $date_raw );

		if ( false === $timestamp && $source_post instanceof WP_Post ) {
			$timestamp = strtotime( $source_post->post_date );
		}

		if ( false === $timestamp ) {
			return '';
		}

		return wp_date( get_option( 'date_format' ), $timestamp );
	}

	/**
	 * Format datetime attribute value.
	 *
	 * @param string       $date_raw   Saved date value.
	 * @param WP_Post|null $source_post Source post fallback.
	 * @return string
	 */
	private function format_datetime_attr( string $date_raw, ?WP_Post $source_post ): string {
		$timestamp = strtotime( $date_raw );

		if ( false === $timestamp && $source_post instanceof WP_Post ) {
			$timestamp = strtotime( $source_post->post_date );
		}

		if ( false === $timestamp ) {
			return '';
		}

		return gmdate( 'c', $timestamp );
	}
}
