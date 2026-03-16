<?php
/**
 * Movie Post Type.
 *
 * Registers the `rt-movie` custom post type.
 *
 * Field Mapping:
 * - Title     → Movie Title
 * - Excerpt   → Synopsis / Description
 * - Content   → Plot
 * - Thumbnail → Movie Poster
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Post_Types;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie.
 */
class Movie {

	use Singleton;

	/**
	 * User meta key used as a one-time flag for Gutenberg notice.
	 *
	 * @var string
	 */
	private const NOTICE_USER_META_KEY = 'rt_genre_required_notice';

	/**
	 * REST field name exposing required-genre notice flag.
	 *
	 * @var string
	 */
	private const NOTICE_REST_FIELD = 'rt_genre_required_notice';

	/**
	 * Shared block-editor script handle for required taxonomy notices.
	 *
	 * @var string
	 */
	private const NOTICE_SCRIPT_HANDLE = 'rt-required-taxonomy-notice';

	/**
	 * Genre taxonomy slug for movies.
	 *
	 * @var string
	 */
	private const TAXONOMY_GENRE = 'rt-movie-genre';

	/**
	 * Bootstrap hooks.
	 */
	protected function __construct() {
		add_action( 'save_post_rt-movie', array( $this, 'validate_required_genre' ), 10, 2 );
		add_action( 'rest_api_init', array( $this, 'register_notice_rest_field' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_validation_notice_script' ) );
	}

	/**
	 * Registers the "Movie" custom post type with all labels,
	 * supported features, rewrite rules, and REST API support.
	 *
	 * @return void
	 */
	public function register() {

		$labels = array(
			'name'                  => __( 'Movies', 'rt-movie-library' ),
			'singular_name'         => __( 'Movie', 'rt-movie-library' ),
			'menu_name'             => __( 'Movies', 'rt-movie-library' ),
			'name_admin_bar'        => __( 'Movie', 'rt-movie-library' ),
			'add_new'               => __( 'Add New', 'rt-movie-library' ),
			'add_new_item'          => __( 'Add New Movie', 'rt-movie-library' ),
			'edit_item'             => __( 'Edit Movie', 'rt-movie-library' ),
			'new_item'              => __( 'New Movie', 'rt-movie-library' ),
			'view_item'             => __( 'View Movie', 'rt-movie-library' ),
			'view_items'            => __( 'View Movies', 'rt-movie-library' ),
			'search_items'          => __( 'Search Movies', 'rt-movie-library' ),
			'not_found'             => __( 'No movies found.', 'rt-movie-library' ),
			'not_found_in_trash'    => __( 'No movies found in Trash.', 'rt-movie-library' ),
			'all_items'             => __( 'All Movies', 'rt-movie-library' ),
			'archives'              => __( 'Movie Archives', 'rt-movie-library' ),
			'attributes'            => __( 'Movie Attributes', 'rt-movie-library' ),
			'insert_into_item'      => __( 'Insert into movie', 'rt-movie-library' ),
			'uploaded_to_this_item' => __( 'Uploaded to this movie', 'rt-movie-library' ),
			'featured_image'        => __( 'Movie Poster', 'rt-movie-library' ),
			'set_featured_image'    => __( 'Set Movie Poster', 'rt-movie-library' ),
			'remove_featured_image' => __( 'Remove Movie Poster', 'rt-movie-library' ),
			'use_featured_image'    => __( 'Use as Movie Poster', 'rt-movie-library' ),
		);

		$args = array(
			'labels'          => $labels,
			'public'          => true,
			'show_in_rest'    => true,
			'capability_type' => array( 'rt-movie', 'rt-movies' ),
			'map_meta_cap'    => true,

			'menu_icon'       => 'dashicons-video-alt2',
			'menu_position'   => 24,

			'has_archive'     => 'rt-movie',
			'rewrite'         => array(
				'slug'       => 'rt-movie',
				'with_front' => false,
			),

			/**
			 * Editor support as per assignment requirements.
			 */
			'supports'        => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				'author',
				'comments',
			),
		);

		register_post_type( 'rt-movie', $args );
	}

	/**
	 * Register REST field to expose one-time required-genre notice flag.
	 *
	 * @return void
	 */
	public function register_notice_rest_field(): void {
		register_rest_field(
			'rt-movie',
			self::NOTICE_REST_FIELD,
			array(
				'get_callback' => array( $this, 'get_required_genre_notice_flag' ),
				'schema'       => array(
					/* translators: REST schema field description for genre-required notice flag. */
					'description' => __( 'Indicates publish was blocked because Genre is required.', 'rt-movie-library' ),
					'type'        => 'boolean',
					'context'     => array( 'edit' ),
				),
			)
		);
	}

	/**
	 * Return and optionally clear the required-genre notice flag for current user.
	 *
	 * The flag is only cleared when the request explicitly includes
	 * `rt_notice_check=1` so save responses do not consume it prematurely.
	 *
	 * @param array<string,mixed> $prepared_object Prepared REST object.
	 * @param string              $field_name      REST field name.
	 * @param \WP_REST_Request    $request         Current REST request.
	 * @return bool
	 */
	public function get_required_genre_notice_flag( array $prepared_object, string $field_name, \WP_REST_Request $request ): bool {
		unset( $prepared_object, $field_name );

		$user_id = get_current_user_id();

		if ( $user_id < 1 ) {
			return false;
		}

		$flag = '1' === (string) get_user_meta( $user_id, self::NOTICE_USER_META_KEY, true );

		if ( $flag && '1' === (string) $request->get_param( 'rt_notice_check' ) ) {
			delete_user_meta( $user_id, self::NOTICE_USER_META_KEY );
		}

		return $flag;
	}

	/**
	 * Enqueue block-editor script that renders required-genre notice in Gutenberg.
	 *
	 * @return void
	 */
	public function enqueue_validation_notice_script(): void {
		$screen = get_current_screen();

		if ( ! $screen instanceof \WP_Screen || 'rt-movie' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			self::NOTICE_SCRIPT_HANDLE,
			RT_MOVIE_LIBRARY_URL . 'assets/js/admin/required-taxonomy-notice.js',
			array( 'wp-api-fetch', 'wp-data', 'wp-i18n', 'wp-notices', 'wp-url' ),
			RT_MOVIE_LIBRARY_VERSION,
			true
		);

		wp_set_script_translations( self::NOTICE_SCRIPT_HANDLE, 'rt-movie-library', RT_MOVIE_LIBRARY_PATH . 'languages' );

		wp_add_inline_script(
			self::NOTICE_SCRIPT_HANDLE,
			'window.rtRequiredTaxonomyNoticeConfig = ' . wp_json_encode(
				array(
					'postType'      => 'rt-movie',
					'restBase'      => 'rt-movie',
					'noticeField'   => self::NOTICE_REST_FIELD,
					'noticeMessage' => __( 'Genre is required to publish a Movie.', 'rt-movie-library' ),
					'noticeId'      => 'rt-genre-required-notice',
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Validate that a Movie has at least one Genre before publish.
	 *
	 * If missing, post is moved to draft.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function validate_required_genre( int $post_id, \WP_Post $post ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( 'rt-movie' !== $post->post_type ) {
			return;
		}

		if ( ! in_array( $post->post_status, array( 'publish', 'future', 'pending', 'private' ), true ) ) {
			return;
		}

		$terms = wp_get_object_terms( $post_id, self::TAXONOMY_GENRE, array( 'fields' => 'ids' ) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			$this->force_post_to_draft( $post_id );
		}
	}

	/**
	 * Force post status to draft.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function force_post_to_draft( int $post_id ): void {
		static $is_updating = false;

		if ( $is_updating ) {
			return;
		}

		$is_updating = true;

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);

		$is_updating = false;

		$user_id = get_current_user_id();

		if ( $user_id > 0 ) {
			update_user_meta( $user_id, self::NOTICE_USER_META_KEY, '1' );
		}
	}
}
