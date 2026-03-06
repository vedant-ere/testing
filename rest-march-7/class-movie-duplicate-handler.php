<?php
/**
 * Movie duplicate detection and merge-candidate selection.
 *
 * Extracted from Movie_Controller to keep each class focused on a single
 * responsibility. This class owns all duplicate / near-duplicate logic:
 * finding candidates by title, comparing fields, picking the best merge
 * target, and normalising values for comparison.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Rest;

use WP_Post;
use WP_Query;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Duplicate_Handler
 */
class Movie_Duplicate_Handler {

	/**
	 * Find movies whose normalised title matches the given string.
	 *
	 * @param string $normalized_title Normalised title to match against.
	 * @return array<int, WP_Post>
	 */
	public function find_by_normalized_title( string $normalized_title ): array {
		$query = new WP_Query(
			array(
				'post_type'      => 'rt-movie',
				'post_status'    => Cpt_Helper::get_public_post_statuses(),
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
	 * Build a duplicate-detection signature from the incoming request.
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 * @return array<string, mixed>
	 */
	public function build_incoming_signature( WP_REST_Request $request ): array {
		$meta = $request->get_param( 'meta' );
		$meta = is_array( $meta ) ? $meta : array();

		$release_date = $request->has_param( 'release_date' )
			? $request->get_param( 'release_date' )
			: ( $meta['rt-movie-meta-basic-release-date'] ?? null );

		$runtime = $request->has_param( 'runtime' )
			? $request->get_param( 'runtime' )
			: ( $meta['rt-movie-meta-basic-runtime'] ?? null );

		$content = $request->has_param( 'content_rating' )
			? $request->get_param( 'content_rating' )
			: ( $meta['rt-movie-meta-basic-content-rating'] ?? null );

		return array(
			'title'          => $this->normalize_title( (string) $request->get_param( 'title' ) ),
			'release_date'   => $this->normalize_date( $release_date ),
			'runtime'        => $this->normalize_runtime( $runtime ),
			'content_rating' => $this->normalize_content_rating( $content ),
			'directors'      => $this->get_incoming_director_names( $request ),
		);
	}

	/**
	 * Check whether a candidate movie is an exact duplicate of the request.
	 *
	 * Only fields present in the incoming request are compared.
	 *
	 * @param int             $post_id   Candidate movie ID.
	 * @param WP_REST_Request $request   Incoming request payload.
	 * @param Movie_Controller $controller Controller (needed for taxonomy/meta resolution helpers).
	 * @return bool
	 */
	public function is_exact_duplicate( int $post_id, WP_REST_Request $request, Movie_Controller $controller ): bool {
		$has_any_comparison = false;

		if ( ! $this->is_core_post_fields_identical( $post_id, $request, $has_any_comparison ) ) {
			return false;
		}

		if ( ! $this->is_core_meta_fields_identical( $post_id, $request, $has_any_comparison, $controller ) ) {
			return false;
		}

		if ( ! $this->is_taxonomy_payload_identical( $post_id, $request, $has_any_comparison, $controller ) ) {
			return false;
		}

		if ( $request->has_param( 'crew' ) ) {
			$has_any_comparison = true;

			if ( ! $this->is_crew_payload_identical( $post_id, $request->get_param( 'crew' ), $controller ) ) {
				return false;
			}
		}

		return $has_any_comparison;
	}

	/**
	 * Pick the best merge candidate by matching score.
	 *
	 * @param array<int, WP_Post>  $candidates      Candidate posts.
	 * @param array<string, mixed> $incoming_sig     Incoming signature from build_incoming_signature().
	 * @return WP_Post|null
	 */
	public function pick_best_candidate( array $candidates, array $incoming_sig ): ?WP_Post {
		$best       = null;
		$best_score = -1;

		foreach ( $candidates as $candidate ) {
			$score = 0;

			if ( $this->normalize_title( (string) $candidate->post_title ) === (string) $incoming_sig['title'] ) {
				++$score;
			}

			$release = $this->normalize_date( get_post_meta( $candidate->ID, 'rt-movie-meta-basic-release-date', true ) );
			if ( ! empty( $incoming_sig['release_date'] ) && $release === $incoming_sig['release_date'] ) {
				++$score;
			}

			$runtime = $this->normalize_runtime( get_post_meta( $candidate->ID, 'rt-movie-meta-basic-runtime', true ) );
			if ( ! empty( $incoming_sig['runtime'] ) && $runtime === $incoming_sig['runtime'] ) {
				++$score;
			}

			$content_rating = $this->normalize_content_rating( get_post_meta( $candidate->ID, 'rt-movie-meta-basic-content-rating', true ) );
			if ( ! empty( $incoming_sig['content_rating'] ) && $content_rating === $incoming_sig['content_rating'] ) {
				++$score;
			}

			$directors = $this->get_existing_director_names( $candidate->ID );
			if ( ! empty( $incoming_sig['directors'] ) && $directors === $incoming_sig['directors'] ) {
				++$score;
			}

			if ( $score > $best_score ) {
				$best_score = $score;
				$best       = $candidate;
			}
		}

		return $best;
	}

	// -------------------------------------------------------------------------
	// Normalisation helpers (public so Movie_Controller can reuse them)
	// -------------------------------------------------------------------------

	/**
	 * Normalise a title for duplicate matching.
	 *
	 * @param string $title Raw title.
	 * @return string
	 */
	public function normalize_title( string $title ): string {
		$title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );
		$title = preg_replace( '/\s+/', ' ', trim( strtolower( $title ) ) );

		return is_string( $title ) ? $title : '';
	}

	/**
	 * Normalise runtime to integer minutes.
	 *
	 * @param mixed $runtime Runtime value.
	 * @return int|null
	 */
	public function normalize_runtime( $runtime ): ?int {
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
	 * Normalise a date value to Y-m-d.
	 *
	 * @param mixed $date Date value.
	 * @return string|null
	 */
	public function normalize_date( $date ): ?string {
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
	 * Normalise content rating for comparison.
	 *
	 * @param mixed $rating Rating value.
	 * @return string|null
	 */
	public function normalize_content_rating( $rating ): ?string {
		if ( null === $rating || '' === $rating ) {
			return null;
		}

		$rating = strtoupper( trim( (string) $rating ) );

		return '' !== $rating ? $rating : null;
	}

	// -------------------------------------------------------------------------
	// Private comparison helpers
	// -------------------------------------------------------------------------

	/**
	 * Compare incoming core post fields against stored values.
	 *
	 * @param int             $post_id            Post ID.
	 * @param WP_REST_Request $request            Request.
	 * @param bool            $has_any_comparison Running flag (passed by reference).
	 * @return bool
	 */
	private function is_core_post_fields_identical( int $post_id, WP_REST_Request $request, bool &$has_any_comparison ): bool {
		if ( $request->has_param( 'title' ) ) {
			$has_any_comparison = true;
			if ( $this->normalize_title( (string) $request->get_param( 'title' ) ) !== $this->normalize_title( (string) get_the_title( $post_id ) ) ) {
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

		return true;
	}

	/**
	 * Compare incoming core / meta payload fields against stored values.
	 *
	 * @param int              $post_id            Post ID.
	 * @param WP_REST_Request  $request            Request.
	 * @param bool             $has_any_comparison Running flag (passed by reference).
	 * @param Movie_Controller $controller         Controller (for resolve_meta_key_from_input_key).
	 * @return bool
	 */
	private function is_core_meta_fields_identical( int $post_id, WP_REST_Request $request, bool &$has_any_comparison, Movie_Controller $controller ): bool {
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

			if ( ! $this->is_meta_value_identical( $post_id, $meta_key, $request->get_param( $request_key ) ) ) {
				return false;
			}
		}

		$meta_payload = $request->get_param( 'meta' );
		if ( ! is_array( $meta_payload ) ) {
			return true;
		}

		foreach ( $meta_payload as $key => $value ) {
			if ( ! is_string( $key ) ) {
				continue;
			}

			$meta_key = $controller->resolve_meta_key( $key );
			if ( null === $meta_key ) {
				continue;
			}

			$has_any_comparison = true;

			if ( ! $this->is_meta_value_identical( $post_id, $meta_key, $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Compare one meta value against the stored value using type-aware normalisation.
	 *
	 * @param int    $post_id  Post ID.
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
			$in_ids = Cpt_Helper::resolve_image_attachment_ids( $incoming );
			$ex_ids = Cpt_Helper::resolve_image_attachment_ids( $existing );
			sort( $in_ids );
			sort( $ex_ids );
			return $in_ids === $ex_ids;
		}

		return (string) $incoming === (string) $existing;
	}

	/**
	 * Compare taxonomy payload against existing terms.
	 *
	 * @param int              $post_id            Post ID.
	 * @param WP_REST_Request  $request            Request.
	 * @param bool             $has_any_comparison Running flag (passed by reference).
	 * @param Movie_Controller $controller         Controller (for taxonomy_field_map access).
	 * @return bool
	 */
	private function is_taxonomy_payload_identical( int $post_id, WP_REST_Request $request, bool &$has_any_comparison, Movie_Controller $controller ): bool {
		foreach ( $controller->get_taxonomy_field_map() as $request_key => $taxonomy ) {
			if ( ! $request->has_param( $request_key ) ) {
				continue;
			}

			$term_refs    = $request->get_param( $request_key );
			$term_refs    = is_array( $term_refs ) ? $term_refs : array();
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

		$allowed = $controller->get_allowed_taxonomies();

		foreach ( $tax_payload as $taxonomy => $term_refs ) {
			if ( ! is_string( $taxonomy ) || ! in_array( $taxonomy, $allowed, true ) ) {
				continue;
			}

			$term_refs    = is_array( $term_refs ) ? $term_refs : array( $term_refs );
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
	 * @param int              $post_id      Movie post ID.
	 * @param mixed            $crew_payload Crew payload from request.
	 * @param Movie_Controller $controller   Controller (for resolve_attachment_ids).
	 * @return bool
	 */
	private function is_crew_payload_identical( int $post_id, $crew_payload, Movie_Controller $controller ): bool {
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

		foreach ( $incoming_grouped as &$ids ) {
			$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );
			sort( $ids );
		}
		unset( $ids );

		$existing_grouped = array(
			'director' => $controller->resolve_attachment_ids_public( get_post_meta( $post_id, 'rt-movie-meta-crew-director', true ) ),
			'producer' => $controller->resolve_attachment_ids_public( get_post_meta( $post_id, 'rt-movie-meta-crew-producer', true ) ),
			'writer'   => $controller->resolve_attachment_ids_public( get_post_meta( $post_id, 'rt-movie-meta-crew-writer', true ) ),
			'actor'    => $controller->resolve_attachment_ids_public( get_post_meta( $post_id, 'rt-movie-meta-crew-actor', true ) ),
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
		foreach ( $existing_chars as $pid => $character ) {
			$pid       = absint( $pid );
			$character = sanitize_text_field( (string) $character );
			if ( $pid > 0 && '' !== $character ) {
				$normalized_existing[ $pid ] = $character;
			}
		}

		ksort( $incoming_characters );
		ksort( $normalized_existing );

		return $incoming_characters === $normalized_existing;
	}

	/**
	 * Resolve director names from incoming crew param.
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
	 * Resolve director names from an existing movie's stored crew meta.
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
}