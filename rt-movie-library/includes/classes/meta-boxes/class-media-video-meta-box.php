<?php
/**
 * Video Gallery Meta Box.
 *
 * Meta Box ID / Key: rt-media-meta-video
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Meta_Boxes;

use RT_Movie_Library\Traits\Singleton;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class Media_Video_Meta_Box
 *
 * Handles the registration, rendering, and saving of the video gallery meta box for Movie and Person post types.
 */
class Media_Video_Meta_Box {

	use Singleton;

	/**
	 * Constructor.
	 *
	 * Registers hooks for meta box registration, saving, and asset enqueueing.
	 */
	protected function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * Register the video gallery meta box for Movie and Person post types.
	 */
	public function register(): void {
		foreach ( array( 'rt-movie', 'rt-person' ) as $type ) {
			add_meta_box(
				'rt-media-meta-video',
				__( 'Video Gallery', 'rt-movie-library' ),
				array( $this, 'render' ),
				$type
			);
		}
	}

	/**
	 * Render the meta box fields for adding video URLs or selecting videos from the media library.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'rt_media_video', 'rt_media_video_nonce' );
		$value = get_post_meta( $post->ID, 'rt-media-meta-video', true );
		?>
		<div class="rt-media-box" data-multiple="1" data-type="video">
			<input type="hidden" class="rt-media-input" name="rt_media_video" value="<?php echo esc_attr( $value ); ?>">
			<button type="button" class="button rt-media-add"><?php esc_html_e( 'Add Videos', 'rt-movie-library' ); ?></button>
			<div class="rt-media-list"></div>
		</div>
		<?php
	}

	/**
	 * Save the added video URLs or selected videos by validating nonce and updating post meta.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( int $post_id ): void {
		if (
			! isset( $_POST['rt_media_video_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['rt_media_video_nonce'] ) ),
				'rt_media_video'
			)
		) {
			return;
		}

		if ( isset( $_POST['rt_media_video'] ) ) {
			$video = sanitize_text_field(
				wp_unslash( $_POST['rt_media_video'] )
			);

			update_post_meta(
				$post_id,
				'rt-media-meta-video',
				$video
			);
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
