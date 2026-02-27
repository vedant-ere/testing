<?php
/**
 * Person REST controller.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Rest;

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
}
