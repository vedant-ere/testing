<?php
/**
 * Movie crew persistence and response handler.
 *
 * Extracted from Movie_Controller to keep each class focused on a single
 * responsibility. This class owns everything related to crew: writing crew
 * meta, syncing the shadow taxonomy, and building the crew portion of the
 * REST response payload.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Rest;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Crew_Handler
 */
class Movie_Crew_Handler {

	/**
	 * Role aliases normalised to canonical role names.
	 *
	 * @var array<string, string>
	 */
	private const ROLE_MAP = array(
		'director' => 'director',
		'producer' => 'producer',
		'writer'   => 'writer',
		'actor'    => 'actor',
		'star'     => 'actor',
	);

	/**
	 * Persist crew assignments from the REST request onto the given movie post.
	 *
	 * @param int             $post_id Movie post ID.
	 * @param WP_REST_Request $request REST request carrying a 'crew' param.
	 * @return true|WP_Error
	 */
	public function persist( int $post_id, WP_REST_Request $request ) {
		if ( ! $request->has_param( 'crew' ) ) {
			return true;
		}

		$crew_payload = $request->get_param( 'crew' );

		if ( ! is_array( $crew_payload ) ) {
			return new WP_Error(
				'rt_rest_invalid_crew',
				__( 'Crew must be an array.', 'rt-movie-library' ),
				array( 'status' => 400 )
			);
		}

		$grouped    = array(
			'director' => array(),
			'producer' => array(),
			'writer'   => array(),
			'actor'    => array(),
		);
		$characters = array();

		foreach ( $crew_payload as $index => $entry ) {
			if ( ! is_array( $entry ) ) {
				return new WP_Error(
					'rt_rest_invalid_crew',
					/* translators: %d: crew array index. */
					sprintf( __( 'Crew item at index %d is invalid.', 'rt-movie-library' ), (int) $index ),
					array( 'status' => 400 )
				);
			}

			$raw_role = isset( $entry['role'] ) ? sanitize_key( (string) $entry['role'] ) : '';
			$role     = self::ROLE_MAP[ $raw_role ] ?? '';

			if ( '' === $role ) {
				return new WP_Error(
					'rt_rest_invalid_crew_role',
					/* translators: %d: crew array index. */
					sprintf( __( 'Crew role is invalid at index %d.', 'rt-movie-library' ), (int) $index ),
					array( 'status' => 400 )
				);
			}

			if ( ! array_key_exists( 'person', $entry ) ) {
				return new WP_Error(
					'rt_rest_invalid_crew_person',
					/* translators: %d: crew array index. */
					sprintf( __( 'Crew person is missing at index %d.', 'rt-movie-library' ), (int) $index ),
					array( 'status' => 400 )
				);
			}

			$person_id = Cpt_Helper::find_or_create_person_reference( $entry['person'], $role );

			if ( $person_id <= 0 ) {
				return new WP_Error(
					'rt_rest_invalid_crew_person',
					/* translators: %d: crew array index. */
					sprintf( __( 'Crew person could not be resolved at index %d.', 'rt-movie-library' ), (int) $index ),
					array( 'status' => 400 )
				);
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
		foreach ( $grouped as $role => &$ids ) {
			$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );
		}
		unset( $ids );

		$this->upsert_json_array_meta( $post_id, 'rt-movie-meta-crew-director', $grouped['director'] );
		$this->upsert_json_array_meta( $post_id, 'rt-movie-meta-crew-producer', $grouped['producer'] );
		$this->upsert_json_array_meta( $post_id, 'rt-movie-meta-crew-writer', $grouped['writer'] );
		$this->upsert_json_array_meta( $post_id, 'rt-movie-meta-crew-actor', $grouped['actor'] );
		$this->upsert_json_object_meta( $post_id, 'rt-movie-meta-crew-actor-characters', $characters, $grouped['actor'] );
		$this->sync_shadow_taxonomy( $post_id, $grouped );

		return true;
	}

	/**
	 * Build the crew portion of the REST response payload for a movie.
	 *
	 * All person posts are fetched in a single get_posts() call to avoid
	 * N+1 queries.
	 *
	 * @param int $post_id Movie post ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function build_response_payload( int $post_id ): array {
		$crew_meta_keys = array(
			'director' => 'rt-movie-meta-crew-director',
			'producer' => 'rt-movie-meta-crew-producer',
			'writer'   => 'rt-movie-meta-crew-writer',
			'actor'    => 'rt-movie-meta-crew-actor',
		);

		$characters_json = get_post_meta( $post_id, 'rt-movie-meta-crew-actor-characters', true );
		$characters      = is_string( $characters_json ) ? json_decode( $characters_json, true ) : array();
		$characters      = is_array( $characters ) ? $characters : array();

		// Decode all role meta into role => IDs map and collect unique person IDs.
		$role_ids = array();

		foreach ( $crew_meta_keys as $role => $meta_key ) {
			$raw  = get_post_meta( $post_id, $meta_key, true );
			$ids  = is_string( $raw ) ? json_decode( $raw, true ) : array();
			$ids  = is_array( $ids ) ? array_values( array_filter( array_map( 'absint', $ids ) ) ) : array();

			$role_ids[ $role ] = $ids;
		}

		$all_person_ids = array_values(
			array_unique(
				array_merge( ...array_values( $role_ids ) )
			)
		);

		// Single batch query — replaces one get_post() call per crew member.
		$person_map = array();

		if ( ! empty( $all_person_ids ) ) {
			$persons = get_posts(
				array(
					'post_type'              => 'rt-person',
					'post__in'               => $all_person_ids,
					'posts_per_page'         => count( $all_person_ids ),
					'post_status'            => 'any',
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
				)
			);

			foreach ( $persons as $person ) {
				$person_map[ (int) $person->ID ] = $person;
			}
		}

		// Assemble the crew array from the in-memory map.
		$crew = array();

		foreach ( $role_ids as $role => $ids ) {
			foreach ( $ids as $person_id ) {
				if ( ! isset( $person_map[ $person_id ] ) ) {
					continue;
				}

				$person = $person_map[ $person_id ];
				$entry  = array(
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

		return $crew;
	}

	/**
	 * Sync the internal movie-person shadow taxonomy from crew assignments.
	 *
	 * @param int                  $post_id Movie post ID.
	 * @param array<string, int[]> $grouped Crew grouped by role.
	 * @return void
	 */
	private function sync_shadow_taxonomy( int $post_id, array $grouped ): void {
		$all_people = array_unique(
			array_merge(
				$grouped['director'] ?? array(),
				$grouped['producer'] ?? array(),
				$grouped['writer']   ?? array(),
				$grouped['actor']    ?? array()
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
	 * Upsert a JSON-encoded array meta value, or delete when empty.
	 *
	 * @param int            $post_id  Movie post ID.
	 * @param string         $meta_key Meta key.
	 * @param array<int,int> $values   Values to encode.
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
	 * Upsert a JSON-encoded object meta value for actor-character mappings.
	 *
	 * @param int                     $post_id  Movie post ID.
	 * @param string                  $meta_key Meta key.
	 * @param array<int|string,mixed> $values   Character map.
	 * @param array<int,int>          $actors   Actor IDs used for validation.
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
}