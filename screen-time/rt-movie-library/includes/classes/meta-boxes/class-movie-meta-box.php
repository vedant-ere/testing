<?php
/**
 * Movie Meta Box – Basic Details.
 *
 * Registers and saves metadata for the Movie (`rt-movie`) post type.
 *
 * Meta Box ID:
 * - rt-movie-meta-basic
 *
 * Meta Keys:
 * - rt-movie-meta-basic-rating
 * - rt-movie-meta-basic-runtime
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Meta_Boxes;

use RT_Movie_Library\Traits\Singleton;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Meta_Box
 *
 * Handles Movie meta box registration, rendering, and saving of basic details like rating and runtime.
 */
class Movie_Meta_Box {

	use Singleton;

	/**
	 * Constructor.
	 *
	 * Registers hooks for meta box registration and saving.
	 */
	protected function __construct() {
		add_action( 'add_meta_boxes_rt-movie', array( $this, 'register' ) );
		add_action( 'save_post_rt-movie', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue meta box specific assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		global $post_type;

		if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && 'rt-movie' === $post_type ) {
			wp_enqueue_style(
				'rt-movie-meta-basic',
				plugin_dir_url( RT_MOVIE_LIBRARY_PATH . 'rt-movie-library.php' ) . 'assets/css/admin/movie-basic-meta-box.css',
				array(),
				RT_MOVIE_LIBRARY_VERSION
			);
		}
	}

	/**
	 * Register the meta box.
	 *
	 * @return void
	 */
	public function register(): void {
		add_meta_box(
			'rt-movie-meta-basic',
			__( 'Basic Movie Details', 'rt-movie-library' ),
			array( $this, 'render' ),
			'rt-movie',
			'side',
			'high'
		);
	}

	/**
	 * Render meta box HTML.
	 *
	 * @param WP_Post $post Current post object.
	 *
	 * @return void
	 */
	public function render( WP_Post $post ): void {

		wp_nonce_field(
			'rt_movie_meta_box_action',
			'rt_movie_meta_box_nonce'
		);

		$rating  = get_post_meta( $post->ID, 'rt-movie-meta-basic-rating', true );
		$runtime = get_post_meta( $post->ID, 'rt-movie-meta-basic-runtime', true );
		?>

		<div class="rt-movie-basic-wrapper">

	<div class="rt-movie-basic-field">
		<label for="rt-movie-rating">
			<strong><?php esc_html_e( 'Rating', 'rt-movie-library' ); ?></strong>
		</label>

		<input
			type="number"
			id="rt-movie-rating"
			name="rt_movie_rating"
			class="rt-movie-basic-input"
			value="<?php echo esc_attr( $rating ); ?>"
			step="0.1"
			min="1"
			max="10"
		>

		<p class="description">
			<?php esc_html_e( 'Enter rating (e.g. 8.1, 7.4)', 'rt-movie-library' ); ?>
		</p>
	</div>

	<div class="rt-movie-basic-field">
		<label for="rt-movie-runtime">
			<strong><?php esc_html_e( 'Runtime (minutes)', 'rt-movie-library' ); ?></strong>
		</label>

		<input
			type="number"
			id="rt-movie-runtime"
			name="rt_movie_runtime"
			class="rt-movie-basic-input"
			value="<?php echo esc_attr( $runtime ); ?>"
			step="1"
			min="1"
		>

		<p class="description">
			<?php esc_html_e( 'e.g. 120 for 2 hours', 'rt-movie-library' ); ?>
		</p>
	</div>

	<div class="rt-movie-basic-field">
		<label for="rt-movie-release-date">
			<strong><?php esc_html_e( 'Release Date', 'rt-movie-library' ); ?></strong>
		</label>

		<input
			type="date"
			id="rt-movie-release-date"
			name="rt_movie_release_date"
			class="rt-movie-basic-input"
			value="<?php echo esc_attr( get_post_meta( $post->ID, 'rt-movie-meta-basic-release-date', true ) ); ?>"
		>
	</div>

	<div class="rt-movie-basic-field">
		<label for="rt-movie-content-rating">
			<strong><?php esc_html_e( 'Content Rating', 'rt-movie-library' ); ?></strong>
		</label>

		<select
			id="rt-movie-content-rating"
			name="rt_movie_content_rating"
			class="rt-movie-basic-select"
		>
			<option value=""><?php esc_html_e( 'Select rating', 'rt-movie-library' ); ?></option>
			<?php
			$ratings = array( 'U', 'U/A', 'PG', 'PG-13', 'R', 'NC-17' );
			$current = get_post_meta(
				$post->ID,
				'rt-movie-meta-basic-content-rating',
				true
			);

			foreach ( $ratings as $rating ) {
				printf(
					'<option value="%1$s" %2$s>%1$s</option>',
					esc_attr( $rating ),
					selected( $current, $rating, false )
				);
			}
			?>
		</select>
	</div>

</div>

		<?php
	}

