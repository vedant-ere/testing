<?php
/**
 * Photo Gallery Meta Box.
 *
 * Meta Box ID / Key: rt-media-meta-img
 * Post Types: Movie, Person
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Meta_Boxes;

use RT_Movie_Library\Traits\Singleton;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class Media_Image_Meta_Box
 *
 * Handles the registration, rendering, and saving of the photo gallery meta box for Movie and Person post types.
 */
class Media_Image_Meta_Box {

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
	 * Register the photo gallery meta box for Movie and Person post types.
	 */
	public function register(): void {
		foreach ( array( 'rt-movie', 'rt-person' ) as $type ) {
			add_meta_box(
				'rt-media-meta-img',
				__( 'Photo Gallery', 'rt-movie-library' ),
				array( $this, 'render' ),
				$type
			);
		}
	}

	/**
	 * Render the meta box fields for adding image URLs or selecting images from the media library.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'rt_media_img', 'rt_media_img_nonce' );
		$value = get_post_meta( $post->ID, 'rt-media-meta-img', true );
		?>
		<div class="rt-media-box" data-multiple="1" data-type="image">
			<input type="hidden" class="rt-media-input" name="rt_media_img" value="<?php echo esc_attr( $value ); ?>">
			<button type="button" class="button rt-media-add"><?php esc_html_e( 'Add Images', 'rt-movie-library' ); ?></button>
			<div class="rt-media-list"></div>
		</div>
		<?php
	}

	/**
	 * Save the added image URLs or selected images by validating nonce and updating post meta.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( int $post_id ): void {
		if (
			! isset( $_POST['rt_media_img_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rt_media_img_nonce'] ) ), 'rt_media_img' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, array( 'rt-movie', 'rt-person' ), true ) ) {
			return;
		}

		if ( isset( $_POST['rt_media_img'] ) ) {
			$media_ids = $this->sanitize_media_ids(
				(string) wp_unslash( $_POST['rt_media_img'] ),
				'image'
			);

			if ( ! empty( $media_ids ) ) {
				update_post_meta(
					$post_id,
					'rt-media-meta-img',
					wp_json_encode( $media_ids )
				);
			} else {
				delete_post_meta( $post_id, 'rt-media-meta-img' );
			}
		}
	}

	/**
	 * Sanitize attachment IDs and keep only valid attachments of the expected media type.
	 *
	 * @param string $raw_ids    Raw submitted IDs.
	 * @param string $media_type Expected media type prefix (e.g. image, video).
	 * @return array<int>
	 */
	private function sanitize_media_ids( string $raw_ids, string $media_type ): array {
		$decoded = json_decode( $raw_ids, true );
		$ids     = array();

		if ( is_array( $decoded ) ) {
			$ids = $decoded;
		} elseif ( is_numeric( $raw_ids ) ) {
			$ids = array( $raw_ids );
		}

		$sanitized = array();

		foreach ( $ids as $id ) {
			$attachment_id = absint( $id );
			if ( 0 === $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
				continue;
			}

			$mime = get_post_mime_type( $attachment_id );
			if ( ! is_string( $mime ) || strpos( $mime, $media_type . '/' ) !== 0 ) {
				continue;
			}

			$sanitized[] = $attachment_id;
		}

		return array_values( array_unique( $sanitized ) );
	}

	/**
	 * Enqueue media scripts and styles for the meta box.
	 */
	public function assets(): void {
		wp_enqueue_media();
		wp_enqueue_script( 'rt-media-box', RT_MOVIE_LIBRARY_URL . 'assets/js/admin/media-meta-box.js', array( 'media-editor' ), RT_MOVIE_LIBRARY_VERSION, true );
		wp_localize_script(
			'rt-media-box',
			'rtMediaBoxL10n',
			array(
				'selectMedia'  => __( 'Select media', 'rt-movie-library' ),
				'useMedia'     => __( 'Use selected media', 'rt-movie-library' ),
				'removeLabel'  => __( 'Remove %s', 'rt-movie-library' ),
			)
		);
		wp_enqueue_style( 'rt-media-box', RT_MOVIE_LIBRARY_URL . 'assets/css/admin/media-meta-box.css', array(), RT_MOVIE_LIBRARY_VERSION );
	}
}
