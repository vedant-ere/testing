<?php
/**
 * Person REST controller.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Rest;

use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class Person_Controller
 */
class Person_Controller extends Base_Cpt_Controller {

	/**
	 * Setup person controller.
	 */
	public function __construct() {
		$this->namespace = 'rt-movie-library/v1';
		$this->rest_base = 'persons';
		$this->post_type = 'rt-person';

		$this->meta_field_map = array(
			'full_name'     => 'rt-person-meta-full-name',
			'birth_date'    => 'rt-person-meta-basic-birth-date',
			'birth_place'   => 'rt-person-meta-basic-birth-place',
			'twitter'       => 'rt-person-meta-social-twitter',
			'facebook'      => 'rt-person-meta-social-facebook',
			'instagram'     => 'rt-person-meta-social-instagram',
			'website'       => 'rt-person-meta-social-web',
			'image_gallery' => 'rt-media-meta-img',
		);

		$this->meta_input_aliases = array(
			'rt-person-full-name'              => 'full_name',
			'rt-person-meta-full-name'         => 'full_name',
			'rt-person-birth-date'             => 'birth_date',
			'rt-person-meta-basic-birth-date'  => 'birth_date',
			'rt-person-birth-place'            => 'birth_place',
			'rt-person-meta-basic-birth-place' => 'birth_place',
			'rt-person-meta-social-twitter'    => 'twitter',
			'rt-person-meta-social-facebook'   => 'facebook',
			'rt-person-meta-social-instagram'  => 'instagram',
			'rt-person-meta-social-web'        => 'website',
			'rt-media-meta-img'                => 'image_gallery',
			'image_urls'                       => 'image_gallery',
		);

		$this->allowed_taxonomies = array(
			'rt-person-career',
		);

		$this->taxonomy_field_map = array(
			'careers' => 'rt-person-career',
		);

		$this->meta_response_schema = array(
			'rt-person-meta-full-name'         => array( 'type' => array( 'string', 'null' ) ),
			'rt-person-meta-basic-birth-date'  => array( 'type' => array( 'string', 'null' ) ),
			'rt-person-meta-basic-birth-place' => array( 'type' => array( 'string', 'null' ) ),
			'rt-person-meta-social-twitter'    => array( 'type' => array( 'string', 'null' ) ),
			'rt-person-meta-social-facebook'   => array( 'type' => array( 'string', 'null' ) ),
			'rt-person-meta-social-instagram'  => array( 'type' => array( 'string', 'null' ) ),
			'rt-person-meta-social-web'        => array( 'type' => array( 'string', 'null' ) ),
			'rt-media-meta-img'                => array( 'type' => 'array' ),
		);

		$this->extra_write_args = array(
			'full_name'   => array(
				'description'       => __( 'Full name of the person.', 'rt-movie-library' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'birth_date'  => array(
				'description'       => __( 'Birth date in Y-m-d format.', 'rt-movie-library' ),
				'type'              => 'string',
				'format'            => 'date',
				'validate_callback' => array( Cpt_Helper::class, 'validate_date' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'birth_place' => array(
				'description'       => __( 'Birth place of the person.', 'rt-movie-library' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'twitter'     => array(
				'description'       => __( 'Twitter/X profile URL.', 'rt-movie-library' ),
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => array( Cpt_Helper::class, 'validate_person_social_url' ),
			),
			'facebook'    => array(
				'description'       => __( 'Facebook profile URL.', 'rt-movie-library' ),
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => array( Cpt_Helper::class, 'validate_person_social_url' ),
			),
			'instagram'   => array(
				'description'       => __( 'Instagram profile URL.', 'rt-movie-library' ),
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => array( Cpt_Helper::class, 'validate_person_social_url' ),
			),
			'website'     => array(
				'description'       => __( 'Website URL.', 'rt-movie-library' ),
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => array( Cpt_Helper::class, 'validate_person_social_url' ),
			),
		);
	}

	/**
	 * Validate person meta values for both top-level and `meta` payload inputs.
	 *
	 * @param string          $meta_key Meta key.
	 * @param mixed           $value Meta value.
	 * @param WP_REST_Request $request Request.
	 * @return true|\WP_Error
	 */
	protected function validate_meta_value( string $meta_key, $value, WP_REST_Request $request ) {
		unset( $request );

		if ( null === $value || '' === $value ) {
			return true;
		}

		if ( 'rt-person-meta-basic-birth-date' === $meta_key ) {
			return Cpt_Helper::validate_date( $value, new WP_REST_Request( 'POST' ), 'birth_date' );
		}

		$social_meta_to_param = array(
			'rt-person-meta-social-twitter'   => 'twitter',
			'rt-person-meta-social-facebook'  => 'facebook',
			'rt-person-meta-social-instagram' => 'instagram',
			'rt-person-meta-social-web'       => 'website',
		);

		if ( isset( $social_meta_to_param[ $meta_key ] ) ) {
			return Cpt_Helper::validate_person_social_url(
				$value,
				new WP_REST_Request( 'POST' ),
				$social_meta_to_param[ $meta_key ]
			);
		}

		return true;
	}

	/**
	 * Validate person request payload before create/update.
	 *
	 * Applies birth-date and social URL validation to top-level and `meta` payload
	 * through existing shared helper functions.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|\WP_Error
	 */
	protected function validate_request_payload( WP_REST_Request $request ) {
		$field_to_meta_map = array(
			'birth_date' => 'rt-person-meta-basic-birth-date',
			'twitter'    => 'rt-person-meta-social-twitter',
			'facebook'   => 'rt-person-meta-social-facebook',
			'instagram'  => 'rt-person-meta-social-instagram',
			'website'    => 'rt-person-meta-social-web',
		);

		foreach ( $field_to_meta_map as $field => $meta_key ) {
			if ( ! $request->has_param( $field ) ) {
				continue;
			}

			$validation = $this->validate_meta_value( $meta_key, $request->get_param( $field ), $request );

			if ( is_wp_error( $validation ) ) {
				return $validation;
			}
		}

		$meta_payload = $request->get_param( 'meta' );

		if ( is_array( $meta_payload ) ) {
			foreach ( $meta_payload as $input_key => $value ) {
				if ( ! is_string( $input_key ) ) {
					continue;
				}

				$meta_key = $this->resolve_meta_key_from_input_key( $input_key );

				if ( null === $meta_key ) {
					continue;
				}

				$validation = $this->validate_meta_value( $meta_key, $value, $request );

				if ( is_wp_error( $validation ) ) {
					return $validation;
				}
			}
		}

		return true;
	}
}