	/**
	 * Save meta box data by validating, sanitizing, and updating post meta.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function save( int $post_id ): void {

		if (
			! isset( $_POST['rt_movie_meta_box_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['rt_movie_meta_box_nonce'] ) ),
				'rt_movie_meta_box_action'
			)
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'rt-movie' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['rt_movie_rating'] ) ) {
			$rating_raw = trim( sanitize_text_field( wp_unslash( $_POST['rt_movie_rating'] ) ) );

			if ( '' === $rating_raw || ! is_numeric( $rating_raw ) ) {
				delete_post_meta( $post_id, 'rt-movie-meta-basic-rating' );
			} else {
				$rating = (float) $rating_raw;

				if ( $rating < 1 || $rating > 10 ) {
					delete_post_meta( $post_id, 'rt-movie-meta-basic-rating' );
				} else {
					update_post_meta(
						$post_id,
						'rt-movie-meta-basic-rating',
						number_format( $rating, 1, '.', '' )
					);
				}
			}
		} else {
			delete_post_meta( $post_id, 'rt-movie-meta-basic-rating' );
		}

		if ( isset( $_POST['rt_movie_runtime'] ) ) {
			$runtime_raw = trim( sanitize_text_field( wp_unslash( $_POST['rt_movie_runtime'] ) ) );

			if ( '' === $runtime_raw || ! preg_match( '/^\d+$/', $runtime_raw ) ) {
				delete_post_meta( $post_id, 'rt-movie-meta-basic-runtime' );
			} else {
				$runtime = absint( $runtime_raw );

				if ( 0 < $runtime ) {
					update_post_meta(
						$post_id,
						'rt-movie-meta-basic-runtime',
						$runtime
					);
				} else {
					delete_post_meta( $post_id, 'rt-movie-meta-basic-runtime' );
				}
			}
		} else {
			delete_post_meta( $post_id, 'rt-movie-meta-basic-runtime' );
		}

		if ( isset( $_POST['rt_movie_release_date'] ) ) {
			$release_date = trim( sanitize_text_field(
				wp_unslash( $_POST['rt_movie_release_date'] )
			) );

			if ( '' === $release_date ) {
				delete_post_meta( $post_id, 'rt-movie-meta-basic-release-date' );
			} elseif ( $this->is_valid_date( $release_date ) ) {
				update_post_meta(
					$post_id,
					'rt-movie-meta-basic-release-date',
					$release_date
				);
			} else {
				delete_post_meta( $post_id, 'rt-movie-meta-basic-release-date' );
			}
		}

		if ( isset( $_POST['rt_movie_content_rating'] ) ) {

			$content_rating = sanitize_text_field(
				wp_unslash( $_POST['rt_movie_content_rating'] )
			);

			$allowed_ratings = array( 'U', 'U/A', 'PG', 'PG-13', 'R', 'NC-17' );

			if ( '' === $content_rating ) {
				delete_post_meta(
					$post_id,
					'rt-movie-meta-basic-content-rating'
				);
			} elseif ( in_array( $content_rating, $allowed_ratings, true ) ) {
				update_post_meta(
					$post_id,
					'rt-movie-meta-basic-content-rating',
					$content_rating
				);
			} else {
				delete_post_meta(
					$post_id,
					'rt-movie-meta-basic-content-rating'
				);
			}
		}
	}

	/**
	 * Validate YYYY-MM-DD date format strictly.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	private function is_valid_date( string $date ): bool {
		$parsed = \DateTimeImmutable::createFromFormat( 'Y-m-d', $date );

		return $parsed instanceof \DateTimeImmutable && $parsed->format( 'Y-m-d' ) === $date;
	}
}
