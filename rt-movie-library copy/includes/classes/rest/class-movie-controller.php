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
use RT_Movie_Library\Classes\Database\Meta_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Controller
 *
 * Handles movie REST routes. Heavy lifting is delegated to:
 *  - Movie_Crew_Handler      — crew persistence + response payload
 *  - Movie_Duplicate_Handler — duplicate detection + merge candidate selection
 *  - Movie_Merge_Handler     — smart-merge of near-duplicate movies
 */
class Movie_Controller extends Base_Cpt_Controller {

	/**
	 * Crew persistence + response helper.
	 *
	 * @var Movie_Crew_Handler
	 */
	private Movie_Crew_Handler $crew_handler;

	/**
	 * Duplicate detection + merge candidate selection helper.
	 *
	 * @var Movie_Duplicate_Handler
	 */
	private Movie_Duplicate_Handler $duplicate_handler;

	/**
	 * Smart-merge helper.
	 *
	 * @var Movie_Merge_Handler
	 */
	private Movie_Merge_Handler $merge_handler;

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

		$this->crew_handler      = new Movie_Crew_Handler();
		$this->duplicate_handler = new Movie_Duplicate_Handler();
		$this->merge_handler     = new Movie_Merge_Handler();
	}

	// =========================================================================
	// Public accessor methods (needed by handler classes)
	// =========================================================================

	/**
	 * Expose the meta field map to handler classes.
	 *
	 * @return array<string, string>
	 */
	public function get_meta_field_map(): array {
		return $this->meta_field_map;
	}

	/**
	 * Expose the taxonomy field map to handler classes.
	 *
	 * @return array<string, string>
	 */
	public function get_taxonomy_field_map(): array {
		return $this->taxonomy_field_map;
	}

	/**
	 * Public proxy for protected get_allowed_taxonomies() (used by handlers).
	 *
	 * @return array<int, string>
	 */
	public function get_allowed_taxonomies_public(): array {
		return $this->get_allowed_taxonomies();
	}

	/**
	 * Public proxy for resolve_meta_key_from_input_key() (needed by handlers).
	 *
	 * @param string $input_key Input key from the request.
	 * @return string|null Resolved meta key, or null if unrecognised.
	 */
	public function resolve_meta_key( string $input_key ): ?string {
		return $this->resolve_meta_key_from_input_key( $input_key );
	}

	/**
	 * Extend collection query parameters with movie-specific filters.
	 *
	 * @return array<string, mixed>
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['genreId'] = array(
			'description'       => __( 'Filter by genre term ID.', 'rt-movie-library' ),
			'type'              => 'integer',
			'minimum'           => 0,
			'sanitize_callback' => 'absint',
			'validate_callback' => array( $this, 'validate_genre_id' ),
		);

		$params['labelId'] = array(
			'description'       => __( 'Filter by label term ID.', 'rt-movie-library' ),
			'type'              => 'integer',
			'minimum'           => 0,
			'sanitize_callback' => 'absint',
			'validate_callback' => array( $this, 'validate_label_id' ),
		);

		$params['languageId'] = array(
			'description'       => __( 'Filter by language term ID.', 'rt-movie-library' ),
			'type'              => 'integer',
			'minimum'           => 0,
			'sanitize_callback' => 'absint',
			'validate_callback' => array( $this, 'validate_language_id' ),
		);

		$params['directorId'] = array(
			'description'       => __( 'Filter by director person ID (shadow taxonomy filtering).', 'rt-movie-library' ),
			'type'              => 'integer',
			'minimum'           => 0,
			'sanitize_callback' => 'absint',
			'validate_callback' => array( $this, 'validate_director_id' ),
		);

		$params['min_rating'] = array(
			'description'       => __( 'Limit results to movies with a rating greater than or equal to this value.', 'rt-movie-library' ),
			'type'              => 'number',
			'minimum'           => 1,
			'maximum'           => 10,
			'sanitize_callback' => 'floatval',
		);

		$params['max_runtime'] = array(
			'description'       => __( 'Limit results to movies with a runtime less than or equal to this value.', 'rt-movie-library' ),
			'type'              => 'integer',
			'minimum'           => 1,
			'maximum'           => 300,
			'sanitize_callback' => 'absint',
		);

		return $params;
	}

	/**
	 * Validate genre ID exists.
	 *
	 * @param int    $genre_id Genre term ID.
	 * @param object $request    Request object.
	 * @param string $param      Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_genre_id( $genre_id, $request, $param ) {
		if ( 0 === (int) $genre_id ) {
			return true; // 0 means no filter.
		}

		return term_exists( (int) $genre_id, 'rt-movie-genre' ) ? true : new WP_Error( 'invalid_genre_id', __( 'Invalid genre ID.', 'rt-movie-library' ) );
	}

	/**
	 * Validate label ID exists.
	 *
	 * @param int    $label_id Label term ID.
	 * @param object $request    Request object.
	 * @param string $param      Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_label_id( $label_id, $request, $param ) {
		if ( 0 === (int) $label_id ) {
			return true; // 0 means no filter.
		}

		return term_exists( (int) $label_id, 'rt-movie-label' ) ? true : new WP_Error( 'invalid_label_id', __( 'Invalid label ID.', 'rt-movie-library' ) );
	}

	/**
	 * Validate language ID exists.
	 *
	 * @param int    $language_id Language term ID.
	 * @param object $request      Request object.
	 * @param string $param        Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_language_id( $language_id, $request, $param ) {
		if ( 0 === (int) $language_id ) {
			return true; // 0 means no filter.
		}

		return term_exists( (int) $language_id, 'rt-movie-language' ) ? true : new WP_Error( 'invalid_language_id', __( 'Invalid language ID.', 'rt-movie-library' ) );
	}

	/**
	 * Validate director ID exists.
	 *
	 * @param int    $director_id Director person post ID.
	 * @param object $request      Request object.
	 * @param string $param        Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_director_id( $director_id, $request, $param ) {
		if ( 0 === (int) $director_id ) {
			return true; // 0 means no filter.
		}

		$post = get_post( (int) $director_id );
		return ( $post instanceof WP_Post && 'rt-person' === $post->post_type ) ? true : new WP_Error( 'invalid_director_id', __( 'Invalid director ID.', 'rt-movie-library' ) );
	}

	/**
	 * Extend collection retrieval with movie-specific taxonomy and meta filtering.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$page        = max( 1, (int) $request->get_param( 'page' ) );
		$per_page    = (int) $request->get_param( 'per_page' );
		$search      = (string) $request->get_param( 'search' );
		$statuses    = $request->get_param( 'status' );
		$genre_id    = (int) $request->get_param( 'genreId' );
		$label_id    = (int) $request->get_param( 'labelId' );
		$language_id = (int) $request->get_param( 'languageId' );
		$director_id = (int) $request->get_param( 'directorId' );
		$min_rating  = $request->get_param( 'min_rating' );
		$max_runtime = $request->get_param( 'max_runtime' );

		if ( empty( $statuses ) || ! is_array( $statuses ) ) {
			$statuses = array( Cpt_Helper::get_default_public_status() );
		}

		$post_status = ( is_user_logged_in() && current_user_can( 'edit_others_posts' ) ) ? $statuses : array( Cpt_Helper::get_default_public_status() );

		// ── High Performance Filter: if meta filtering is requested, use Meta_Repository::query_ids() ──
		$has_meta_filter = ( null !== $min_rating || null !== $max_runtime );

		if ( $has_meta_filter && empty( $search ) && empty( $genre_id ) && empty( $label_id ) && empty( $language_id ) && empty( $director_id ) ) {
			$meta_filter = array();

			if ( null !== $min_rating ) {
				$meta_filter[] = array(
					'key'     => 'rt-movie-meta-basic-rating',
					'value'   => $min_rating,
					'compare' => '>=',
				);
			}

			if ( null !== $max_runtime ) {
				$meta_filter[] = array(
					'key'     => 'rt-movie-meta-basic-runtime',
					'value'   => $max_runtime,
					'compare' => '<=',
				);
			}

			$post_ids = Meta_Repository::query_ids(
				array(
					'post_type'   => $this->post_type,
					'post_status' => ( count( $post_status ) === 1 ) ? $post_status[0] : 'any',
					'meta_filter' => $meta_filter,
					'limit'       => $per_page * $page, // Simplified pagination for the specialized query.
				)
			);

			if ( empty( $post_ids ) ) {
				return new WP_REST_Response( array(), 200 );
			}

			// Slice for the requested page.
			$paged_ids = array_slice( $post_ids, ( $page - 1 ) * $per_page, $per_page );
			$posts     = array_map( 'get_post', $paged_ids );
			$items     = array();

			foreach ( $posts as $post ) {
				if ( $post ) {
					$items[] = $this->prepare_item_data( $post );
				}
			}

			$response = new WP_REST_Response( $items, 200 );
			$response->header( 'X-WP-Total', (string) count( $post_ids ) );
			return $response;
		}

		// Fallback to standard WP_Query for mixed taxonomy/search/meta queries (or if no meta filter).
		$query_args = array(
			'post_type'              => $this->post_type,
			'post_status'            => $post_status,
			'posts_per_page'         => $per_page,
			'paged'                  => $page,
			's'                      => $search,
			'search_columns'         => array( 'post_title' ),
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		);

		// ... (rest of the taxonomy query building) ...
		// We'll keep the existing logic below but integrate meta filters if they exist.
		if ( null !== $min_rating || null !== $max_runtime ) {
			$meta_query = array( 'relation' => 'AND' );
			if ( null !== $min_rating ) {
				$meta_query[] = array(
					'key'     => 'rt-movie-meta-basic-rating',
					'value'   => $min_rating,
					'compare' => '>=',
					'type'    => 'DECIMAL(10,2)',
				);
			}
			if ( null !== $max_runtime ) {
				$meta_query[] = array(
					'key'     => 'rt-movie-meta-basic-runtime',
					'value'   => $max_runtime,
					'compare' => '<=',
					'type'    => 'NUMERIC',
				);
			}
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Fallback logic for legacy data.
			$query_args['meta_query'] = $meta_query;
		}

		// Build taxonomy query for genre, label, language.
		$tax_query = array();

		if ( $genre_id > 0 ) {
			$tax_query[] = array(
				'taxonomy' => 'rt-movie-genre',
				'field'    => 'term_id',
				'terms'    => array( $genre_id ),
			);
		}

		if ( $label_id > 0 ) {
			$tax_query[] = array(
				'taxonomy' => 'rt-movie-label',
				'field'    => 'term_id',
				'terms'    => array( $label_id ),
			);
		}

		if ( $language_id > 0 ) {
			$tax_query[] = array(
				'taxonomy' => 'rt-movie-language',
				'field'    => 'term_id',
				'terms'    => array( $language_id ),
			);
		}

		// Director filtering via shadow taxonomy.
		if ( $director_id > 0 ) {
			$shadow_slug = $this->get_director_shadow_slug( $director_id );

			if ( '' !== $shadow_slug ) {
				$tax_query[] = array(
					'taxonomy' => '_rt-movie-person',
					'field'    => 'slug',
					'terms'    => array( $shadow_slug ),
				);
			}
		}

		if ( ! empty( $tax_query ) ) {
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Standard CPT taxonomy filtering.
			$query_args['tax_query'] = $tax_query;
		}

		$query = new WP_Query( $query_args );

		// ── High Performance: eliminate N+1 by pre-fetching meta for all results. ──
		$post_ids = wp_list_pluck( $query->posts, 'ID' );
		Meta_Repository::prefetch( $post_ids, array_values( $this->meta_field_map ) );

		$items = array();
		foreach ( $query->posts as $post ) {
			$items[] = $this->prepare_item_data( $post );
		}

		$response = new WP_REST_Response( $items, 200 );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}

	/**
	 * Public proxy for validate_meta_value() (needed by Movie_Merge_Handler).
	 *
	 * @param string          $meta_key Meta key.
	 * @param mixed           $value    Value to validate.
	 * @param WP_REST_Request $request  Current request.
	 * @return true|WP_Error
	 */
	public function validate_meta( string $meta_key, $value, WP_REST_Request $request ) {
		return $this->validate_meta_value( $meta_key, $value, $request );
	}

	/**
	 * Public proxy for upsert_meta_value() (needed by Movie_Merge_Handler).
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $value    Value to store.
	 * @return void
	 */
	public function upsert_meta( int $post_id, string $meta_key, $value ): void {
		$this->upsert_meta_value( $post_id, $meta_key, $value );
	}

	/**
	 * Public proxy for protected prepare_item_data() (used by handlers).
	 *
	 * @param WP_Post|null $post Post object.
	 * @return array<string, mixed>
	 */
	public function prepare_item_data_public( ?WP_Post $post ): array {
		return $this->prepare_item_data( $post );
	}

	/**
	 * Public proxy for the private resolve_attachment_ids() helper.
	 *
	 * Required by Movie_Duplicate_Handler when comparing stored crew IDs.
	 *
	 * @param mixed $value Raw meta value.
	 * @return array<int, int>
	 */
	public function resolve_attachment_ids_public( $value ): array {
		return $this->resolve_attachment_ids( $value );
	}

	/**
	 * Public proxy for crew persistence (needed by Movie_Merge_Handler).
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function persist_crew( int $post_id, WP_REST_Request $request ) {
		return $this->crew_handler->persist( $post_id, $request );
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

		$title = $this->duplicate_handler->normalize_title( (string) $request->get_param( 'title' ) );

		if ( '' === $title ) {
			return parent::create_item( $request );
		}

		$candidates = $this->duplicate_handler->find_by_normalized_title( $title );

		if ( empty( $candidates ) ) {
			return parent::create_item( $request );
		}

		$incoming_signature = $this->duplicate_handler->build_incoming_signature( $request );

		foreach ( $candidates as $candidate ) {
			if ( $this->duplicate_handler->is_exact_duplicate( $candidate->ID, $request, $this ) ) {
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

		$target = $this->duplicate_handler->pick_best_candidate( $candidates, $incoming_signature );
		if ( ! $target instanceof WP_Post ) {
			return parent::create_item( $request );
		}

		return $this->merge_handler->merge( $target->ID, $request, $this );
	}

	// =========================================================================
	// Schema
	// =========================================================================

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
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'role'      => array( 'type' => 'string' ),
						'person'    => array( 'type' => array( 'integer', 'string' ) ),
						'character' => array( 'type' => 'string' ),
					),
				),
			),
		);
	}

	/**
	 * Register additional response schema fields for movies.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_additional_response_schema(): array {
		return array(
			'crew'               => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'role'      => array( 'type' => 'string' ),
						'person'    => array(
							'type'       => 'object',
							'properties' => array(
								'id'    => array( 'type' => 'integer' ),
								'slug'  => array( 'type' => 'string' ),
								'title' => array( 'type' => 'string' ),
							),
						),
						'character' => array( 'type' => 'string' ),
					),
				),
			),
			'featured_image_url' => array( 'type' => array( 'string', 'null' ) ),
			'featured_image'     => array(
				'type'       => array( 'object', 'null' ),
				'properties' => array(
					'id'  => array( 'type' => 'integer' ),
					'url' => array( 'type' => 'string' ),
				),
			),
			'taxonomy_terms'     => array(
				'type'                 => 'object',
				'additionalProperties' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
			'comments'           => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'parent'  => array( 'type' => 'integer' ),
						'author'  => array( 'type' => 'string' ),
						'url'     => array( 'type' => 'string' ),
						'date'    => array( 'type' => 'string' ),
						'content' => array( 'type' => 'string' ),
					),
				),
			),
			'gallery_image_urls' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'string' ),
			),
			'gallery_images'     => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'  => array( 'type' => 'integer' ),
						'url' => array( 'type' => 'string' ),
					),
				),
			),
			'gallery_video_urls' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'string' ),
			),
			'gallery_videos'     => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'  => array( 'type' => 'integer' ),
						'url' => array( 'type' => 'string' ),
					),
				),
			),
			'carousel_url'       => array( 'type' => array( 'string', 'null' ) ),
			'carousel'           => array(
				'type'       => array( 'object', 'null' ),
				'properties' => array(
					'id'  => array( 'type' => 'integer' ),
					'url' => array( 'type' => 'string' ),
				),
			),
		);
	}

	// =========================================================================
	// Payload validation
	// =========================================================================

	/**
	 * Validate movie request payload before create/update/merge.
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
	 * Validate movie-specific meta values.
	 *
	 * @param string          $meta_key Meta key.
	 * @param mixed           $value    Meta value.
	 * @param WP_REST_Request $request  Request.
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
			$normalized = $this->duplicate_handler->normalize_content_rating( $value );
			$allowed    = array( 'U', 'U/A', 'G', 'PG', 'PG-13', 'R', 'NC-17' );

			if ( null === $normalized || ! in_array( $normalized, $allowed, true ) ) {
				return new WP_Error( 'rt_rest_invalid_content_rating', __( 'Movie content rating is invalid.', 'rt-movie-library' ), array( 'status' => 400 ) );
			}
		}

		return true;
	}

	// =========================================================================
	// Persistence
	// =========================================================================

	/**
	 * Persist crew metadata after post create / update.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	protected function persist_additional_fields( int $post_id, WP_REST_Request $request ) {
		return $this->crew_handler->persist( $post_id, $request );
	}

	/**
	 * Upsert movie media meta keys with attachment resolution.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $value    Value.
	 * @return void
	 */
	protected function upsert_meta_value( int $post_id, string $meta_key, $value ): void {
		if ( 'rt-media-meta-video' === $meta_key ) {
			$video_ids = $this->resolve_media_attachment_ids( $value, 'video' );
			if ( empty( $video_ids ) ) {
				Meta_Repository::delete( $post_id, $meta_key );
			} else {
				Meta_Repository::update( $post_id, $meta_key, wp_json_encode( $video_ids ) );
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
				Meta_Repository::update( $post_id, $meta_key, $attachment_id );
			} else {
				Meta_Repository::delete( $post_id, $meta_key );
			}

			return;
		}

		parent::upsert_meta_value( $post_id, $meta_key, $value );
	}

	// =========================================================================
	// Response payload
	// =========================================================================

	/**
	 * Build movie-specific response payload fields.
	 *
	 * Crew persons are loaded in a single batch query via Movie_Crew_Handler.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 */
	protected function get_additional_response_payload( int $post_id ): array {
		$release_date_raw       = (string) Meta_Repository::get( $post_id, 'rt-movie-meta-basic-release-date', true );
		$formatted_release_date = '';

		if ( '' !== $release_date_raw ) {
			$timestamp = strtotime( $release_date_raw );

			if ( false !== $timestamp ) {
				$formatted_release_date = wp_date( get_option( 'date_format' ), $timestamp );
			}
		}

		$thumbnail_id = (int) get_post_thumbnail_id( $post_id );
		$img_ids      = $this->resolve_attachment_ids( Meta_Repository::get( $post_id, 'rt-media-meta-img', true ) );
		$video_ids    = $this->resolve_attachment_ids( Meta_Repository::get( $post_id, 'rt-media-meta-video', true ) );
		$carousel_raw = Meta_Repository::get( $post_id, 'rt-movie-meta-carousel-poster', true );

		return array(
			'crew'                   => $this->crew_handler->build_response_payload( $post_id ),
			'featured_image_url'     => $this->get_featured_image_url( $post_id ),
			'featured_image'         => $this->attachment_payload_or_null( $thumbnail_id ),
			'taxonomy_terms'         => $this->get_taxonomy_terms_payload( $post_id ),
			'comments'               => $this->get_comments_payload( $post_id ),
			'gallery_image_urls'     => Cpt_Helper::image_ids_to_urls( $img_ids ),
			'gallery_images'         => $this->attachment_payload_list( $img_ids ),
			'gallery_video_urls'     => Cpt_Helper::image_ids_to_urls( $video_ids ),
			'gallery_videos'         => $this->attachment_payload_list( $video_ids ),
			'carousel_url'           => $this->attachment_url_or_null( $carousel_raw ),
			'carousel'               => $this->attachment_payload_or_null( $carousel_raw ),
			'formatted_release_date' => $formatted_release_date,
		);
	}

	/**
	 * Strip the raw `taxonomies` key; taxonomy names live in `taxonomy_terms`.
	 *
	 * @param WP_Post|null $post Post object.
	 * @return array<string, mixed>
	 */
	protected function prepare_item_data( ?WP_Post $post ): array {
		$data = parent::prepare_item_data( $post );
		unset( $data['taxonomies'] );

		return $data;
	}

	/**
	 * Expose only scalar movie basics in `meta`; complex media/crew data has
	 * its own dedicated top-level response fields.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	protected function get_meta_payload( int $post_id ): array {
		$payload = parent::get_meta_payload( $post_id );

		unset( $payload['rt-media-meta-img'] );
		unset( $payload['rt-media-meta-video'] );
		unset( $payload['rt-movie-meta-carousel-poster'] );

		return $payload;
	}

	// =========================================================================
	// Private filtering helpers
	// =========================================================================

	/**
	 * Resolve shadow taxonomy slug for a director person ID.
	 *
	 * Using the shadow taxonomy keeps filtering aligned with existing data model
	 * and avoids introducing meta-query joins for relationship matching.
	 *
	 * @param int $director_id Director post ID.
	 * @return string
	 */
	private function get_director_shadow_slug( int $director_id ): string {
		$person_post = get_post( $director_id );

		if ( ! $person_post instanceof WP_Post || 'rt-person' !== $person_post->post_type ) {
			return '';
		}

		return sanitize_title( $person_post->post_name . '-' . $director_id );
	}

	// =========================================================================
	// Private media helpers
	// =========================================================================

	/**
	 * Return the full URL of the featured image or null.
	 *
	 * @param int $post_id Post ID.
	 * @return string|null
	 */
	private function get_featured_image_url( int $post_id ): ?string {
		$url = get_the_post_thumbnail_url( $post_id, 'full' );
		return is_string( $url ) && '' !== $url ? $url : null;
	}

	/**
	 * Return an attachment URL or null.
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
	 * Return an attachment payload (id + url) or null.
	 *
	 * @param mixed $attachment_id Attachment ID.
	 * @return array<string, mixed>|null
	 */
	private function attachment_payload_or_null( $attachment_id ): ?array {
		$attachment_id = absint( $attachment_id );
		if ( $attachment_id <= 0 ) {
			return null;
		}

		$url = wp_get_attachment_url( $attachment_id );
		if ( ! is_string( $url ) || '' === $url ) {
			return null;
		}

		return array(
			'id'  => $attachment_id,
			'url' => $url,
		);
	}

	/**
	 * Return a list of attachment payloads (id + url).
	 *
	 * @param array<int, int> $attachment_ids Attachment IDs.
	 * @return array<int, array<string, mixed>>
	 */
	private function attachment_payload_list( array $attachment_ids ): array {
		$payload = array();

		foreach ( $attachment_ids as $id ) {
			$entry = $this->attachment_payload_or_null( $id );
			if ( is_array( $entry ) ) {
				$payload[] = $entry;
			}
		}

		return $payload;
	}

	/**
	 * Decode a JSON-encoded or raw attachment ID value into an int array.
	 *
	 * @param mixed $value Raw meta value.
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
	 * Resolve media (image or video) attachment IDs from IDs / URLs.
	 *
	 * @param mixed  $value Value.
	 * @param string $type  MIME type prefix (e.g. 'image', 'video').
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
	 * Build taxonomy name payload for all allowed taxonomies.
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
	 * Return approved comments for a movie post.
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
}
