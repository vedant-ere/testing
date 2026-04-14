<?php
/**
 * Block editor data REST controller for Gutenberg inspector lookups.
 *
 * This file exposes lightweight, editor-only REST endpoints used by custom
 * block controls (director dropdowns, movie/person autocomplete searches).
 * The payloads are intentionally minimal and permission-gated so block-side
 * interactions stay fast without exposing unnecessary public data surfaces.
 *
 * Keeps editor-only lookup payloads separate from the public CRUD endpoints so
 * inspector controls can stay fast without coupling UI concerns to main APIs.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Rest;

use RT_Movie_Library\Traits\Singleton;
use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Class Block_Data_Controller
 *
 * Exposes lightweight block-editor-only endpoints for inspector controls.
 *
 * These routes intentionally return minimal fields (`id` + label text) because
 * block sidebars call them repeatedly while authors type and filter content.
 */
class Block_Data_Controller {

	use Singleton;

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private const REST_NAMESPACE = 'rt-movie-library/v1';

	/**
	 * Cache TTL for director list transient.
	 *
	 * @var int
	 */
	private const DIRECTORS_CACHE_TTL = 300;

	/**
	 * Max records for person/movie search endpoints.
	 *
	 * @var int
	 */
	private const SEARCH_LIMIT = 20;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register block editor data routes.
	 *
	 * Separate endpoints let the editor fetch small, purpose-built datasets
	 * instead of over-fetching from generic CPT endpoints.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/block-data/directors',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_directors' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/block-data/persons',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_persons' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'args'                => array(
					'search' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( Cpt_Helper::class, 'validate_collection_search' ),
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/block-data/movies',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_movies' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'args'                => array(
					'search' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( Cpt_Helper::class, 'validate_collection_search' ),
					),
				),
			)
		);
	}

	/**
	 * Check editor capability for block data routes.
	 *
	 * Restricting these routes avoids exposing site content lookups to public
	 * consumers; only users editing content need this data.
	 *
	 * @return true|WP_Error
	 */
	public function check_edit_permission() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rt_rest_forbidden',
				__( 'You do not have permission to access block data.', 'rt-movie-library' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get all director person records for movies block filter UI.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_directors( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );

		$cache_key = 'rt_block_data_directors';
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return new WP_REST_Response( $cached, 200 );
		}

		$director_term = get_term_by( 'slug', 'director', 'rt-person-career' );

		if ( ! $director_term || is_wp_error( $director_term ) ) {
			return new WP_REST_Response( array(), 200 );
		}

		$directors_query = new WP_Query(
			array(
				'post_type'              => 'rt-person',
				'post_status'            => Cpt_Helper::get_public_post_statuses(),
				'posts_per_page'         => 50,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'fields'                 => 'all',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'tax_query'              => array(
					array(
						'taxonomy' => 'rt-person-career',
						'field'    => 'term_id',
						'terms'    => array( (int) $director_term->term_id ),
					),
				),
			)
		);

		$data = array_map(
			static function ( WP_Post $person ): array {
				return array(
					'id'   => (int) $person->ID,
					'name' => (string) $person->post_title,
				);
			},
			$directors_query->posts
		);

		set_transient( $cache_key, $data, self::DIRECTORS_CACHE_TTL );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Search person records for block combobox controls.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_persons( WP_REST_Request $request ): WP_REST_Response {
		$search = sanitize_text_field( (string) $request->get_param( 'search' ) );
		$data   = $this->search_posts_by_title( 'rt-person', $search );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Search movie records for block combobox controls.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_movies( WP_REST_Request $request ): WP_REST_Response {
		$search = sanitize_text_field( (string) $request->get_param( 'search' ) );
		$posts  = $this->search_posts_by_title( 'rt-movie', $search );

		$data = array_map(
			static function ( array $item ): array {
				return array(
					'id'    => (int) $item['id'],
					'title' => (string) $item['name'],
				);
			},
			$posts
		);

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Search posts by title for a post type.
	 *
	 * Title-only matching keeps autocomplete intent predictable and avoids
	 * noisy matches from full-content search in editor comboboxes.
	 *
	 * @param string $post_type Post type slug.
	 * @param string $search    Search term.
	 * @return array<int, array{id:int,name:string}>
	 */
	private function search_posts_by_title( string $post_type, string $search ): array {
		$query_args = array(
			'post_type'              => $post_type,
			'post_status'            => Cpt_Helper::get_public_post_statuses(),
			'posts_per_page'         => self::SEARCH_LIMIT,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'fields'                 => 'all',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( '' !== $search ) {
			$query_args['s']              = $search;
			$query_args['search_columns'] = array( 'post_title' );
		}

		$query = new WP_Query( $query_args );

		return array_map(
			static function ( WP_Post $post ): array {
				return array(
					'id'   => (int) $post->ID,
					'name' => (string) $post->post_title,
				);
			},
			$query->posts
		);
	}
}
