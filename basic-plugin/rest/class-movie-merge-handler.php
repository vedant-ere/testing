<?php
/**
 * Movie merge logic.
 *
 * Handles the smart-merge flow when a near-duplicate movie is detected:
 * updates only the fields that have changed and preserves the rest.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Merge_Handler
 */
class Movie_Merge_Handler {

	/**
	 * Merge the incoming request payload into an existing movie post.
	 *
	 * Operates in PATCH-style semantics: only non-empty supplied fields are
	 * applied; everything else is left untouched.
	 *
	 * @param int              $post_id    Existing movie post ID.
	 * @param WP_REST_Request  $request    Incoming REST request.
	 * @param Movie_Controller $controller Controller instance (provides helpers).
	 * @return WP_REST_Response|WP_Error
	 */
	public function merge( int $post_id, WP_REST_Request $request, Movie_Controller $controller ) {
		$updated_fields   = array();
		$preserved_fields = array();

		// ---- Core post fields ------------------------------------------------
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

		// ---- Meta fields -----------------------------------------------------
		foreach ( $controller->get_meta_field_map() as $request_key => $meta_key ) {
			if ( ! $request->has_param( $request_key ) ) {
				continue;
			}

			$value = $request->get_param( $request_key );
			if ( ! $this->has_meaningful_value( $value ) ) {
				continue;
			}

			$validation = $controller->validate_meta( $meta_key, $value, $request );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			$controller->upsert_meta( $post_id, $meta_key, $value );
			$updated_fields[] = $request_key;
		}

		$meta_payload = $request->get_param( 'meta' );
		if ( is_array( $meta_payload ) ) {
			foreach ( $meta_payload as $key => $value ) {
				if ( ! is_string( $key ) || ! $this->has_meaningful_value( $value ) ) {
					continue;
				}

				$meta_key = $controller->resolve_meta_key( $key );
				if ( null === $meta_key ) {
					continue;
				}

				$validation = $controller->validate_meta( $meta_key, $value, $request );
				if ( is_wp_error( $validation ) ) {
					return $validation;
				}

				$controller->upsert_meta( $post_id, $meta_key, $value );
				$updated_fields[] = $key;
			}
		}

		// ---- Featured media --------------------------------------------------
		if ( $request->has_param( 'featured_media' ) ) {
			$featured = absint( $request->get_param( 'featured_media' ) );
			if ( $featured > 0 ) {
				set_post_thumbnail( $post_id, $featured );
				$updated_fields[] = 'featured_media';
			} else {
				$preserved_fields[] = 'featured_media';
			}
		}

		// ---- Taxonomies ------------------------------------------------------
		$tax_updated    = $this->merge_taxonomy_fields( $post_id, $request, $controller );
		$updated_fields = array_merge( $updated_fields, $tax_updated );

		// ---- Crew ------------------------------------------------------------
		if ( $request->has_param( 'crew' ) ) {
			$crew = $request->get_param( 'crew' );
			if ( is_array( $crew ) && ! empty( $crew ) ) {
				$crew_result = $controller->persist_crew( $post_id, $request );
				if ( is_wp_error( $crew_result ) ) {
					return $crew_result;
				}
				$updated_fields[] = 'crew';
			} else {
				$preserved_fields[] = 'crew';
			}
		}

		return new WP_REST_Response(
			array(
				'code'    => 'movie_merged',
				'message' => __( 'Movie updated with new information', 'rt-movie-library' ),
				'data'    => array(
					'movie_id'         => $post_id,
					'action'           => 'merged',
					'updated_fields'   => array_values( array_unique( $updated_fields ) ),
					'preserved_fields' => array_values( array_unique( $preserved_fields ) ),
					'movie'            => $controller->prepare_item_data_public( get_post( $post_id ) ),
				),
			),
			200
		);
	}

	/**
	 * Merge taxonomy fields by appending new terms to existing ones.
	 *
	 * @param int              $post_id    Movie post ID.
	 * @param WP_REST_Request  $request    Incoming request.
	 * @param Movie_Controller $controller Controller instance.
	 * @return array<int, string> List of updated taxonomy slugs.
	 */
	private function merge_taxonomy_fields( int $post_id, WP_REST_Request $request, Movie_Controller $controller ): array {
		$updated  = array();
		$incoming = array();

		foreach ( $controller->get_taxonomy_field_map() as $request_key => $taxonomy ) {
			if ( ! $request->has_param( $request_key ) ) {
				continue;
			}

			$term_refs = $request->get_param( $request_key );
			if ( is_array( $term_refs ) && ! empty( $term_refs ) ) {
				$incoming[ $taxonomy ] = array_merge( $incoming[ $taxonomy ] ?? array(), $term_refs );
			}
		}

		$tax_payload = $request->get_param( 'taxonomies' );
		if ( is_array( $tax_payload ) ) {
			$allowed = $controller->get_allowed_taxonomies_public();

			foreach ( $tax_payload as $taxonomy => $term_refs ) {
				if ( ! is_string( $taxonomy ) || ! in_array( $taxonomy, $allowed, true ) || ! is_array( $term_refs ) || empty( $term_refs ) ) {
					continue;
				}

				$incoming[ $taxonomy ] = array_merge( $incoming[ $taxonomy ] ?? array(), $term_refs );
			}
		}

		foreach ( $incoming as $taxonomy => $term_refs ) {
			$new_ids = Cpt_Helper::resolve_term_ids_for_taxonomy( $taxonomy, $term_refs );
			if ( empty( $new_ids ) ) {
				continue;
			}

			$current_ids = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			$current_ids = is_wp_error( $current_ids ) || ! is_array( $current_ids ) ? array() : array_map( 'absint', $current_ids );
			$merged_ids  = array_values( array_unique( array_filter( array_merge( $current_ids, $new_ids ) ) ) );

			wp_set_object_terms( $post_id, $merged_ids, $taxonomy, false );

			$updated[] = $taxonomy;
		}

		return $updated;
	}

	/**
	 * Determine whether a value is meaningful enough to trigger a merge update.
	 *
	 * @param mixed $value Value to test.
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
