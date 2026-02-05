<?php
/**
 * Movie Carousel Poster Meta Box.
 *
 * Meta Box ID / Key: rt-movie-meta-carousel-poster
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Meta_Boxes;

use RT_Movie_Library\Traits\Singleton;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Poster_Meta_Box
 *
 * Handles the registration, rendering, and saving of the carousel poster meta box for Movie post type.
 */
class Movie_Poster_Meta_Box {

	use Singleton;

	/**
	 * Constructor.
	 *
	 * Registers hooks for meta box registration, saving, and asset enqueueing.
	 */
	protected function __construct() {
		add_action( 'add_meta_boxes_rt-movie', array( $this, 'register' ) );
		add_action( 'save_post_rt-movie', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * Register the carousel poster meta box for Movie post type.
	 */
	public function register(): void {
		add_meta_box(
			'rt-movie-meta-carousel-poster',
			__( 'Carousel Poster', 'rt-movie-library' ),
			array( $this, 'render' ),
			'rt-movie'
		);
	}

	/**
	 * Render the meta box fields for selecting a carousel poster image.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'rt_movie_poster', 'rt_movie_poster_nonce' );
		$value = get_post_meta( $post->ID, 'rt-movie-meta-carousel-poster', true );
		?>
		<div class="rt-media-box" data-multiple="0" data-type="image">
			<input type="hidden" class="rt-media-input" name="rt_movie_poster" value="<?php echo esc_attr( $value ); ?>">
			<button type="button" class="button rt-media-add"><?php esc_html_e( 'Select Poster', 'rt-movie-library' ); ?></button>
			<div class="rt-media-list"></div>
		</div>
		<?php
	}

	/**
	 * Save the selected carousel poster image by validating nonce and updating post meta.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( int $post_id ): void {
		if (
			! isset( $_POST['rt_movie_poster_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rt_movie_poster_nonce'] ) ), 'rt_movie_poster' )
		) {
			return;
		}

		if ( isset( $_POST['rt_movie_poster'] ) ) {
			update_post_meta( $post_id, 'rt-movie-meta-carousel-poster', absint( $_POST['rt_movie_poster'] ) );
		}
	}

	/**
	 * Enqueue media scripts and styles for the meta box.
	 */
	public function assets(): void {
		wp_enqueue_media();
		wp_enqueue_script( 'rt-media-box' );
		wp_enqueue_style( 'rt-media-box' );
	}
}