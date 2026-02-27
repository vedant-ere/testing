<?php
/**
 * Base REST controller for CPT resources.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Rest;

use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Class Base_Cpt_Controller
 */
abstract class Base_Cpt_Controller extends WP_REST_Controller {

	/**
	 * Target post type handled by the controller.
	 *
	 * @var string
	 */
	protected $post_type = '';

	/**
	 * Map of request field names to post meta keys.
	 *
	 * @var array<string, string>
	 */
	protected $meta_field_map = array();

	/**
	 * Alternate input aliases for request-to-meta mapping.
	 *
	 * @var array<string, string>
	 */
	protected $meta_input_aliases = array();

	/**
	 * Map of request taxonomy keys to taxonomy slugs.
	 *
	 * @var array<string, string>
	 */
	protected $taxonomy_field_map = array();

	/**
	 * Explicit list of taxonomies accepted by this resource.
	 *
	 * @var array<int, string>
	 */
	protected $allowed_taxonomies = array();

	/**
	 * JSON schema for meta keys returned in responses.
	 *
	 * @var array<string, mixed>
	 */
	protected $meta_response_schema = array();

	/**
	 * Extra writable request args merged into create/update schema.
	 *
	 * @var array<string, mixed>
	 */
	protected $extra_write_args = array();

	/**
	 * Optional taxonomy descriptions keyed by taxonomy slug.
	 *
	 * @var array<string, string>
	 */
	protected $taxonomy_descriptions = array();

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_create_update_args( true ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description'       => __( 'Unique identifier for the post.', 'rt-movie-library' ),
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( Cpt_Helper::class, 'validate_id' ),
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_create_update_args( false ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'description' => __( 'Whether to bypass trash and force deletion.', 'rt-movie-library' ),
							'type'        => 'boolean',
							'default'     => false,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/slug/(?P<slug>[a-z0-9-]+)',
			array(
				'args'   => array(
					'slug' => array(
						'description'       => __( 'Unique slug for the post.', 'rt-movie-library' ),
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_title',
						'validate_callback' => array( Cpt_Helper::class, 'validate_slug' ),
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_by_slug' ),
					'permission_callback' => array( $this, 'get_item_by_slug_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Return collection query args schema.
	 *
	 * @return array<string, mixed>
	 */
	public function get_collection_params() {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'rt-movie-library' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items per page.', 'rt-movie-library' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
			'search'   => array(
				'description'       => __( 'Limit results to items matching a search term.', 'rt-movie-library' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status'   => array(
				'description'       => __( 'Limit results to one or more post statuses.', 'rt-movie-library' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'string',
					'enum' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				),
				'default'           => array( 'publish' ),
				'sanitize_callback' => array( Cpt_Helper::class, 'sanitize_status_list' ),
			),
		);
	}

	/**
	 * Build and cache the item response schema.
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema() {
		if ( is_array( $this->schema ) ) {
			return $this->schema;
		}

		$properties = array(
			'id'             => array(
				'type'     => 'integer',
				'readonly' => true,
			),
			'type'           => array(
				'type'     => 'string',
				'readonly' => true,
			),
			'status'         => array( 'type' => 'string' ),
			'title'          => array( 'type' => 'string' ),
			'content'        => array( 'type' => 'string' ),
			'excerpt'        => array( 'type' => 'string' ),
			'slug'           => array( 'type' => 'string' ),
			'author'         => array( 'type' => 'integer' ),
			'featured_media' => array( 'type' => 'integer' ),
			'date_gmt'       => array(
				'type'     => 'string',
				'readonly' => true,
			),
			'modified_gmt'   => array(
				'type'     => 'string',
				'readonly' => true,
			),
			'link'           => array(
				'type'     => 'string',
				'readonly' => true,
			),
			'meta'           => array(
				'type'                 => 'object',
				'additionalProperties' => true,
				'properties'           => $this->meta_response_schema,
			),
			'taxonomies'     => array(
				'type'                 => 'object',
				'additionalProperties' => true,
			),
		);

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array_merge( $properties, $this->get_additional_response_schema() ),
		);

		return $this->schema;
	}

	/**
	 * Return public item schema for REST route registration.
	 *
	 * @return array<string, mixed>
	 */
	public function get_public_item_schema() {
		return $this->get_item_schema();
	}

	/**
	 * Build create/update argument schema for the resource.
	 *
	 * @param bool $required_title Required title.
	 * @return array<string, mixed>
	 */
	protected function get_create_update_args( bool $required_title ): array {
		$args = array(
			'title'          => array(
				'description'       => __( 'Post title.', 'rt-movie-library' ),
				'type'              => 'string',
				'required'          => $required_title,
				'minLength'         => 1,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'content'        => array(
				'description'       => __( 'Post content.', 'rt-movie-library' ),
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
			),
			'excerpt'        => array(
				'description'       => __( 'Post excerpt.', 'rt-movie-library' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'status'         => array(
				'description'       => __( 'Post status.', 'rt-movie-library' ),
				'type'              => 'string',
				'enum'              => array( 'draft', 'publish', 'pending', 'private' ),
				'default'           => 'draft',
				'sanitize_callback' => 'sanitize_key',
			),
			'author'         => array(
				'description'       => __( 'Post author user ID.', 'rt-movie-library' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'featured_media' => array(
				'description'       => __( 'Featured media attachment ID.', 'rt-movie-library' ),
				'type'              => 'integer',
				'minimum'           => 0,
				'sanitize_callback' => 'absint',
				'validate_callback' => array( Cpt_Helper::class, 'validate_featured_media' ),
			),
			'meta'           => array(
				'description' => __( 'Associative metadata payload.', 'rt-movie-library' ),
				'type'        => 'object',
			),
			'taxonomies'     => array(
				'description' => __( 'Associative taxonomy payload.', 'rt-movie-library' ),
				'type'        => 'object',
			),
		);

		return array_merge( $args, $this->extra_write_args, $this->get_additional_write_args() );
	}

	/**
	 * Always allow collection reads at permission-check level.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true
	 */
	public function get_items_permissions_check( $request ) {
		unset( $request );
		return true;
	}

	/**
	 * Permissions callback for single-item read.
	 *
	 * Read access is public; unpublished items are gated in `get_item()`.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return true
	 */
	public function get_item_permissions_check( $request ) {
		unset( $request );
		return true;
	}

	/**
	 * Permissions callback for slug-based single-item read.
	 *
	 * Read access is public; unpublished items are gated in `get_item_by_slug()`.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return true
	 */
	public function get_item_by_slug_permissions_check( $request ) {
		unset( $request );
		return true;
	}

	/**
	 * Permissions callback for create operation.
	 *
	 * Requires authenticated Application Password and editor/admin capabilities.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		unset( $request );
		return $this->check_write_permissions();
	}

	/**
	 * Permissions callback for update operation.
	 *
	 * Validates global write capability and per-post `edit_post` capability.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$permission = $this->check_write_permissions();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$post = get_post( (int) $request['id'] );

		if ( ! $post instanceof WP_Post || $this->post_type !== $post->post_type ) {
			return new WP_Error( 'rt_rest_not_found', __( 'Requested item was not found.', 'rt-movie-library' ), array( 'status' => 404 ) );
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return new WP_Error( 'rt_rest_forbidden', __( 'You are not allowed to edit this item.', 'rt-movie-library' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Permissions callback for delete operation.
	 *
	 * Validates global write capability and per-post `delete_post` capability.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$permission = $this->check_write_permissions();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$post = get_post( (int) $request['id'] );

		if ( ! $post instanceof WP_Post || $this->post_type !== $post->post_type ) {
			return new WP_Error( 'rt_rest_not_found', __( 'Requested item was not found.', 'rt-movie-library' ), array( 'status' => 404 ) );
		}

		if ( ! current_user_can( 'delete_post', $post->ID ) ) {
			return new WP_Error( 'rt_rest_forbidden', __( 'You are not allowed to delete this item.', 'rt-movie-library' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Collection read callback.
	 *
	 * Applies pagination, search, and post-status filtering.
	 * Non-editor users only receive published posts.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = (int) $request->get_param( 'per_page' );
		$search   = (string) $request->get_param( 'search' );
		$statuses = $request->get_param( 'status' );

		if ( empty( $statuses ) || ! is_array( $statuses ) ) {
			$statuses = array( 'publish' );
		}

		$post_status = ( is_user_logged_in() && current_user_can( 'edit_others_posts' ) ) ? $statuses : array( 'publish' );

		$query = new WP_Query(
			array(
				'post_type'      => $this->post_type,
				'post_status'    => $post_status,
				'posts_per_page' => $per_page,
				'paged'          => $page,
				's'              => $search,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

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
	 * Single-item read callback by numeric ID.
	 *
	 * Unpublished posts are readable only by users with `edit_post`.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$post = get_post( (int) $request['id'] );

		if ( ! $post instanceof WP_Post || $this->post_type !== $post->post_type ) {
			return new WP_Error( 'rt_rest_not_found', __( 'Requested item was not found.', 'rt-movie-library' ), array( 'status' => 404 ) );
		}

		if ( 'publish' !== $post->post_status && ( ! is_user_logged_in() || ! current_user_can( 'edit_post', $post->ID ) ) ) {
			return new WP_Error( 'rt_rest_forbidden', __( 'You are not allowed to view this item.', 'rt-movie-library' ), array( 'status' => 403 ) );
		}

		return new WP_REST_Response( $this->prepare_item_data( $post ), 200 );
	}

	/**
	 * Single-item read callback by slug.
	 *
	 * Slug is normalized with `sanitize_title` and queried directly.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item_by_slug( $request ) {
		$slug = sanitize_title( (string) $request['slug'] );
		$post = null;

		if ( '' !== $slug ) {
			$query = new WP_Query(
				array(
					'post_type'      => $this->post_type,
					'name'           => $slug,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
					'posts_per_page' => 1,
					'no_found_rows'  => true,
				)
			);

			if ( ! empty( $query->posts ) && $query->posts[0] instanceof WP_Post ) {
				$post = $query->posts[0];
			}
		}

		if ( ! $post instanceof WP_Post || $this->post_type !== $post->post_type ) {
			return new WP_Error( 'rt_rest_not_found', __( 'Requested item was not found.', 'rt-movie-library' ), array( 'status' => 404 ) );
		}

		if ( 'publish' !== $post->post_status && ( ! is_user_logged_in() || ! current_user_can( 'edit_post', $post->ID ) ) ) {
			return new WP_Error( 'rt_rest_forbidden', __( 'You are not allowed to view this item.', 'rt-movie-library' ), array( 'status' => 403 ) );
		}

		return new WP_REST_Response( $this->prepare_item_data( $post ), 200 );
	}

	/**
	 * Create callback.
	 *
	 * Writes core post fields first, then applies meta/taxonomies/media and
	 * resource-specific extras (e.g., movie crew).
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$postarr = $this->build_post_array_from_request( $request, 0 );
		$post_id = wp_insert_post( wp_slash( $postarr ), true );

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error(
				'rt_rest_create_failed',
				__( 'Failed to create item.', 'rt-movie-library' ),
				array(
					'status'  => 400,
					'details' => $post_id->get_error_message(),
				) 
			);
		}

		$extra_result = $this->persist_all_fields( $post_id, $request );

		if ( is_wp_error( $extra_result ) ) {
			return $extra_result;
		}

		return new WP_REST_Response( $this->prepare_item_data( get_post( $post_id ) ), 201 );
	}

	/**
	 * Update callback.
	 *
	 * Operates in PATCH-style semantics: only submitted fields are updated.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post || $this->post_type !== $post->post_type ) {
			return new WP_Error( 'rt_rest_not_found', __( 'Requested item was not found.', 'rt-movie-library' ), array( 'status' => 404 ) );
		}

		$postarr = $this->build_post_array_from_request( $request, $post_id );

		$result = wp_update_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				'rt_rest_update_failed',
				__( 'Failed to update item.', 'rt-movie-library' ),
				array(
					'status'  => 400,
					'details' => $result->get_error_message(),
				) 
			);
		}

		$extra_result = $this->persist_all_fields( $post_id, $request );

		if ( is_wp_error( $extra_result ) ) {
			return $extra_result;
		}

		return new WP_REST_Response( $this->prepare_item_data( get_post( $post_id ) ), 200 );
	}

	/**
	 * Build post array from request params for create/update.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param int             $post_id Post ID.
	 * @return array<string, mixed>
	 */
	protected function build_post_array_from_request( WP_REST_Request $request, int $post_id ): array {
		$postarr = array();

		if ( 0 === $post_id ) {
			$postarr['post_type']   = $this->post_type;
			$postarr['post_author'] = get_current_user_id();
		} else {
			$postarr['ID'] = $post_id;
		}

		$param_map = array(
			'title'   => 'post_title',
			'content' => 'post_content',
			'excerpt' => 'post_excerpt',
			'status'  => 'post_status',
		);

		foreach ( $param_map as $request_key => $post_field ) {
			if ( $request->has_param( $request_key ) ) {
				$postarr[ $post_field ] = $request->get_param( $request_key );
			}
		}

		if ( $request->has_param( 'author' ) ) {
			$author = absint( $request->get_param( 'author' ) );
			if ( $author > 0 && current_user_can( 'edit_others_posts' ) ) {
				$postarr['post_author'] = $author;
			}
		}

		return $postarr;
	}

	/**
	 * Persist all non-core fields after post create/update.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	protected function persist_all_fields( int $post_id, WP_REST_Request $request ) {
		$this->persist_meta_fields( $post_id, $request );
		$this->persist_taxonomy_fields( $post_id, $request );
		$this->maybe_set_featured_media( $post_id, $request );

		return $this->persist_additional_fields( $post_id, $request );
	}

	/**
	 * Delete callback.
	 *
	 * By default this moves to trash (`force=false`) to match assignment needs.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$post = get_post( (int) $request['id'] );

		if ( ! $post instanceof WP_Post || $this->post_type !== $post->post_type ) {
			return new WP_Error( 'rt_rest_not_found', __( 'Requested item was not found.', 'rt-movie-library' ), array( 'status' => 404 ) );
		}

		$deleted = wp_delete_post( $post->ID, (bool) $request->get_param( 'force' ) );

		if ( ! $deleted instanceof WP_Post ) {
			return new WP_Error( 'rt_rest_delete_failed', __( 'Failed to delete item.', 'rt-movie-library' ), array( 'status' => 400 ) );
		}

		return new WP_REST_Response(
			array(
				'deleted'  => true,
				'previous' => $this->prepare_item_data( $post ),
			),
			200
		);
	}

	/**
	 * Build normalized API response payload for one post.
	 *
	 * @param WP_Post|null $post Post.
	 * @return array<string, mixed>
	 */
	protected function prepare_item_data( ?WP_Post $post ): array {
		if ( ! $post instanceof WP_Post ) {
			return array();
		}

		$data = array(
			'id'             => (int) $post->ID,
			'type'           => $post->post_type,
			'status'         => $post->post_status,
			'title'          => get_the_title( $post ),
			'content'        => (string) $post->post_content,
			'excerpt'        => (string) $post->post_excerpt,
			'slug'           => $post->post_name,
			'author'         => (int) $post->post_author,
			'featured_media' => (int) get_post_thumbnail_id( $post->ID ),
			'date_gmt'       => (string) $post->post_date_gmt,
			'modified_gmt'   => (string) $post->post_modified_gmt,
			'link'           => get_permalink( $post ),
			'meta'           => $this->get_meta_payload( $post->ID ),
			'taxonomies'     => $this->get_taxonomy_payload( $post->ID ),
		);

		return array_merge( $data, $this->get_additional_response_payload( $post->ID ) );
	}

	/**
	 * Persist mapped meta fields from top-level/meta payload.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request.
	 * @return void
	 */
	protected function persist_meta_fields( int $post_id, WP_REST_Request $request ): void {
		foreach ( $this->meta_field_map as $request_key => $meta_key ) {
			if ( $request->has_param( $request_key ) ) {
				$this->upsert_meta_value( $post_id, $meta_key, $request->get_param( $request_key ) );
			}
		}

		$meta_payload = $request->get_param( 'meta' );

		if ( ! is_array( $meta_payload ) ) {
			return;
		}

		foreach ( $meta_payload as $key => $value ) {
			if ( ! is_string( $key ) ) {
				continue;
			}

			$meta_key = $this->resolve_meta_key_from_input_key( $key );

			if ( null === $meta_key ) {
				continue;
			}

			$this->upsert_meta_value( $post_id, $meta_key, $value );
		}
	}

	/**
	 * Upsert a single meta value with media-special handling.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $value Value.
	 * @return void
	 */
	protected function upsert_meta_value( int $post_id, string $meta_key, $value ): void {
		if ( 'rt-media-meta-img' === $meta_key ) {
			$image_ids = Cpt_Helper::resolve_image_attachment_ids( $value );

			if ( empty( $image_ids ) ) {
				delete_post_meta( $post_id, $meta_key );
			} else {
				update_post_meta( $post_id, $meta_key, wp_json_encode( $image_ids ) );
			}

			return;
		}

		if ( null === $value || '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}

		update_post_meta( $post_id, $meta_key, $value );
	}

	/**
	 * Resolve a request input key to a concrete post meta key.
	 *
	 * @param string $input_key Input key.
	 * @return string|null
	 */
	protected function resolve_meta_key_from_input_key( string $input_key ): ?string {
		if ( isset( $this->meta_input_aliases[ $input_key ] ) ) {
			$mapped = $this->meta_input_aliases[ $input_key ];

			if ( isset( $this->meta_field_map[ $mapped ] ) ) {
				return $this->meta_field_map[ $mapped ];
			}

			if ( in_array( $mapped, $this->meta_field_map, true ) ) {
				return $mapped;
			}
		}

		if ( isset( $this->meta_field_map[ $input_key ] ) ) {
			return $this->meta_field_map[ $input_key ];
		}

		if ( in_array( $input_key, $this->meta_field_map, true ) ) {
			return $input_key;
		}

		return null;
	}

	/**
	 * Persist mapped taxonomy terms from request payload.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request.
	 * @return void
	 */
	protected function persist_taxonomy_fields( int $post_id, WP_REST_Request $request ): void {
		foreach ( $this->taxonomy_field_map as $request_key => $taxonomy ) {
			if ( ! $request->has_param( $request_key ) ) {
				continue;
			}

			$term_refs = $request->get_param( $request_key );
			if ( ! is_array( $term_refs ) ) {
				$term_refs = array();
			}

			$term_ids = Cpt_Helper::resolve_term_ids_for_taxonomy( $taxonomy, $term_refs );
			wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
		}

		$tax_payload = $request->get_param( 'taxonomies' );
		if ( ! is_array( $tax_payload ) ) {
			return;
		}

		$allowed_taxonomies = $this->get_allowed_taxonomies();

		foreach ( $tax_payload as $taxonomy => $term_refs ) {
			if ( ! is_string( $taxonomy ) || ! in_array( $taxonomy, $allowed_taxonomies, true ) ) {
				continue;
			}

			if ( ! is_array( $term_refs ) ) {
				$term_refs = array( $term_refs );
			}

			$term_ids = Cpt_Helper::resolve_term_ids_for_taxonomy( $taxonomy, $term_refs );
			wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
		}
	}

	/**
	 * Resolve and cache allowed taxonomies for this controller.
	 *
	 * @return array<int, string>
	 */
	protected function get_allowed_taxonomies(): array {
		if ( ! empty( $this->allowed_taxonomies ) ) {
			return $this->allowed_taxonomies;
		}

		$taxonomies               = array_values( array_unique( array_values( $this->taxonomy_field_map ) ) );
		$this->allowed_taxonomies = array_values( array_filter( $taxonomies, 'is_string' ) );

		return $this->allowed_taxonomies;
	}

	/**
	 * Apply featured media assignment if provided in request.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request.
	 * @return void
	 */
	protected function maybe_set_featured_media( int $post_id, WP_REST_Request $request ): void {
		if ( ! $request->has_param( 'featured_media' ) ) {
			return;
		}

		$featured_media_id = (int) $request->get_param( 'featured_media' );

		if ( $featured_media_id <= 0 ) {
			delete_post_thumbnail( $post_id );
			return;
		}

		set_post_thumbnail( $post_id, $featured_media_id );
	}

	/**
	 * Build meta payload for response output.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	protected function get_meta_payload( int $post_id ): array {
		$payload = array();

		foreach ( $this->meta_field_map as $request_key => $meta_key ) {
			$value = get_post_meta( $post_id, $meta_key, true );

			if ( 'rt-media-meta-img' === $meta_key ) {
				$image_ids            = Cpt_Helper::resolve_image_attachment_ids( $value );
				$payload[ $meta_key ] = Cpt_Helper::image_ids_to_urls( $image_ids );
				continue;
			}

			if ( 'rating' === $request_key && '' !== $value ) {
				$payload[ $meta_key ] = round( (float) $value, 1 );
				continue;
			}

			if ( 'runtime' === $request_key && '' !== $value ) {
				$payload[ $meta_key ] = (int) $value;
				continue;
			}

			$payload[ $meta_key ] = Cpt_Helper::meta_to_string_or_null( $value );
		}

		return $payload;
	}

	/**
	 * Build taxonomy term ID payload for response output.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, array<int, int>>
	 */
	protected function get_taxonomy_payload( int $post_id ): array {
		$payload = array();

		foreach ( $this->get_allowed_taxonomies() as $taxonomy ) {
			$term_ids             = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			$payload[ $taxonomy ] = ( is_wp_error( $term_ids ) || ! is_array( $term_ids ) ) ? array() : array_values( array_map( 'intval', $term_ids ) );
		}

		return $payload;
	}

	/**
	 * Validate that current request has write permissions.
	 *
	 * @return bool|WP_Error
	 */
	protected function check_write_permissions() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rt_rest_unauthorized', __( 'Authentication is required.', 'rt-movie-library' ), array( 'status' => rest_authorization_required_code() ) );
		}

		if ( 0 === did_action( 'application_password_did_authenticate' ) ) {
			return new WP_Error( 'rt_rest_app_password_required', __( 'Application Password authentication is required for write operations.', 'rt-movie-library' ), array( 'status' => 401 ) );
		}

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return new WP_Error( 'rt_rest_forbidden', __( 'Only editors or administrators can perform this operation.', 'rt-movie-library' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Return additional writable args for child controllers.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_additional_write_args(): array {
		return array();
	}

	/**
	 * Return additional response schema for child controllers.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_additional_response_schema(): array {
		return array();
	}

	/**
	 * Persist resource-specific fields in child controllers.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	protected function persist_additional_fields( int $post_id, WP_REST_Request $request ) {
		unset( $post_id, $request );
		return true;
	}

	/**
	 * Build resource-specific response payload in child controllers.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	protected function get_additional_response_payload( int $post_id ): array {
		unset( $post_id );
		return array();
	}
}
