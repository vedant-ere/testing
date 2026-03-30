<?php
/**
 * REST controller for Post Embedder block APIs.
 *
 * @package RT_Post_Embedder
 */

namespace RT_Post_Embedder;

use RT_Post_Embedder\Traits\Singleton;
use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rest_Controller
 */
class Rest_Controller {

	use Singleton;

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private const REST_NAMESPACE = 'rt-post-embedder/v1';

	/**
	 * Search page size.
	 *
	 * @var int
	 */
	private const RESULTS_PER_PAGE = 10;

	/**
	 * Search cache TTL in seconds.
	 *
	 * @var int
	 */
	private const SEARCH_CACHE_TTL = 300;

	/**
	 * Cache lock TTL in seconds.
	 *
	 * @var int
	 */
	private const CACHE_LOCK_TTL = 15;

	/**
	 * Option key tracking cache version.
	 *
	 * @var string
	 */
	private const CACHE_VERSION_OPTION = 'rt_pe_search_cache_version';

	/**
	 * Register hooks.
	 */
	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'save_post', array( $this, 'bump_cache_version' ) );
		add_action( 'deleted_post', array( $this, 'bump_cache_version' ) );
		add_action( 'set_object_terms', array( $this, 'bump_cache_version_on_term_set' ), 10, 6 );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/search',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search_posts' ),
				'permission_callback' => array( $this, 'require_edit_posts' ),
				'args'                => array(
					'search'    => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_search_param' ),
					),
					'page'      => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'validate_page_param' ),
					),
					'post_type' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => array( $this, 'validate_post_type_param' ),
					),
				),
			)
		);
	}

	/**
	 * Check endpoint capability.
	 *
	 * @return true|WP_Error
	 */
	public function require_edit_posts() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rt_pe_forbidden',
				__( 'You do not have permission to access this endpoint.', 'rt-post-embedder' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Validate search string.
	 *
	 * @param mixed           $value   Param value.
	 * @param WP_REST_Request $request Request object.
	 * @param string          $param   Param key.
	 * @return bool
	 */
	public function validate_search_param( $value, WP_REST_Request $request, string $param ): bool {
		unset( $request, $param );

		if ( ! is_string( $value ) ) {
			return false;
		}

		return strlen( $value ) <= 200;
	}

	/**
	 * Validate page number.
	 *
	 * @param mixed           $value   Param value.
	 * @param WP_REST_Request $request Request object.
	 * @param string          $param   Param key.
	 * @return bool
	 */
	public function validate_page_param( $value, WP_REST_Request $request, string $param ): bool {
		unset( $request, $param );

		return is_numeric( $value ) && (int) $value > 0;
	}

	/**
	 * Validate post type filter.
	 *
	 * @param mixed           $value   Param value.
	 * @param WP_REST_Request $request Request object.
	 * @param string          $param   Param key.
	 * @return bool
	 */
	public function validate_post_type_param( $value, WP_REST_Request $request, string $param ): bool {
		unset( $request, $param );

		if ( '' === $value ) {
			return true;
		}

		if ( ! is_string( $value ) ) {
			return false;
		}

		return post_type_exists( sanitize_key( $value ) );
	}

	/**
	 * Search posts with pagination.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function search_posts( WP_REST_Request $request ): WP_REST_Response {
		$search    = sanitize_text_field( (string) $request->get_param( 'search' ) );
		$page      = max( 1, absint( (string) $request->get_param( 'page' ) ) );
		$post_type = sanitize_key( (string) $request->get_param( 'post_type' ) );

		$searchable_post_types = $this->get_searchable_post_types();
		$post_types            = $this->resolve_post_type_filter( $post_type, $searchable_post_types );
		$cache_key             = $this->get_search_cache_key( $search, $page, $post_types );

		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return new WP_REST_Response( $cached, 200 );
		}

		$lock_key      = $cache_key . ':lock';
		$lock_acquired = $this->acquire_cache_lock( $lock_key );

		if ( ! $lock_acquired ) {
			/*
			 * A short wait reduces duplicate expensive queries when many requests for
			 * the same search term hit the endpoint concurrently.
			 */
			usleep( 150000 );
			$retry_cached = get_transient( $cache_key );

			if ( is_array( $retry_cached ) ) {
				return new WP_REST_Response( $retry_cached, 200 );
			}
		}

		$query_args = array(
			'post_type'              => $post_types,
			'post_status'            => $this->get_public_post_statuses(),
			'posts_per_page'         => self::RESULTS_PER_PAGE,
			'paged'                  => $page,
			'orderby'                => '' !== $search ? 'relevance' : 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( '' !== $search ) {
			$query_args['s']              = $search;
			$query_args['search_columns'] = array( 'post_title' );
		}

		$query = new WP_Query( $query_args );

		$response = array(
			'posts'       => array_map( array( $this, 'format_post_for_response' ), $query->posts ),
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'page'        => $page,
		);

		set_transient( $cache_key, $response, self::SEARCH_CACHE_TTL );

		if ( $lock_acquired ) {
			delete_transient( $lock_key );
		}

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Bump cache version after content changes.
	 *
	 * @return void
	 */
	public function bump_cache_version(): void {
		$version = (int) get_option( self::CACHE_VERSION_OPTION, 1 );
		update_option( self::CACHE_VERSION_OPTION, $version + 1, false );
	}

	/**
	 * Bump cache version when post terms are updated.
	 *
	 * @param int          $object_id     Object ID.
	 * @param int[]|string $terms         Terms.
	 * @param int[]        $tt_ids        Term taxonomy IDs.
	 * @param string       $taxonomy      Taxonomy slug.
	 * @param bool         $append        Append flag.
	 * @param int[]        $old_tt_ids    Previous term taxonomy IDs.
	 * @return void
	 */
	public function bump_cache_version_on_term_set( int $object_id, $terms, array $tt_ids, string $taxonomy, bool $append, array $old_tt_ids ): void {
		unset( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids );
		$this->bump_cache_version();
	}

	/**
	 * Get searchable post types.
	 *
	 * @return string[]
	 */
	private function get_searchable_post_types(): array {
		$post_types = get_post_types(
			array(
				'public'       => true,
				'show_in_rest' => true,
			),
			'names'
		);

		unset( $post_types[ Custom_Posts_Cpt::POST_TYPE ] );
		unset( $post_types['attachment'] );

		return array_values( $post_types );
	}

	/**
	 * Resolve the requested post type filter.
	 *
	 * @param string   $post_type        Requested post type.
	 * @param string[] $searchable_types Searchable type list.
	 * @return string[]
	 */
	private function resolve_post_type_filter( string $post_type, array $searchable_types ): array {
		if ( '' !== $post_type && in_array( $post_type, $searchable_types, true ) ) {
			return array( $post_type );
		}

		return $searchable_types;
	}

	/**
	 * Build cache key for search endpoint.
	 *
	 * @param string   $search     Search term.
	 * @param int      $page       Page number.
	 * @param string[] $post_types Post type filter list.
	 * @return string
	 */
	private function get_search_cache_key( string $search, int $page, array $post_types ): string {
		$version = (int) get_option( self::CACHE_VERSION_OPTION, 1 );
		$payload = wp_json_encode(
			array(
				's' => $search,
				'p' => $page,
				't' => $post_types,
				'v' => $version,
			)
		);

		return 'rt_pe_search_' . md5( (string) $payload );
	}

	/**
	 * Acquire cache lock.
	 *
	 * @param string $lock_key Lock transient key.
	 * @return bool
	 */
	private function acquire_cache_lock( string $lock_key ): bool {
		if ( false !== get_transient( $lock_key ) ) {
			return false;
		}

		return set_transient( $lock_key, '1', self::CACHE_LOCK_TTL );
	}

	/**
	 * Get public statuses for searches.
	 *
	 * @return string[]
	 */
	private function get_public_post_statuses(): array {
		$statuses = get_post_stati( array( 'public' => true ), 'names' );
		return array_values( $statuses );
	}

	/**
	 * Format response payload for one post.
	 *
	 * @param WP_Post $post Post object.
	 * @return array<string, mixed>
	 */
	private function format_post_for_response( WP_Post $post ): array {
		$thumbnail_id = (int) get_post_thumbnail_id( $post->ID );
		$thumbnail    = '';

		if ( $thumbnail_id > 0 ) {
			$resolved_thumbnail = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
			$thumbnail          = is_string( $resolved_thumbnail ) ? $resolved_thumbnail : '';
		}

		return array(
			'id'            => (int) $post->ID,
			'title'         => (string) get_the_title( $post ),
			'excerpt'       => (string) get_the_excerpt( $post ),
			'date'          => (string) get_the_date( 'c', $post ),
			'type'          => (string) $post->post_type,
			'link'          => (string) get_permalink( $post ),
			'thumbnail_id'  => $thumbnail_id,
			'thumbnail_url' => $thumbnail,
		);
	}
}
