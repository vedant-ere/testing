<?php
/**
 * Movie REST controller.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Rest;

use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

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
			'video_gallery'  => 'rt-media-meta-video',
			'carousel_url'   => 'rt-movie-meta-carousel-poster',
		);

		$this->meta_input_aliases = array(
			'rt-movie-basic-rating'              => 'rating',
			'rt-movie-meta-basic-rating'         => 'rating',
			'rt-movie-basic-runtime'             => 'runtime',
			'rt-movie-meta-basic-runtime'        => 'runtime',
			'rt-movie-basic-release-date'        => 'release_date',
			'rt-movie-meta-basic-release-date'   => 'release_date',
			'rt-movie-basic-content-rating'      => 'content_rating',
			'rt-movie-meta-basic-content-rating' => 'content_rating',
			'rt-media-meta-img'                  => 'image_gallery',
			'image_urls'                         => 'image_gallery',
			'rt-media-meta-video'                => 'video_gallery',
			'video_urls'                         => 'video_gallery',
			'rt-movie-meta-carousel-poster'      => 'carousel_url',
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
		);

		$this->extra_write_args = array(
			'rating'         => array(
				'description'       => __( 'Movie rating between 1.0 and 10.0.', 'rt-movie-library' ),
				'type'              => 'number',
				'minimum'           => 1,
				'maximum'           => 10,
				'sanitize_callback' => array( Cpt_Helper::class, 'sanitize_rating' ),
			),
			'runtime'        => array(
				'description'       => __( 'Movie runtime in minutes (1-300).', 'rt-movie-library' ),
				'type'              => 'integer',
				'minimum'           => 1,
				'maximum'           => 300,
				'sanitize_callback' => 'absint',
			),
			'release_date'   => array(
				'description'       => __( 'Movie release date in Y-m-d format.', 'rt-movie-library' ),
				'type'              => 'string',
				'format'            => 'date',
				'validate_callback' => array( Cpt_Helper::class, 'validate_date' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'content_rating' => array(
				'description'       => __( 'Movie content rating.', 'rt-movie-library' ),
				'type'              => 'string',
				'enum'              => array( 'U', 'U/A', 'G', 'PG', 'PG-13', 'R', 'NC-17' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Create callback with duplicate detection and smart merge.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$request_validation = $this->validate_request_payload( $request );

		if ( is_wp_error( $request_validation ) ) {
			return $request_validation;
		}

		$title = $this->normalize_title( (string) $request->get_param( 'title' ) );

		if ( '' === $title ) {
			return parent::create_item( $request );
		}

		$candidates = $this->find_movies_by_normalized_title( $title );

		if ( empty( $candidates ) ) {
			return parent::create_item( $request );
		}

		$incoming_signature = $this->build_incoming_signature( $request );

		foreach ( $candidates as $candidate ) {
			if ( $this->is_exact_duplicate_for_request( $candidate->ID, $request ) ) {
				return new WP_Error(
					'movie_duplicate_found',
					__( 'Movie with identical details already exists.', 'rt-movie-library' ),
					array(
						'status'               => 409,
						'existing_movie_id'    => $candidate->ID,
						'existing_movie_title' => $candidate->post_title,
						'reason'               => __( 'All core details match exactly (title, release_date, runtime, director, content_rating).', 'rt-movie-library' ),
						'suggestion'           => sprintf(
							/* translators: %d: movie ID. */
							__( 'Use PATCH update on movie ID %d for incremental changes.', 'rt-movie-library' ),
							$candidate->ID
						),
					)
				);
			}
		}

		$target = $this->pick_best_merge_candidate( $candidates, $incoming_signature );
		if ( ! $target instanceof WP_Post ) {
			return parent::create_item( $request );
		}

		return $this->merge_movie_from_request( $target->ID, $request );
	}

	/**
	 * Collection read callback: published movies only.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$request->set_param( 'status', array( 'publish' ) );

		return parent::get_items( $request );
	}

	/**
	 * Single movie read callback by ID: published movies only.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$post = get_post( (int) $request['id'] );

		if ( ! $post instanceof WP_Post || 'rt-movie' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new WP_Error( 'rt_rest_not_found', __( 'Requested item was not found.', 'rt-movie-library' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $this->prepare_item_data( $post ), 200 );
	}

	/**
	 * Single movie read callback by slug: published movies only.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item_by_slug( $request ) {
		$slug = sanitize_title( (string) $request['slug'] );

		$query = new WP_Query(
			array(
				'post_type'      => 'rt-movie',
				'name'           => $slug,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);

		if ( empty( $query->posts ) || ! $query->posts[0] instanceof WP_Post ) {
			return new WP_Error( 'rt_rest_not_found', __( 'Requested item was not found.', 'rt-movie-library' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $this->prepare_item_data( $query->posts[0] ), 200 );
	}

	/**
	 * Register additional writable fields for movie resources.
	 *
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
	 * Validate movie request payload before create/update/merge.
	 *
	 * Ensures top-level fields are validated the same way as meta payload keys.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	protected function validate_request_payload( WP_REST_Request $request ) {
		$field_to_meta_map = array(
			'rating'         => 'rt-movie-meta-basic-rating',
			'runtime'        => 'rt-movie-meta-basic-runtime',
			'release_date'   => 'rt-movie-meta-basic-release-date',
			'content_rating' => 'rt-movie-meta-basic-content-rating',
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

	/**
	 * Validate movie meta values for both top-level and `meta` payload inputs.
	 *
	 * @param string          $meta_key Meta key.
	 * @param mixed           $value Meta value.
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	protected function validate_meta_value( string $meta_key, $value, WP_REST_Request $request ) {
		unset( $request );

		if ( null === $value || '' === $value ) {
			return true;
		}

		if ( 'rt-movie-meta-basic-rating' === $meta_key ) {
			if ( ! is_numeric( $value ) ) {
				return new WP_Error( 'rt_rest_invalid_rating', __( 'Movie rating must be numeric.', 'rt-movie-library' ), array( 'status' => 400 ) );
			}

			$rating = (float) $value;
			if ( $rating < 1 || $rating > 10 ) {
				return new WP_Error( 'rt_rest_invalid_rating', __( 'Movie rating must be between 1 and 10.', 'rt-movie-library' ), array( 'status' => 400 ) );
			}
		}

		if ( 'rt-movie-meta-basic-runtime' === $meta_key ) {
			if ( ! is_numeric( $value ) ) {
				return new WP_Error( 'rt_rest_invalid_runtime', __( 'Movie runtime must be numeric.', 'rt-movie-library' ), array( 'status' => 400 ) );
			}

			$runtime = (float) $value;
			if ( floor( $runtime ) !== $runtime ) {
				return new WP_Error( 'rt_rest_invalid_runtime', __( 'Movie runtime must be an integer.', 'rt-movie-library' ), array( 'status' => 400 ) );
			}

			$runtime = (int) $runtime;
			if ( $runtime < 1 || $runtime > 300 ) {
				return new WP_Error( 'rt_rest_invalid_runtime', __( 'Movie runtime must be between 1 and 300.', 'rt-movie-library' ), array( 'status' => 400 ) );
			}
		}

		if ( 'rt-movie-meta-basic-release-date' === $meta_key ) {
			return Cpt_Helper::validate_date( $value, new WP_REST_Request( 'POST' ), 'release_date' );
		}

		if ( 'rt-movie-meta-basic-content-rating' === $meta_key ) {
			$normalized = $this->normalize_content_rating( $value );
			$allowed    = array( 'U', 'U/A', 'G', 'PG', 'PG-13', 'R', 'NC-17' );

			if ( null === $normalized || ! in_array( $normalized, $allowed, true ) ) {
				return new WP_Error( 'rt_rest_invalid_content_rating', __( 'Movie content rating is invalid.', 'rt-movie-library' ), array( 'status' => 400 ) );
			}
		}

		return true;
	}

	/**
	 * Register additional response schema fields for movies.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_additional_response_schema(): array {
		return array(
			'crew'               => array(
				'type'                 => 'array',
				'additionalProperties' => true,
			),
			'featured_image_url' => array(
				'type' => array( 'string', 'null' ),
			),
			'taxonomy_terms'     => array(
				'type'                 => 'object',
				'additionalProperties' => true,
			),
			'comments'           => array(
				'type'  => 'array',
				'items' => array(
					'type' => 'object',
				),
			),
			'gallery_image_urls' => array(
				'type' => 'array',
			),
			'gallery_video_urls' => array(
				'type' => 'array',
			),
			'carousel_url'       => array(
				'type' => array( 'string', 'null' ),
			),
		);
	}

	/**
	 * Persist movie-specific fields (crew metadata and relations).
	 *
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

		$grouped    = array(
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
				/* translators: %d: invalid crew array index. */
				return new WP_Error( 'rt_rest_invalid_crew', sprintf( __( 'Crew item at index %d is invalid.', 'rt-movie-library' ), (int) $index ), array( 'status' => 400 ) );
			}

			$raw_role = isset( $entry['role'] ) ? sanitize_key( (string) $entry['role'] ) : '';
			$role     = isset( $role_map[ $raw_role ] ) ? $role_map[ $raw_role ] : '';

			if ( '' === $role ) {
				/* translators: %d: invalid crew array index. */
				return new WP_Error( 'rt_rest_invalid_crew_role', sprintf( __( 'Crew role is invalid at index %d.', 'rt-movie-library' ), (int) $index ), array( 'status' => 400 ) );
			}

			if ( ! array_key_exists( 'person', $entry ) ) {
				/* translators: %d: invalid crew array index. */
				return new WP_Error( 'rt_rest_invalid_crew_person', sprintf( __( 'Crew person is missing at index %d.', 'rt-movie-library' ), (int) $index ), array( 'status' => 400 ) );
			}

			$person_id = Cpt_Helper::find_or_create_person_reference( $entry['person'], $role );

			if ( $person_id <= 0 ) {
				/* translators: %d: invalid crew array index. */
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
	 * Sync internal movie-person taxonomy terms from crew assignments.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $grouped Grouped crew.
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
	 * Upsert a JSON array meta value or remove when empty.
	 *
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
	 * Upsert a JSON object meta value for actor-character mappings.
	 *
	 * @param int                     $post_id Post ID.
	 * @param string                  $meta_key Meta key.
	 * @param array<int|string,mixed> $values Object values.
	 * @param array<int,int>          $actors Actor IDs.
	 * @return void
	 */
	private function upsert_json_object_meta( int $post_id, string $meta_key, array $values, array $actors ): void {
		$clean = array();

		foreach ( $values as $person_id => $character_name ) {
			$person_id      = absint( $person_id );
			$character_name = sanitize_text_field( (string) $character_name );

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
	 * Build movie-specific response payload fields.
	 *
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

		return array(
			'crew'               => $crew,
			'featured_image_url' => $this->get_featured_image_url( $post_id ),
			'taxonomy_terms'     => $this->get_taxonomy_terms_payload( $post_id ),
			'comments'           => $this->get_comments_payload( $post_id ),
			'gallery_image_urls' => Cpt_Helper::image_ids_to_urls( $this->resolve_attachment_ids( get_post_meta( $post_id, 'rt-media-meta-img', true ) ) ),
			'gallery_video_urls' => Cpt_Helper::image_ids_to_urls( $this->resolve_attachment_ids( get_post_meta( $post_id, 'rt-media-meta-video', true ) ) ),
			'carousel_url'       => $this->attachment_url_or_null( get_post_meta( $post_id, 'rt-movie-meta-carousel-poster', true ) ),
		);
	}

	/**
	 * Prepare movie response data and keep only taxonomy names payload.
	 *
	 * @param WP_Post|null $post Post object.
	 * @return array<string, mixed>
	 */
	protected function prepare_item_data( ?WP_Post $post ): array {
		$data = parent::prepare_item_data( $post );

		if ( isset( $data['taxonomies'] ) ) {
			unset( $data['taxonomies'] );
		}

		return $data;
	}

	/**
	 * Build normalized meta payload for movies.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	protected function get_meta_payload( int $post_id ): array {
		$payload = parent::get_meta_payload( $post_id );

		// Return only scalar movie basics in `meta`; complex media/crew data is exposed via dedicated top-level fields.
		unset( $payload['rt-media-meta-img'] );
		unset( $payload['rt-media-meta-video'] );
		unset( $payload['rt-movie-meta-carousel-poster'] );

		return $payload;
	}

	/**
	 * Upsert movie media meta keys with attachment resolution.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $value Value.
	 * @return void
	 */
	protected function upsert_meta_value( int $post_id, string $meta_key, $value ): void {
		if ( 'rt-media-meta-video' === $meta_key ) {
			$video_ids = $this->resolve_media_attachment_ids( $value, 'video' );
			if ( empty( $video_ids ) ) {
				delete_post_meta( $post_id, $meta_key );
			} else {
				update_post_meta( $post_id, $meta_key, wp_json_encode( $video_ids ) );
			}
			return;
		}

		if ( 'rt-movie-meta-carousel-poster' === $meta_key ) {
			$attachment_id = 0;

			if ( is_numeric( $value ) ) {
				$attachment_id = absint( $value );
			} elseif ( is_string( $value ) ) {
				// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid -- Needed to resolve provided media URLs to local attachments.
				$attachment_id = absint( attachment_url_to_postid( esc_url_raw( $value ) ) );
			}

			if ( $attachment_id > 0 && 'attachment' === get_post_type( $attachment_id ) ) {
				update_post_meta( $post_id, $meta_key, $attachment_id );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}

			return;
		}

		parent::upsert_meta_value( $post_id, $meta_key, $value );
	}

	/**
	 * Resolve attachment URL or return null.
	 *
	 * @param mixed $attachment_id Attachment ID.
	 * @return string|null
	 */
	private function attachment_url_or_null( $attachment_id ): ?string {
		$attachment_id = absint( $attachment_id );

		if ( $attachment_id <= 0 ) {
			return null;
		}

		$url = wp_get_attachment_url( $attachment_id );
		return is_string( $url ) && '' !== $url ? $url : null;
	}

	/**
	 * Resolve potentially JSON encoded attachment IDs.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, int>
	 */
	private function resolve_attachment_ids( $value ): array {
		if ( is_array( $value ) ) {
			return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
		}

		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				return array_values( array_unique( array_filter( array_map( 'absint', $decoded ) ) ) );
			}

			if ( is_numeric( $value ) ) {
				$id = absint( $value );
				return $id > 0 ? array( $id ) : array();
			}
		}

		return array();
	}

	/**
	 * Resolve media attachment IDs from IDs/URLs list.
	 *
	 * @param mixed  $value Value.
	 * @param string $type Media type (image|video).
	 * @return array<int, int>
	 */
	private function resolve_media_attachment_ids( $value, string $type ): array {
		$values = is_array( $value ) ? $value : ( is_scalar( $value ) ? array( $value ) : array() );
		$ids    = array();

		foreach ( $values as $item ) {
			$attachment_id = 0;

			if ( is_numeric( $item ) ) {
				$attachment_id = absint( $item );
			} elseif ( is_string( $item ) ) {
				// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid -- Needed to resolve provided media URLs to local attachments.
				$attachment_id = absint( attachment_url_to_postid( esc_url_raw( $item ) ) );
			}

			if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
				continue;
			}

			$mime = get_post_mime_type( $attachment_id );
			if ( ! is_string( $mime ) || 0 !== strpos( $mime, $type . '/' ) ) {
				continue;
			}

			$ids[] = $attachment_id;
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Featured image URL.
	 *
	 * @param int $post_id Post ID.
	 * @return string|null
	 */
	private function get_featured_image_url( int $post_id ): ?string {
		$url = get_the_post_thumbnail_url( $post_id, 'full' );
		return is_string( $url ) && '' !== $url ? $url : null;
	}

	/**
	 * Taxonomy names payload.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, array<int, string>>
	 */
	private function get_taxonomy_terms_payload( int $post_id ): array {
		$payload = array();

		foreach ( $this->allowed_taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post_id, $taxonomy );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				$payload[ $taxonomy ] = array();
				continue;
			}

			$payload[ $taxonomy ] = array_values(
				array_filter(
					array_map(
						static function ( $term ): string {
							return isset( $term->name ) ? (string) $term->name : '';
						},
						$terms
					)
				)
			);
		}

		return $payload;
	}

	/**
	 * Approved comments payload.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_comments_payload( int $post_id ): array {
		$comments = get_comments(
			array(
				'post_id' => $post_id,
				'status'  => 'approve',
				'orderby' => 'comment_ID',
				'order'   => 'ASC',
			)
		);

		$payload = array();

		foreach ( $comments as $comment ) {
			$payload[] = array(
				'id'      => (int) $comment->comment_ID,
				'parent'  => (int) $comment->comment_parent,
				'author'  => (string) $comment->comment_author,
				'url'     => (string) $comment->comment_author_url,
				'date'    => (string) $comment->comment_date_gmt,
				'content' => (string) $comment->comment_content,
			);
		}

		return $payload;
	}

	/**
	 * Normalize title for duplicate matching.
	 *
	 * @param string $title Raw title.
	 * @return string
	 */
	private function normalize_title( string $title ): string {
		$title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );
		$title = preg_replace( '/\s+/', ' ', trim( strtolower( $title ) ) );

		return is_string( $title ) ? $title : '';
	}

	/**
	 * Normalize runtime to integer minutes.
	 *
	 * @param mixed $runtime Runtime value.
	 * @return int|null
	 */
	private function normalize_runtime( $runtime ): ?int {
		if ( null === $runtime || '' === $runtime ) {
			return null;
		}

		if ( is_numeric( $runtime ) ) {
			return absint( $runtime );
		}

		if ( is_string( $runtime ) && preg_match( '/\d+/', $runtime, $matches ) ) {
			return absint( $matches[0] );
		}

		return null;
	}

	/**
	 * Normalize date as Y-m-d.
	 *
	 * @param mixed $date Date value.
	 * @return string|null
	 */
	private function normalize_date( $date ): ?string {
		if ( null === $date || '' === $date ) {
			return null;
		}

		if ( is_string( $date ) ) {
			$date = trim( $date );

			$strict = \DateTimeImmutable::createFromFormat( 'Y-m-d', $date );
			if ( $strict instanceof \DateTimeImmutable && $strict->format( 'Y-m-d' ) === $date ) {
				return $date;
			}

			$timestamp = strtotime( $date );
			if ( false !== $timestamp ) {
				return gmdate( 'Y-m-d', $timestamp );
			}
		}

		return null;
	}

	/**
	 * Normalize content rating for comparison.
	 *
	 * @param mixed $rating Rating.
	 * @return string|null
	 */
	private function normalize_content_rating( $rating ): ?string {
		if ( null === $rating || '' === $rating ) {
			return null;
		}

		$rating = strtoupper( trim( (string) $rating ) );

		return '' !== $rating ? $rating : null;
	}

	/**
	 * Resolve normalized director names from request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<int, string>
	 */
	private function get_incoming_director_names( WP_REST_Request $request ): array {
		$directors = array();
		$crew      = $request->get_param( 'crew' );

		if ( is_array( $crew ) ) {
			foreach ( $crew as $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}

				$role = sanitize_key( (string) ( $entry['role'] ?? '' ) );
				if ( 'director' !== $role ) {
					continue;
				}

				if ( ! array_key_exists( 'person', $entry ) ) {
					continue;
				}

				$person_id = Cpt_Helper::resolve_person_reference( $entry['person'] );
				if ( $person_id > 0 ) {
					$name = $this->normalize_title( (string) get_the_title( $person_id ) );
					if ( '' !== $name ) {
						$directors[] = $name;
					}
					continue;
				}

				$fallback = $this->normalize_title( (string) $entry['person'] );
				if ( '' !== $fallback ) {
					$directors[] = $fallback;
				}
			}
		}

		sort( $directors );

		return array_values( array_unique( array_filter( $directors ) ) );
	}

	/**
	 * Resolve normalized director names from existing movie meta.
	 *
	 * @param int $post_id Movie post ID.
	 * @return array<int, string>
	 */
	private function get_existing_director_names( int $post_id ): array {
		$raw_json = get_post_meta( $post_id, 'rt-movie-meta-crew-director', true );
		$ids      = is_string( $raw_json ) ? json_decode( $raw_json, true ) : array();

		if ( ! is_array( $ids ) ) {
			return array();
		}

		$names = array();

		foreach ( $ids as $person_id ) {
			$person_id = absint( $person_id );
			if ( $person_id <= 0 ) {
				continue;
			}

			$name = $this->normalize_title( (string) get_the_title( $person_id ) );
			if ( '' !== $name ) {
				$names[] = $name;
			}
		}

		sort( $names );

		return array_values( array_unique( array_filter( $names ) ) );
	}

	/**
	 * Find movies with matching normalized title.
	 *
	 * @param string $normalized_title Normalized title.
	 * @return array<int, WP_Post>
	 */
	private function find_movies_by_normalized_title( string $normalized_title ): array {
		$query = new WP_Query(
			array(
				'post_type'      => 'rt-movie',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => -1,
				's'              => $normalized_title,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		$matches = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			if ( $this->normalize_title( (string) $post->post_title ) === $normalized_title ) {
				$matches[] = $post;
			}
		}

		return $matches;
	}

	/**
	 * Build incoming duplicate signature from request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<string, mixed>
	 */
	private function build_incoming_signature( WP_REST_Request $request ): array {
		$meta = $request->get_param( 'meta' );
		$meta = is_array( $meta ) ? $meta : array();

		$release_date = $request->has_param( 'release_date' ) ? $request->get_param( 'release_date' ) : ( $meta['rt-movie-meta-basic-release-date'] ?? null );
		$runtime      = $request->has_param( 'runtime' ) ? $request->get_param( 'runtime' ) : ( $meta['rt-movie-meta-basic-runtime'] ?? null );
		$content      = $request->has_param( 'content_rating' ) ? $request->get_param( 'content_rating' ) : ( $meta['rt-movie-meta-basic-content-rating'] ?? null );

		return array(
			'title'          => $this->normalize_title( (string) $request->get_param( 'title' ) ),
			'release_date'   => $this->normalize_date( $release_date ),
			'runtime'        => $this->normalize_runtime( $runtime ),
			'content_rating' => $this->normalize_content_rating( $content ),
			'directors'      => $this->get_incoming_director_names( $request ),
		);
	}

	/**
	 * Check if candidate movie is exact duplicate.
	 *
	 * Compares only fields explicitly provided in the incoming request.
	 *
	 * @param int             $post_id Candidate movie ID.
	 * @param WP_REST_Request $request Incoming request payload.
	 * @return bool
	 */
	private function is_exact_duplicate_for_request( int $post_id, WP_REST_Request $request ): bool {
		$has_any_comparison = false;

		if ( $request->has_param( 'title' ) ) {
			$has_any_comparison = true;
			$incoming_title     = $this->normalize_title( (string) $request->get_param( 'title' ) );
			$existing_title     = $this->normalize_title( (string) get_the_title( $post_id ) );

			if ( $incoming_title !== $existing_title ) {
				return false;
			}
		}

		if ( $request->has_param( 'content' ) ) {
			$has_any_comparison = true;
			if ( (string) $request->get_param( 'content' ) !== (string) get_post_field( 'post_content', $post_id ) ) {
				return false;
			}
		}

		if ( $request->has_param( 'excerpt' ) ) {
			$has_any_comparison = true;
			if ( (string) $request->get_param( 'excerpt' ) !== (string) get_post_field( 'post_excerpt', $post_id ) ) {
				return false;
			}
		}

		if ( $request->has_param( 'status' ) ) {
			$has_any_comparison = true;
			if ( sanitize_key( (string) $request->get_param( 'status' ) ) !== (string) get_post_field( 'post_status', $post_id ) ) {
				return false;
			}
		}

		if ( $request->has_param( 'featured_media' ) ) {
			$has_any_comparison = true;
			if ( absint( $request->get_param( 'featured_media' ) ) !== (int) get_post_thumbnail_id( $post_id ) ) {
				return false;
			}
		}

		$core_meta_keys = array(
			'release_date'   => 'rt-movie-meta-basic-release-date',
			'runtime'        => 'rt-movie-meta-basic-runtime',
			'content_rating' => 'rt-movie-meta-basic-content-rating',
		);

		foreach ( $core_meta_keys as $request_key => $meta_key ) {
			if ( ! $request->has_param( $request_key ) ) {
				continue;
			}

			$has_any_comparison = true;
			$incoming           = $request->get_param( $request_key );
			$existing           = get_post_meta( $post_id, $meta_key, true );

			if ( 'release_date' === $request_key ) {
				if ( $this->normalize_date( $incoming ) !== $this->normalize_date( $existing ) ) {
					return false;
				}
			} elseif ( 'runtime' === $request_key ) {
				if ( $this->normalize_runtime( $incoming ) !== $this->normalize_runtime( $existing ) ) {
					return false;
				}
			} elseif ( 'content_rating' === $request_key ) {
				if ( $this->normalize_content_rating( $incoming ) !== $this->normalize_content_rating( $existing ) ) {
					return false;
				}
			}
		}

		$meta_payload = $request->get_param( 'meta' );
		if ( is_array( $meta_payload ) ) {
			foreach ( $meta_payload as $key => $value ) {
				if ( ! is_string( $key ) ) {
					continue;
				}

				$meta_key = $this->resolve_meta_key_from_input_key( $key );
				if ( null === $meta_key ) {
					continue;
				}

				$has_any_comparison = true;

				if ( ! $this->is_meta_value_identical( $post_id, $meta_key, $value ) ) {
					return false;
				}
			}
		}

		if ( ! $this->is_taxonomy_payload_identical( $post_id, $request, $has_any_comparison ) ) {
			return false;
		}

		if ( $request->has_param( 'crew' ) ) {
			$has_any_comparison = true;

			if ( ! $this->is_crew_payload_identical( $post_id, $request->get_param( 'crew' ) ) ) {
				return false;
			}
		}

		return $has_any_comparison;
	}

	/**
	 * Compare incoming meta payload value against existing stored value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $incoming Incoming value.
	 * @return bool
	 */
	private function is_meta_value_identical( int $post_id, string $meta_key, $incoming ): bool {
		$existing = get_post_meta( $post_id, $meta_key, true );

		if ( 'rt-movie-meta-basic-release-date' === $meta_key ) {
			return $this->normalize_date( $incoming ) === $this->normalize_date( $existing );
		}

		if ( 'rt-movie-meta-basic-runtime' === $meta_key ) {
			return $this->normalize_runtime( $incoming ) === $this->normalize_runtime( $existing );
		}

		if ( 'rt-movie-meta-basic-content-rating' === $meta_key ) {
			return $this->normalize_content_rating( $incoming ) === $this->normalize_content_rating( $existing );
		}

		if ( 'rt-media-meta-img' === $meta_key ) {
			$incoming_ids = Cpt_Helper::resolve_image_attachment_ids( $incoming );
			$existing_ids = Cpt_Helper::resolve_image_attachment_ids( $existing );
			sort( $incoming_ids );
			sort( $existing_ids );

			return $incoming_ids === $existing_ids;
		}

		if ( 'rt-media-meta-video' === $meta_key ) {
			$incoming_ids = $this->resolve_media_attachment_ids( $incoming, 'video' );
			$existing_ids = $this->resolve_attachment_ids( $existing );
			sort( $incoming_ids );
			sort( $existing_ids );

			return $incoming_ids === $existing_ids;
		}

		if ( 'rt-movie-meta-carousel-poster' === $meta_key ) {
			$incoming_id = 0;
			if ( is_numeric( $incoming ) ) {
				$incoming_id = absint( $incoming );
			} elseif ( is_string( $incoming ) ) {
				// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid -- Needed to resolve provided media URLs to local attachments.
				$incoming_id = absint( attachment_url_to_postid( esc_url_raw( $incoming ) ) );
			}

				return absint( $existing ) === $incoming_id;
		}

		return (string) $incoming === (string) $existing;
	}

	/**
	 * Compare taxonomy payload against existing terms.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request.
	 * @param bool            $has_any_comparison Whether comparisons already happened.
	 * @return bool
	 */
	private function is_taxonomy_payload_identical( int $post_id, WP_REST_Request $request, bool &$has_any_comparison ): bool {
		foreach ( $this->taxonomy_field_map as $request_key => $taxonomy ) {
			if ( ! $request->has_param( $request_key ) ) {
				continue;
			}

			$term_refs = $request->get_param( $request_key );
			$term_refs = is_array( $term_refs ) ? $term_refs : array();

			$incoming_ids = Cpt_Helper::resolve_term_ids_for_taxonomy( $taxonomy, $term_refs );
			$existing_ids = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			$existing_ids = is_wp_error( $existing_ids ) || ! is_array( $existing_ids ) ? array() : array_map( 'absint', $existing_ids );

			sort( $incoming_ids );
			sort( $existing_ids );

			$has_any_comparison = true;

			if ( $incoming_ids !== $existing_ids ) {
				return false;
			}
		}

		$tax_payload = $request->get_param( 'taxonomies' );
		if ( ! is_array( $tax_payload ) ) {
			return true;
		}

		foreach ( $tax_payload as $taxonomy => $term_refs ) {
			if ( ! is_string( $taxonomy ) || ! in_array( $taxonomy, $this->allowed_taxonomies, true ) ) {
				continue;
			}

			$term_refs = is_array( $term_refs ) ? $term_refs : array( $term_refs );

			$incoming_ids = Cpt_Helper::resolve_term_ids_for_taxonomy( $taxonomy, $term_refs );
			$existing_ids = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			$existing_ids = is_wp_error( $existing_ids ) || ! is_array( $existing_ids ) ? array() : array_map( 'absint', $existing_ids );

			sort( $incoming_ids );
			sort( $existing_ids );

			$has_any_comparison = true;

			if ( $incoming_ids !== $existing_ids ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Compare crew payload against existing stored crew.
	 *
	 * @param int   $post_id Post ID.
	 * @param mixed $crew_payload Crew payload.
	 * @return bool
	 */
	private function is_crew_payload_identical( int $post_id, $crew_payload ): bool {
		if ( ! is_array( $crew_payload ) ) {
			return false;
		}

		$incoming_grouped    = array(
			'director' => array(),
			'producer' => array(),
			'writer'   => array(),
			'actor'    => array(),
		);
		$incoming_characters = array();

		foreach ( $crew_payload as $entry ) {
			if ( ! is_array( $entry ) ) {
				return false;
			}

			$role = sanitize_key( (string) ( $entry['role'] ?? '' ) );
			if ( 'star' === $role ) {
				$role = 'actor';
			}

			if ( ! in_array( $role, array( 'director', 'producer', 'writer', 'actor' ), true ) ) {
				return false;
			}

			if ( ! array_key_exists( 'person', $entry ) ) {
				return false;
			}

			$person_id = Cpt_Helper::resolve_person_reference( $entry['person'] );
			if ( $person_id <= 0 ) {
				return false;
			}

			$incoming_grouped[ $role ][] = $person_id;

			if ( 'actor' === $role && isset( $entry['character'] ) ) {
				$character = sanitize_text_field( (string) $entry['character'] );
				if ( '' !== $character ) {
					$incoming_characters[ $person_id ] = $character;
				}
			}
		}

		foreach ( $incoming_grouped as $role => &$ids ) {
			$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );
			sort( $ids );
		}
		unset( $ids );

		$existing_grouped = array(
			'director' => $this->resolve_attachment_ids( get_post_meta( $post_id, 'rt-movie-meta-crew-director', true ) ),
			'producer' => $this->resolve_attachment_ids( get_post_meta( $post_id, 'rt-movie-meta-crew-producer', true ) ),
			'writer'   => $this->resolve_attachment_ids( get_post_meta( $post_id, 'rt-movie-meta-crew-writer', true ) ),
			'actor'    => $this->resolve_attachment_ids( get_post_meta( $post_id, 'rt-movie-meta-crew-actor', true ) ),
		);

		foreach ( $existing_grouped as &$ids ) {
			$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );
			sort( $ids );
		}
		unset( $ids );

		if ( $incoming_grouped !== $existing_grouped ) {
			return false;
		}

		$existing_chars_raw = get_post_meta( $post_id, 'rt-movie-meta-crew-actor-characters', true );
		$existing_chars     = is_string( $existing_chars_raw ) ? json_decode( $existing_chars_raw, true ) : array();
		$existing_chars     = is_array( $existing_chars ) ? $existing_chars : array();

		$normalized_existing = array();
		foreach ( $existing_chars as $person_id => $character ) {
			$person_id = absint( $person_id );
			$character = sanitize_text_field( (string) $character );
			if ( $person_id > 0 && '' !== $character ) {
				$normalized_existing[ $person_id ] = $character;
			}
		}

		ksort( $incoming_characters );
		ksort( $normalized_existing );

		return $incoming_characters === $normalized_existing;
	}

	/**
	 * Pick best merge candidate by matching score.
	 *
	 * @param array<int, WP_Post>  $candidates Candidates.
	 * @param array<string, mixed> $incoming Incoming signature.
	 * @return WP_Post|null
	 */
	private function pick_best_merge_candidate( array $candidates, array $incoming ): ?WP_Post {
		$best       = null;
		$best_score = -1;

		foreach ( $candidates as $candidate ) {
			$score = 0;

			if ( $this->normalize_title( (string) $candidate->post_title ) === (string) $incoming['title'] ) {
				++$score;
			}

			$release = $this->normalize_date( get_post_meta( $candidate->ID, 'rt-movie-meta-basic-release-date', true ) );
			if ( ! empty( $incoming['release_date'] ) && $release === $incoming['release_date'] ) {
				++$score;
			}

			$runtime = $this->normalize_runtime( get_post_meta( $candidate->ID, 'rt-movie-meta-basic-runtime', true ) );
			if ( ! empty( $incoming['runtime'] ) && $runtime === $incoming['runtime'] ) {
				++$score;
			}

			$content_rating = $this->normalize_content_rating( get_post_meta( $candidate->ID, 'rt-movie-meta-basic-content-rating', true ) );
			if ( ! empty( $incoming['content_rating'] ) && $content_rating === $incoming['content_rating'] ) {
				++$score;
			}

			$directors = $this->get_existing_director_names( $candidate->ID );
			if ( ! empty( $incoming['directors'] ) && $directors === $incoming['directors'] ) {
				++$score;
			}

			if ( $score > $best_score ) {
				$best_score = $score;
				$best       = $candidate;
			}
		}

		return $best;
	}

	/**
	 * Merge request payload into an existing movie.
	 *
	 * @param int             $post_id Existing movie ID.
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	private function merge_movie_from_request( int $post_id, WP_REST_Request $request ) {
		$updated_fields   = array();
		$preserved_fields = array();

		$postarr     = array( 'ID' => $post_id );
		$post_update = false;

		$core_fields = array(
			'title'   => 'post_title',
			'content' => 'post_content',
			'excerpt' => 'post_excerpt',
			'status'  => 'post_status',
		);

		foreach ( $core_fields as $request_key => $post_key ) {
			if ( ! $request->has_param( $request_key ) ) {
				$preserved_fields[] = $request_key;
				continue;
			}

			$value = $request->get_param( $request_key );
			if ( ! $this->has_meaningful_value( $value ) ) {
				$preserved_fields[] = $request_key;
				continue;
			}

			$postarr[ $post_key ] = $value;
			$post_update          = true;
			$updated_fields[]     = $request_key;
		}

		if ( $post_update ) {
			$updated = wp_update_post( wp_slash( $postarr ), true );

			if ( is_wp_error( $updated ) ) {
				return new WP_Error(
					'rt_rest_update_failed',
					__( 'Failed to merge movie.', 'rt-movie-library' ),
					array(
						'status'  => 400,
						'details' => $updated->get_error_message(),
					)
				);
			}
		}

		// Meta updates for explicitly provided and non-empty fields.
		foreach ( $this->meta_field_map as $request_key => $meta_key ) {
			if ( ! $request->has_param( $request_key ) ) {
				continue;
			}

			$value = $request->get_param( $request_key );
			if ( ! $this->has_meaningful_value( $value ) ) {
				continue;
			}

			$validation = $this->validate_meta_value( $meta_key, $value, $request );

			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			$this->upsert_meta_value( $post_id, $meta_key, $value );
			$updated_fields[] = $request_key;
		}

		$meta_payload = $request->get_param( 'meta' );
		if ( is_array( $meta_payload ) ) {
			foreach ( $meta_payload as $key => $value ) {
				if ( ! is_string( $key ) || ! $this->has_meaningful_value( $value ) ) {
					continue;
				}

				$meta_key = $this->resolve_meta_key_from_input_key( $key );
				if ( null === $meta_key ) {
					continue;
				}

				$validation = $this->validate_meta_value( $meta_key, $value, $request );

				if ( is_wp_error( $validation ) ) {
					return $validation;
				}

				$this->upsert_meta_value( $post_id, $meta_key, $value );
				$updated_fields[] = $key;
			}
		}

		// PATCH behavior for featured media: only non-empty provided values are applied.
		if ( $request->has_param( 'featured_media' ) ) {
			$featured = absint( $request->get_param( 'featured_media' ) );
			if ( $featured > 0 ) {
				set_post_thumbnail( $post_id, $featured );
				$updated_fields[] = 'featured_media';
			} else {
				$preserved_fields[] = 'featured_media';
			}
		}

		$tax_updated    = $this->merge_taxonomy_fields( $post_id, $request );
		$updated_fields = array_merge( $updated_fields, $tax_updated );

		if ( $request->has_param( 'crew' ) ) {
			$crew = $request->get_param( 'crew' );
			if ( is_array( $crew ) && ! empty( $crew ) ) {
				$crew_result = $this->persist_additional_fields( $post_id, $request );

				if ( is_wp_error( $crew_result ) ) {
					return $crew_result;
				}

				$updated_fields[] = 'crew';
			} else {
				$preserved_fields[] = 'crew';
			}
		}

		$updated_fields   = array_values( array_unique( $updated_fields ) );
		$preserved_fields = array_values( array_unique( $preserved_fields ) );

		return new WP_REST_Response(
			array(
				'code'    => 'movie_merged',
				'message' => __( 'Movie updated with new information', 'rt-movie-library' ),
				'data'    => array(
					'movie_id'         => $post_id,
					'action'           => 'merged',
					'updated_fields'   => $updated_fields,
					'preserved_fields' => $preserved_fields,
					'movie'            => $this->prepare_item_data( get_post( $post_id ) ),
				),
			),
			200
		);
	}

	/**
	 * Merge taxonomy fields by appending new terms to existing ones.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request.
	 * @return array<int, string>
	 */
	private function merge_taxonomy_fields( int $post_id, WP_REST_Request $request ): array {
		$updated = array();

		$incoming = array();

		foreach ( $this->taxonomy_field_map as $request_key => $taxonomy ) {
			if ( ! $request->has_param( $request_key ) ) {
				continue;
			}

			$term_refs = $request->get_param( $request_key );
			if ( is_array( $term_refs ) && ! empty( $term_refs ) ) {
				$incoming[ $taxonomy ] = isset( $incoming[ $taxonomy ] ) && is_array( $incoming[ $taxonomy ] )
					? array_merge( $incoming[ $taxonomy ], $term_refs )
					: $term_refs;
			}
		}

		$tax_payload = $request->get_param( 'taxonomies' );
		if ( is_array( $tax_payload ) ) {
			foreach ( $tax_payload as $taxonomy => $term_refs ) {
				if ( ! is_string( $taxonomy ) || ! in_array( $taxonomy, $this->allowed_taxonomies, true ) || ! is_array( $term_refs ) || empty( $term_refs ) ) {
					continue;
				}

				$incoming[ $taxonomy ] = isset( $incoming[ $taxonomy ] ) && is_array( $incoming[ $taxonomy ] )
					? array_merge( $incoming[ $taxonomy ], $term_refs )
					: $term_refs;
			}
		}

		foreach ( $incoming as $taxonomy => $term_refs ) {
			if ( ! is_string( $taxonomy ) || ! is_array( $term_refs ) || empty( $term_refs ) ) {
				continue;
			}

			$new_ids = Cpt_Helper::resolve_term_ids_for_taxonomy( $taxonomy, $term_refs );
			if ( empty( $new_ids ) ) {
				continue;
			}

			$current_ids = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			$current_ids = is_wp_error( $current_ids ) || ! is_array( $current_ids ) ? array() : array_map( 'absint', $current_ids );

			$merged_ids = array_values( array_unique( array_filter( array_merge( $current_ids, $new_ids ) ) ) );
			wp_set_object_terms( $post_id, $merged_ids, $taxonomy, false );

			$updated[] = $taxonomy;
		}

		return $updated;
	}

	/**
	 * Determine whether incoming value is meaningful for merge updates.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private function has_meaningful_value( $value ): bool {
		if ( null === $value ) {
			return false;
		}

		if ( is_string( $value ) ) {
			return '' !== trim( $value );
		}

		if ( is_array( $value ) ) {
			return ! empty( $value );
		}

		return true;
	}
}
