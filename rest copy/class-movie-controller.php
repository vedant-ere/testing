<?php
/**
 * Movie REST controller.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Rest;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Controller
 */
class Movie_Controller extends Base_Cpt_Controller {

	/**
	 * Setup movie controller.
	 */
	public function __construct() {
		$this->namespace = 'rt-movie-library/v1';
		$this->rest_base = 'movies';
		$this->post_type = 'rt-movie';

		$this->meta_field_map = array(
			'rating'         => 'rt-movie-meta-basic-rating',
			'runtime'        => 'rt-movie-meta-basic-runtime',
			'release_date'   => 'rt-movie-meta-basic-release-date',
			'content_rating' => 'rt-movie-meta-basic-content-rating',
			'image_gallery'  => 'rt-media-meta-img',
		);

		$this->meta_input_aliases = array(
			'rt-movie-basic-rating'          => 'rating',
			'rt-movie-meta-basic-rating'     => 'rating',
			'rt-movie-basic-runtime'         => 'runtime',
			'rt-movie-meta-basic-runtime'    => 'runtime',
			'rt-movie-basic-release-date'    => 'release_date',
			'rt-movie-meta-basic-release-date' => 'release_date',
			'rt-movie-basic-content-rating'  => 'content_rating',
			'rt-movie-meta-basic-content-rating' => 'content_rating',
			'rt-media-meta-img'              => 'image_gallery',
			'image_urls'                     => 'image_gallery',
		);

		$this->allowed_taxonomies = array(
			'rt-movie-genre',
			'rt-movie-label',
			'rt-movie-language',
			'rt-movie-production-company',
			'rt-movie-tag',
		);

		$this->taxonomy_field_map = array(
			'genres'               => 'rt-movie-genre',
			'labels'               => 'rt-movie-label',
			'languages'            => 'rt-movie-language',
			'production_companies' => 'rt-movie-production-company',
			'tags'                 => 'rt-movie-tag',
		);

		$this->meta_response_schema = array(
			'rt-movie-meta-basic-rating'         => array( 'type' => array( 'number', 'null' ) ),
			'rt-movie-meta-basic-runtime'        => array( 'type' => array( 'integer', 'null' ) ),
			'rt-movie-meta-basic-release-date'   => array( 'type' => array( 'string', 'null' ) ),
			'rt-movie-meta-basic-content-rating' => array( 'type' => array( 'string', 'null' ) ),
			'rt-movie-meta-crew-director'        => array( 'type' => array( 'string', 'null' ) ),
			'rt-movie-meta-crew-producer'        => array( 'type' => array( 'string', 'null' ) ),
			'rt-movie-meta-crew-writer'          => array( 'type' => array( 'string', 'null' ) ),
			'rt-movie-meta-crew-actor'           => array( 'type' => array( 'string', 'null' ) ),
			'rt-movie-meta-crew-actor-characters' => array( 'type' => array( 'string', 'null' ) ),
			'rt-media-meta-img'                  => array( 'type' => 'array' ),
		);

		$this->extra_write_args = array(
			'rating' => array(
				'description'       => __( 'Movie rating between 1.0 and 10.0.', 'rt-movie-library' ),
				'type'              => 'number',
				'minimum'           => 1,
				'maximum'           => 10,
				'sanitize_callback' => array( Cpt_Helper::class, 'sanitize_rating' ),
			),
			'runtime' => array(
				'description'       => __( 'Movie runtime in minutes (1-300).', 'rt-movie-library' ),
				'type'              => 'integer',
				'minimum'           => 1,
				'maximum'           => 300,
				'sanitize_callback' => 'absint',
			),
			'release_date' => array(
				'description'       => __( 'Movie release date in Y-m-d format.', 'rt-movie-library' ),
				'type'              => 'string',
				'format'            => 'date',
				'validate_callback' => array( Cpt_Helper::class, 'validate_date' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'content_rating' => array(
				'description'       => __( 'Movie content rating.', 'rt-movie-library' ),
				'type'              => 'string',
				'enum'              => array( 'U', 'U/A', 'PG', 'PG-13', 'R', 'NC-17' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_additional_write_args(): array {
		return array(
			'crew' => array(
				'description' => __( 'Crew assignments for movie.', 'rt-movie-library' ),
				'type'        => 'array',
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_additional_response_schema(): array {
		return array(
			'crew' => array(
				'type'                 => 'array',
				'additionalProperties' => true,
			),
		);
	}

	/**
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	protected function persist_additional_fields( int $post_id, WP_REST_Request $request ) {
		if ( ! $request->has_param( 'crew' ) ) {
			return true;
		}

		$crew_payload = $request->get_param( 'crew' );

		if ( ! is_array( $crew_payload ) ) {
			return new WP_Error( 'rt_rest_invalid_crew', __( 'Crew must be an array.', 'rt-movie-library' ), array( 'status' => 400 ) );
		}

		$role_map = array(
			'director' => 'director',
			'producer' => 'producer',
			'writer'   => 'writer',
			'actor'    => 'actor',
			'star'     => 'actor',
		);

		$grouped = array(
			'director' => array(),
			'producer' => array(),
			'writer'   => array(),
			'actor'    => array(),
		);
		$characters = array();

		/*
		 * Normalize each crew row into role buckets.
		 *
		 * Supported person formats:
		 * - numeric post ID
		 * - person slug
		 * - person title/name
		 */
		foreach ( $crew_payload as $index => $entry ) {
			if ( ! is_array( $entry ) ) {
				return new WP_Error( 'rt_rest_invalid_crew', sprintf( __( 'Crew item at index %d is invalid.', 'rt-movie-library' ), (int) $index ), array( 'status' => 400 ) );
			}

			$raw_role = isset( $entry['role'] ) ? sanitize_key( (string) $entry['role'] ) : '';
			$role     = isset( $role_map[ $raw_role ] ) ? $role_map[ $raw_role ] : '';

			if ( '' === $role ) {
				return new WP_Error( 'rt_rest_invalid_crew_role', sprintf( __( 'Crew role is invalid at index %d.', 'rt-movie-library' ), (int) $index ), array( 'status' => 400 ) );
			}

			if ( ! array_key_exists( 'person', $entry ) ) {
				return new WP_Error( 'rt_rest_invalid_crew_person', sprintf( __( 'Crew person is missing at index %d.', 'rt-movie-library' ), (int) $index ), array( 'status' => 400 ) );
			}

			$person_id = Cpt_Helper::resolve_person_reference( $entry['person'] );

			if ( $person_id <= 0 ) {
				return new WP_Error( 'rt_rest_invalid_crew_person', sprintf( __( 'Crew person could not be resolved at index %d.', 'rt-movie-library' ), (int) $index ), array( 'status' => 400 ) );
			}

			$grouped[ $role ][] = $person_id;

			if ( 'actor' === $role && isset( $entry['character'] ) ) {
				$character_name = sanitize_text_field( (string) $entry['character'] );
				if ( '' !== $character_name ) {
					$characters[ $person_id ] = $character_name;
				}
			}
		}

		// Deduplicate role buckets before persisting.
		$grouped['director'] = array_values( array_unique( array_map( 'absint', $grouped['director'] ) ) );
		$grouped['producer'] = array_values( array_unique( array_map( 'absint', $grouped['producer'] ) ) );
		$grouped['writer']   = array_values( array_unique( array_map( 'absint', $grouped['writer'] ) ) );
		$grouped['actor']    = array_values( array_unique( array_map( 'absint', $grouped['actor'] ) ) );

		$this->upsert_json_array_meta( $post_id, 'rt-movie-meta-crew-director', $grouped['director'] );
		$this->upsert_json_array_meta( $post_id, 'rt-movie-meta-crew-producer', $grouped['producer'] );
		$this->upsert_json_array_meta( $post_id, 'rt-movie-meta-crew-writer', $grouped['writer'] );
		$this->upsert_json_array_meta( $post_id, 'rt-movie-meta-crew-actor', $grouped['actor'] );
		$this->upsert_json_object_meta( $post_id, 'rt-movie-meta-crew-actor-characters', $characters, $grouped['actor'] );
		// Keep internal person shadow taxonomy in sync with crew assignments.
		$this->sync_movie_person_shadow_taxonomy( $post_id, $grouped );

		return true;
	}

	/**
	 * @param int                   $post_id Post ID.
	 * @param array<string, mixed>  $grouped Grouped crew.
	 * @return void
	 */
	private function sync_movie_person_shadow_taxonomy( int $post_id, array $grouped ): void {
		$all_people = array_unique(
			array_merge(
				isset( $grouped['director'] ) && is_array( $grouped['director'] ) ? $grouped['director'] : array(),
				isset( $grouped['producer'] ) && is_array( $grouped['producer'] ) ? $grouped['producer'] : array(),
				isset( $grouped['writer'] ) && is_array( $grouped['writer'] ) ? $grouped['writer'] : array(),
				isset( $grouped['actor'] ) && is_array( $grouped['actor'] ) ? $grouped['actor'] : array()
			)
		);

		$term_ids = array();

		foreach ( $all_people as $person_id ) {
			$person = get_post( (int) $person_id );

			if ( ! $person || 'rt-person' !== $person->post_type ) {
				continue;
			}

			$slug = sanitize_title( $person->post_name . '-' . $person->ID );
			$term = term_exists( $slug, '_rt-movie-person' );

			if ( ! $term ) {
				$term = wp_insert_term( $person->post_title, '_rt-movie-person', array( 'slug' => $slug ) );
			}

			if ( ! is_wp_error( $term ) ) {
				$term_ids[] = (int) $term['term_id'];
			}
		}

		wp_set_object_terms( $post_id, $term_ids, '_rt-movie-person', false );
	}

	/**
	 * @param int            $post_id Post ID.
	 * @param string         $meta_key Meta key.
	 * @param array<int,int> $values Values.
	 * @return void
	 */
	private function upsert_json_array_meta( int $post_id, string $meta_key, array $values ): void {
		if ( empty( $values ) ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}

		update_post_meta( $post_id, $meta_key, wp_json_encode( $values ) );
	}

	/**
	 * @param int                     $post_id Post ID.
	 * @param string                  $meta_key Meta key.
	 * @param array<int|string,mixed> $values Object values.
	 * @param array<int,int>          $actors Actor IDs.
	 * @return void
	 */
	private function upsert_json_object_meta( int $post_id, string $meta_key, array $values, array $actors ): void {
		$clean = array();

		foreach ( $values as $person_id => $character_name ) {
			$person_id       = absint( $person_id );
			$character_name  = sanitize_text_field( (string) $character_name );

			if ( in_array( $person_id, $actors, true ) && '' !== $character_name ) {
				$clean[ $person_id ] = $character_name;
			}
		}

		if ( empty( $clean ) ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}

		update_post_meta( $post_id, $meta_key, wp_json_encode( $clean ) );
	}

	/**
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 */
	protected function get_additional_response_payload( int $post_id ): array {
		$crew_roles = array(
			'director' => 'rt-movie-meta-crew-director',
			'producer' => 'rt-movie-meta-crew-producer',
			'writer'   => 'rt-movie-meta-crew-writer',
			'actor'    => 'rt-movie-meta-crew-actor',
		);

		$characters_json = get_post_meta( $post_id, 'rt-movie-meta-crew-actor-characters', true );
		$characters      = is_string( $characters_json ) ? json_decode( $characters_json, true ) : array();

		if ( ! is_array( $characters ) ) {
			$characters = array();
		}

		$crew = array();

		foreach ( $crew_roles as $role => $meta_key ) {
			$raw_json = get_post_meta( $post_id, $meta_key, true );
			$ids      = is_string( $raw_json ) ? json_decode( $raw_json, true ) : array();

			if ( ! is_array( $ids ) ) {
				$ids = array();
			}

			foreach ( $ids as $person_id ) {
				$person_id = absint( $person_id );
				$person    = get_post( $person_id );

				if ( ! $person || 'rt-person' !== $person->post_type ) {
					continue;
				}

				$entry = array(
					'role'   => $role,
					'person' => array(
						'id'    => $person_id,
						'slug'  => $person->post_name,
						'title' => $person->post_title,
					),
				);

				if ( 'actor' === $role && isset( $characters[ (string) $person_id ] ) ) {
					$entry['character'] = (string) $characters[ (string) $person_id ];
				}

				$crew[] = $entry;
			}
		}

		return array( 'crew' => $crew );
	}

	/**
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	protected function get_meta_payload( int $post_id ): array {
		$payload = parent::get_meta_payload( $post_id );

		$payload['rt-movie-meta-crew-director']         = Cpt_Helper::meta_to_string_or_null( get_post_meta( $post_id, 'rt-movie-meta-crew-director', true ) );
		$payload['rt-movie-meta-crew-producer']         = Cpt_Helper::meta_to_string_or_null( get_post_meta( $post_id, 'rt-movie-meta-crew-producer', true ) );
		$payload['rt-movie-meta-crew-writer']           = Cpt_Helper::meta_to_string_or_null( get_post_meta( $post_id, 'rt-movie-meta-crew-writer', true ) );
		$payload['rt-movie-meta-crew-actor']            = Cpt_Helper::meta_to_string_or_null( get_post_meta( $post_id, 'rt-movie-meta-crew-actor', true ) );
		$payload['rt-movie-meta-crew-actor-characters'] = Cpt_Helper::meta_to_string_or_null( get_post_meta( $post_id, 'rt-movie-meta-crew-actor-characters', true ) );

		return $payload;
	}
}
