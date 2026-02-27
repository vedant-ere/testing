<?php
/**
 * REST helper utilities.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Rest;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class Cpt_Helper
 */
class Cpt_Helper {

	/**
	 * Validate numeric route ID.
	 *
	 * @param mixed           $value   Route value.
	 * @param WP_REST_Request $request Full request.
	 * @param string          $param   Parameter name.
	 * @return bool|WP_Error
	 */
	public static function validate_id( $value, WP_REST_Request $request, string $param ) {
		unset( $request, $param );

		if ( absint( $value ) > 0 ) {
			return true;
		}

		return new WP_Error(
			'rt_rest_invalid_id',
			__( 'A valid numeric post ID is required.', 'rt-movie-library' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Validate slug parameter.
	 *
	 * @param mixed           $value   Slug value.
	 * @param WP_REST_Request $request Full request.
	 * @param string          $param   Parameter name.
	 * @return bool|WP_Error
	 */
	public static function validate_slug( $value, WP_REST_Request $request, string $param ) {
		unset( $request, $param );

		$slug = sanitize_title( (string) $value );

		if ( '' !== $slug ) {
			return true;
		}

		return new WP_Error(
			'rt_rest_invalid_slug',
			__( 'A valid slug is required.', 'rt-movie-library' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Validate person social URL by request field.
	 *
	 * @param mixed           $value   URL value.
	 * @param WP_REST_Request $request Full request.
	 * @param string          $param   Parameter name.
	 * @return bool|WP_Error
	 */
	public static function validate_person_social_url( $value, WP_REST_Request $request, string $param ) {
		unset( $request );

		if ( '' === $value || null === $value ) {
			return true;
		}

		$url = esc_url_raw( (string) $value );

		if ( '' === $url ) {
			return new WP_Error(
				'rt_rest_invalid_social_url',
				__( 'Social URL is invalid.', 'rt-movie-library' ),
				array( 'status' => 400 )
			);
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		$host = is_string( $host ) ? strtolower( $host ) : '';
		$host = preg_replace( '/^www\./', '', $host );

		if ( ! is_string( $host ) || '' === $host ) {
			return new WP_Error(
				'rt_rest_invalid_social_url',
				__( 'Social URL host is invalid.', 'rt-movie-library' ),
				array( 'status' => 400 )
			);
		}

		$allowed_domains_map = array(
			'twitter'   => array( 'twitter.com', 'x.com' ),
			'facebook'  => array( 'facebook.com' ),
			'instagram' => array( 'instagram.com' ),
			'website'   => array(),
		);

		if ( ! isset( $allowed_domains_map[ $param ] ) ) {
			return new WP_Error(
				'rt_rest_invalid_social_field',
				__( 'Unsupported social field.', 'rt-movie-library' ),
				array( 'status' => 400 )
			);
		}

		$allowed_domains = $allowed_domains_map[ $param ];

		if ( empty( $allowed_domains ) ) {
			return true;
		}

		foreach ( $allowed_domains as $domain ) {
			$subdomain_suffix = '.' . $domain;
			$suffix_length    = strlen( $subdomain_suffix );

			if (
				$host === $domain ||
				( strlen( $host ) > $suffix_length && substr( $host, -$suffix_length ) === $subdomain_suffix )
			) {
				return true;
			}
		}

		return new WP_Error(
			'rt_rest_invalid_social_domain',
			__( 'Social URL does not match allowed domain for this field.', 'rt-movie-library' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Validate date value in Y-m-d format.
	 *
	 * @param mixed           $value   Parameter value.
	 * @param WP_REST_Request $request Full request.
	 * @param string          $param   Parameter name.
	 * @return bool|WP_Error
	 */
	public static function validate_date( $value, WP_REST_Request $request, string $param ) {
		unset( $request, $param );

		if ( '' === $value || null === $value ) {
			return true;
		}

		$date = \DateTimeImmutable::createFromFormat( 'Y-m-d', (string) $value );

		if ( $date instanceof \DateTimeImmutable && $date->format( 'Y-m-d' ) === $value ) {
			return true;
		}

		return new WP_Error(
			'rt_rest_invalid_date',
			__( 'Date must use Y-m-d format.', 'rt-movie-library' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Validate featured media ID is an attachment or zero.
	 *
	 * @param mixed           $value   Parameter value.
	 * @param WP_REST_Request $request Full request.
	 * @param string          $param   Parameter name.
	 * @return bool|WP_Error
	 */
	public static function validate_featured_media( $value, WP_REST_Request $request, string $param ) {
		unset( $request, $param );

		$media_id = absint( $value );

		if ( 0 === $media_id ) {
			return true;
		}

		if ( 'attachment' === get_post_type( $media_id ) ) {
			return true;
		}

		return new WP_Error(
			'rt_rest_invalid_featured_media',
			__( 'Featured media must be a valid attachment ID.', 'rt-movie-library' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Sanitizes status list for collections.
	 *
	 * @param mixed $value Status value.
	 * @return array<int, string>
	 */
	public static function sanitize_status_list( $value ): array {
		$allowed = array( 'publish', 'draft', 'pending', 'private', 'future' );

		if ( ! is_array( $value ) ) {
			return array( 'publish' );
		}

		$statuses = array_map( 'sanitize_key', $value );
		$statuses = array_values( array_unique( array_intersect( $statuses, $allowed ) ) );

		if ( empty( $statuses ) ) {
			return array( 'publish' );
		}

		return $statuses;
	}

	/**
	 * Sanitize rating with one decimal precision.
	 *
	 * @param mixed $value Raw rating value.
	 * @return float
	 */
	public static function sanitize_rating( $value ): float {
		return (float) number_format( (float) $value, 1, '.', '' );
	}

	/**
	 * Sanitizes a list of term IDs.
	 *
	 * @param mixed $value Input values.
	 * @return array<int, int>
	 */
	public static function sanitize_term_ids( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$term_ids = array_map( 'absint', $value );
		$term_ids = array_filter(
			$term_ids,
			static function ( int $term_id ): bool {
				return $term_id > 0;
			}
		);

		return array_values( array_unique( $term_ids ) );
	}

	/**
	 * Validates provided term IDs belong to the expected taxonomy.
	 *
	 * @param mixed           $value    Parameter value.
	 * @param WP_REST_Request $request  Full request.
	 * @param string          $param    Parameter name.
	 * @param string          $taxonomy Expected taxonomy.
	 * @return bool|WP_Error
	 */
	public static function validate_term_ids_for_taxonomy( $value, WP_REST_Request $request, string $param, string $taxonomy ) {
		unset( $request, $param );

		if ( ! is_array( $value ) ) {
			return new WP_Error(
				'rt_rest_invalid_terms',
				__( 'Taxonomy terms must be passed as an array of term IDs.', 'rt-movie-library' ),
				array( 'status' => 400 )
			);
		}

		foreach ( self::sanitize_term_ids( $value ) as $term_id ) {
			$term = get_term( $term_id );

			if ( ! $term || is_wp_error( $term ) || $taxonomy !== $term->taxonomy ) {
				return new WP_Error(
					'rt_rest_invalid_terms',
					__( 'One or more taxonomy terms are invalid for this field.', 'rt-movie-library' ),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Convert meta value to integer or null.
	 *
	 * @param mixed $value Meta value.
	 * @return int|null
	 */
	public static function meta_to_int_or_null( $value ): ?int {
		if ( '' === $value || null === $value ) {
			return null;
		}

		return (int) $value;
	}

	/**
	 * Convert meta value to string or null.
	 *
	 * @param mixed $value Meta value.
	 * @return string|null
	 */
	public static function meta_to_string_or_null( $value ): ?string {
		if ( '' === $value || null === $value ) {
			return null;
		}

		return (string) $value;
	}

	/**
	 * Resolve term references to term IDs for a taxonomy.
	 *
	 * Accepts IDs, slugs, and names. Missing string terms are created.
	 *
	 * @param string            $taxonomy  Taxonomy name.
	 * @param array<int, mixed> $term_refs Term references.
	 * @return array<int, int>
	 */
	public static function resolve_term_ids_for_taxonomy( string $taxonomy, array $term_refs ): array {
		$term_ids = array();

		foreach ( $term_refs as $term_ref ) {
			if ( is_numeric( $term_ref ) ) {
				$term_id = absint( $term_ref );
				$term    = get_term( $term_id, $taxonomy );

				if ( $term && ! is_wp_error( $term ) ) {
					$term_ids[] = (int) $term_id;
				}

				continue;
			}

			if ( ! is_string( $term_ref ) ) {
				continue;
			}

			$raw = trim( $term_ref );

			if ( '' === $raw ) {
				continue;
			}

			$by_slug = get_term_by( 'slug', sanitize_title( $raw ), $taxonomy );

			if ( $by_slug && ! is_wp_error( $by_slug ) ) {
				$term_ids[] = (int) $by_slug->term_id;
				continue;
			}

			$by_name = get_term_by( 'name', $raw, $taxonomy );

			if ( $by_name && ! is_wp_error( $by_name ) ) {
				$term_ids[] = (int) $by_name->term_id;
				continue;
			}

			$inserted = wp_insert_term( $raw, $taxonomy );

			if ( ! is_wp_error( $inserted ) && isset( $inserted['term_id'] ) ) {
				$term_ids[] = (int) $inserted['term_id'];
			}
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $term_ids ) ) ) );
	}

	/**
	 * Resolves a person reference to a valid rt-person post ID.
	 *
	 * Supports ID, slug, and post title.
	 *
	 * @param mixed $person_ref Person reference.
	 * @return int
	 */
	public static function resolve_person_reference( $person_ref ): int {
		if ( is_numeric( $person_ref ) ) {
			$person_id = absint( $person_ref );
			$post      = get_post( $person_id );

			if ( $post && 'rt-person' === $post->post_type ) {
				return $person_id;
			}

			return 0;
		}

		if ( ! is_string( $person_ref ) ) {
			return 0;
		}

		$raw = trim( $person_ref );

		if ( '' === $raw ) {
			return 0;
		}

		$slug = sanitize_title( $raw );

		$base_query_args = array(
			'post_type'              => 'rt-person',
			'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		$by_slug_query = new \WP_Query(
			array_merge(
				$base_query_args,
				array(
					'name' => $slug,
				)
			)
		);
		$by_slug       = $by_slug_query->posts;

		if ( ! empty( $by_slug ) ) {
			return (int) $by_slug[0];
		}

		$by_title_query = new \WP_Query(
			array_merge(
				$base_query_args,
				array(
					'title' => $raw,
				)
			)
		);
		$by_title       = $by_title_query->posts;

		if ( ! empty( $by_title ) ) {
			return (int) $by_title[0];
		}

		$fallback_query = new \WP_Query(
			array_merge(
				$base_query_args,
				array(
					's'       => $raw,
					'orderby' => 'relevance',
				)
			)
		);
		$fallback       = $fallback_query->posts;

		return ! empty( $fallback ) ? (int) $fallback[0] : 0;
	}

	/**
	 * Resolve or create a person reference.
	 *
	 * Supports ID, slug, and post title. If not found, creates a new rt-person.
	 *
	 * @param mixed  $person_ref Person reference.
	 * @param string $role       Optional role for career assignment.
	 * @return int
	 */
	public static function find_or_create_person_reference( $person_ref, string $role = '' ): int {
		$resolved = self::resolve_person_reference( $person_ref );
		if ( $resolved > 0 ) {
			self::assign_person_career_by_role( $resolved, $role );
			return $resolved;
		}

		$title = '';

		if ( is_string( $person_ref ) ) {
			$raw = trim( $person_ref );

			if ( '' !== $raw ) {
				// If a slug-like value is passed, convert to a human-readable title.
				$title = sanitize_text_field( ucwords( str_replace( array( '-', '_' ), ' ', $raw ) ) );
			}
		}

		if ( '' === $title ) {
			return 0;
		}

		$person_id = wp_insert_post(
			array(
				'post_type'   => 'rt-person',
				'post_status' => 'publish',
				'post_title'  => $title,
			),
			true
		);

		if ( is_wp_error( $person_id ) ) {
			return 0;
		}

		$person_id = (int) $person_id;
		self::assign_person_career_by_role( $person_id, $role );

		return $person_id;
	}

	/**
	 * Assign person career based on crew role.
	 *
	 * @param int    $person_id Person post ID.
	 * @param string $role      Crew role.
	 * @return void
	 */
	private static function assign_person_career_by_role( int $person_id, string $role ): void {
		$role = sanitize_key( $role );

		if ( 'star' === $role ) {
			$role = 'actor';
		}

		$allowed = array( 'director', 'producer', 'writer', 'actor' );
		if ( ! in_array( $role, $allowed, true ) ) {
			return;
		}

		$term = get_term_by( 'slug', $role, 'rt-person-career' );

		if ( ! $term || is_wp_error( $term ) ) {
			$inserted = wp_insert_term(
				ucfirst( $role ),
				'rt-person-career',
				array(
					'slug' => $role,
				)
			);

			if ( is_wp_error( $inserted ) || empty( $inserted['term_id'] ) ) {
				return;
			}

			$term_id = (int) $inserted['term_id'];
		} else {
			$term_id = (int) $term->term_id;
		}

		wp_set_object_terms( $person_id, array( $term_id ), 'rt-person-career', true );
	}

	/**
	 * Resolve mixed image refs (attachment IDs or URLs) into valid image attachment IDs.
	 *
	 * @param mixed $image_refs Input refs.
	 * @return array<int, int>
	 */
	public static function resolve_image_attachment_ids( $image_refs ): array {
		$refs = array();

		if ( is_array( $image_refs ) ) {
			$refs = $image_refs;
		} elseif ( is_string( $image_refs ) ) {
			$decoded = json_decode( $image_refs, true );

			if ( is_array( $decoded ) ) {
				$refs = $decoded;
			} elseif ( '' !== trim( $image_refs ) ) {
				$refs = array( $image_refs );
			}
		} elseif ( is_numeric( $image_refs ) ) {
			$refs = array( $image_refs );
		}

		$image_ids = array();

		foreach ( $refs as $ref ) {
			$attachment_id = 0;

			if ( is_numeric( $ref ) ) {
				$attachment_id = absint( $ref );
			} elseif ( is_string( $ref ) ) {
				$url = esc_url_raw( trim( $ref ) );

				if ( '' !== $url ) {
					// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid -- Required to map URLs in API payloads back to local attachment IDs.
					$attachment_id = absint( attachment_url_to_postid( $url ) );
				}
			}

			if ( $attachment_id <= 0 ) {
				continue;
			}

			if ( 'attachment' !== get_post_type( $attachment_id ) ) {
				continue;
			}

			$mime = get_post_mime_type( $attachment_id );

			if ( ! is_string( $mime ) || strpos( $mime, 'image/' ) !== 0 ) {
				continue;
			}

			$image_ids[] = $attachment_id;
		}

		return array_values( array_unique( $image_ids ) );
	}

	/**
	 * Convert image attachment IDs to URLs.
	 *
	 * @param array<int, int> $image_ids Attachment IDs.
	 * @return array<int, string>
	 */
	public static function image_ids_to_urls( array $image_ids ): array {
		$urls = array();

		foreach ( $image_ids as $image_id ) {
			$url = wp_get_attachment_url( absint( $image_id ) );

			if ( is_string( $url ) && '' !== $url ) {
				$urls[] = $url;
			}
		}

		return array_values( array_unique( $urls ) );
	}
}
