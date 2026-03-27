<?php
/**
 * Person Social Meta Box.
 *
 * Registers and handles social profile links for Person post type.
 *
 * Meta Box ID: rt-person-meta-social
 * Meta Keys:
 * - rt-person-meta-social-twitter
 * - rt-person-meta-social-facebook
 * - rt-person-meta-social-instagram
 * - rt-person-meta-social-web
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Meta_Boxes;

use RT_Movie_Library\Traits\Singleton;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class Person_Social_Meta_Box
 *
 * Handles the registration, rendering, and saving of social profile links for Person post type.
 */
class Person_Social_Meta_Box {

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
			'rt-person-meta-social',
			__( 'Social Information', 'rt-movie-library' ),
			array( $this, 'render' ),
			'rt-person',
			'normal',
			'default'
		);
	}

	/**
	 * Renders social fields.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'rt_person_social_meta_action', 'rt_person_social_meta_nonce' );

		$fields = array(
			'Twitter'   => 'rt-person-meta-social-twitter',
			'Facebook'  => 'rt-person-meta-social-facebook',
			'Instagram' => 'rt-person-meta-social-instagram',
			'Website'   => 'rt-person-meta-social-web',
		);

		foreach ( $fields as $label => $key ) :
			$value = get_post_meta( $post->ID, $key, true );
			?>
			<p>
				<label><strong><?php echo esc_html( $label ); ?></strong></label>
				<input type="url" class="widefat" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<?php
		endforeach;
	}

	/**
	 * Saves social links metadata.
	 *
	 * @param int $post_id Person post ID.
	 */
	public function save( int $post_id ): void {

		if (
			! isset( $_POST['rt_person_social_meta_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['rt_person_social_meta_nonce'] ) ),
				'rt_person_social_meta_action'
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

		$meta_keys = array(
			'rt-person-meta-social-twitter',
			'rt-person-meta-social-facebook',
			'rt-person-meta-social-instagram',
			'rt-person-meta-social-web',
		);

		foreach ( $meta_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per-field in sanitize_social_url().
				$value = trim( wp_unslash( $_POST[ $key ] ) );
				$value = '' === $value ? '' : $this->sanitize_social_url( $value, $key );
				$value ? update_post_meta( $post_id, $key, $value ) : delete_post_meta( $post_id, $key );
			}
		}
	}

	/**
	 * Sanitize and validate social URL against expected domains by field.
	 *
	 * @param string $url      Raw URL.
	 * @param string $meta_key Meta key being saved.
	 * @return string
	 */
	private function sanitize_social_url( string $url, string $meta_key ): string {
		$sanitized = esc_url_raw( $url );

		if ( '' === $sanitized ) {
			return '';
		}

		$host = wp_parse_url( $sanitized, PHP_URL_HOST );
		$host = is_string( $host ) ? strtolower( $host ) : '';
		$host = preg_replace( '/^www\./', '', $host );

		if ( ! is_string( $host ) || '' === $host ) {
			return '';
		}

		$allowed_domains = array(
			'rt-person-meta-social-twitter'   => array( 'twitter.com', 'x.com' ),
			'rt-person-meta-social-facebook'  => array( 'facebook.com' ),
			'rt-person-meta-social-instagram' => array( 'instagram.com' ),
			'rt-person-meta-social-web'       => array(),
		);

		if ( ! isset( $allowed_domains[ $meta_key ] ) ) {
			return '';
		}

		if ( empty( $allowed_domains[ $meta_key ] ) ) {
			return $sanitized;
		}

		foreach ( $allowed_domains[ $meta_key ] as $domain ) {
			$subdomain_suffix = '.' . $domain;
			$suffix_length    = strlen( $subdomain_suffix );

			if (
				$host === $domain ||
				( strlen( $host ) > $suffix_length && substr( $host, -$suffix_length ) === $subdomain_suffix )
			) {
				return $sanitized;
			}
		}

		return '';
	}
}
