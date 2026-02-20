<?php
/**
 * Person Basic Meta Box.
 *
 * Registers and handles basic personal details for Person post type.
 *
 * Meta Box ID: rt-person-meta-basic
 * Meta Keys:
 * - rt-person-meta-full-name
 * - rt-person-meta-basic-birth-date
 * - rt-person-meta-basic-birth-place
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Meta_Boxes;

use RT_Movie_Library\Traits\Singleton;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class Person_Basic_Meta_Box
 *
 * Handles the registration, rendering, and saving of basic personal details for Person post type.
 */
class Person_Basic_Meta_Box {

	use Singleton;

	/**
	 * Bootstraps hooks.
	 */
	protected function __construct() {
		add_action( 'add_meta_boxes_rt-person', array( $this, 'register' ) );
		add_action( 'save_post_rt-person', array( $this, 'save' ) );
	}

	/**
	 * Registers the meta box.
	 */
	public function register(): void {
		add_meta_box(
			'rt-person-meta-basic',
			__( 'Basic Details', 'rt-movie-library' ),
			array( $this, 'render' ),
			'rt-person',
			'normal',
			'high'
		);
	}

	/**
	 * Renders meta box fields.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'rt_person_basic_meta_action', 'rt_person_basic_meta_nonce' );

		$full_name   = get_post_meta( $post->ID, 'rt-person-meta-full-name', true );
		$birth_date  = get_post_meta( $post->ID, 'rt-person-meta-basic-birth-date', true );
		$birth_place = get_post_meta( $post->ID, 'rt-person-meta-basic-birth-place', true );
		?>

		<p>
			<label><strong><?php esc_html_e( 'Full Name', 'rt-movie-library' ); ?></strong></label>
			<input type="text" class="widefat" name="rt_person_full_name" value="<?php echo esc_attr( $full_name ); ?>">
		</p>

		<p>
			<label><strong><?php esc_html_e( 'Birth Date', 'rt-movie-library' ); ?></strong></label>
			<input type="date" name="rt_person_birth_date" value="<?php echo esc_attr( $birth_date ); ?>">
		</p>

		<p>
			<label><strong><?php esc_html_e( 'Birth Place', 'rt-movie-library' ); ?></strong></label>
			<input type="text" class="widefat" name="rt_person_birth_place" value="<?php echo esc_attr( $birth_place ); ?>">
		</p>

		<?php
	}

	/**
	 * Saves and sanitizes basic person metadata.
	 *
	 * @param int $post_id Person post ID.
	 */
	public function save( int $post_id ): void {

		if (
			! isset( $_POST['rt_person_basic_meta_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['rt_person_basic_meta_nonce'] ) ),
				'rt_person_basic_meta_action'
			)
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( 'rt-person' !== get_post_type( $post_id ) ) {
			return;
		}

		$map = array(
			'rt_person_full_name'   => 'rt-person-meta-full-name',
			'rt_person_birth_date'  => 'rt-person-meta-basic-birth-date',
			'rt_person_birth_place' => 'rt-person-meta-basic-birth-place',
		);

		foreach ( $map as $field => $meta_key ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = trim( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );

				if ( 'rt_person_birth_date' === $field ) {
					if ( '' === $value ) {
						delete_post_meta( $post_id, $meta_key );
					} elseif ( $this->is_valid_date( $value ) ) {
						update_post_meta( $post_id, $meta_key, $value );
					} else {
						delete_post_meta( $post_id, $meta_key );
					}

					continue;
				}

				$value ? update_post_meta( $post_id, $meta_key, $value ) : delete_post_meta( $post_id, $meta_key );
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
