<?php
/**
 * Admin list table column integration.
 *
 * @package RT_Post_Embedder
 */

namespace RT_Post_Embedder;

use RT_Post_Embedder\Traits\Singleton;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin_Columns
 */
class Admin_Columns {

	use Singleton;

	/**
	 * Column slug.
	 *
	 * @var string
	 */
	private const COLUMN_KEY = 'rt_pe_embedded_in';

	/**
	 * Mapping: row post ID => embedded-in custom-post IDs.
	 *
	 * @var array<int, int[]>
	 */
	private array $embedded_in_map = array();

	/**
	 * Mapping: custom-post ID => title/link payload.
	 *
	 * @var array<int, array{title:string,edit_link:string}>
	 */
	private array $custom_post_lookup = array();

	/**
	 * Register hooks.
	 */
	protected function __construct() {
		add_action( 'admin_init', array( $this, 'register_column_hooks' ) );
		add_filter( 'the_posts', array( $this, 'prime_list_table_cache' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Admin hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'edit.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'rt-pe-admin',
			RT_POST_EMBEDDER_URL . 'assets/css/admin.css',
			array(),
			RT_POST_EMBEDDER_VERSION
		);
	}

	/**
	 * Register column hooks for all UI post types.
	 *
	 * @return void
	 */
	public function register_column_hooks(): void {
		$post_types = get_post_types( array( 'show_ui' => true ), 'names' );
		unset( $post_types['attachment'] );

		foreach ( $post_types as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_column' ), 10, 2 );
		}
	}

	/**
	 * Add Embedded in column.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public function add_column( array $columns ): array {
		$columns[ self::COLUMN_KEY ] = __( 'Embedded in', 'rt-post-embedder' );
		return $columns;
	}

	/**
	 * Prime relation caches for current list table query.
	 *
	 * @param WP_Post[] $posts Query posts.
	 * @param WP_Query  $query Query object.
	 * @return WP_Post[]
	 */
	public function prime_list_table_cache( array $posts, WP_Query $query ): array {
		if ( ! is_admin() || ! $query->is_main_query() || empty( $posts ) ) {
			return $posts;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen instanceof \WP_Screen || 'edit' !== $screen->base ) {
			return $posts;
		}

		$row_post_ids = array_values( array_filter( array_map( 'absint', wp_list_pluck( $posts, 'ID' ) ) ) );
		if ( empty( $row_post_ids ) ) {
			return $posts;
		}

		update_meta_cache( 'post', $row_post_ids );

		$all_embedding_ids = array();
		foreach ( $row_post_ids as $row_post_id ) {
			$embedded_ids = $this->get_embedded_ids_from_meta( $row_post_id );

			$this->embedded_in_map[ $row_post_id ] = $embedded_ids;

			$all_embedding_ids = array_merge( $all_embedding_ids, $embedded_ids );
		}

		$all_embedding_ids = array_values( array_unique( array_filter( array_map( 'absint', $all_embedding_ids ) ) ) );
		if ( empty( $all_embedding_ids ) ) {
			return $posts;
		}

		$custom_posts = new WP_Query(
			array(
				'post_type'              => Custom_Posts_Cpt::POST_TYPE,
				'post__in'               => $all_embedding_ids,
				'post_status'            => get_post_stati( array(), 'names' ),
				'posts_per_page'         => count( $all_embedding_ids ),
				'no_found_rows'          => true,
				'orderby'                => 'post__in',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$this->custom_post_lookup = array();
		foreach ( $custom_posts->posts as $custom_post ) {
			if ( ! $custom_post instanceof WP_Post ) {
				continue;
			}

			$custom_post_id = (int) $custom_post->ID;
			$edit_link      = get_edit_post_link( $custom_post_id );
			$title          = (string) get_the_title( $custom_post );

			if ( ! $edit_link ) {
				continue;
			}

			$this->custom_post_lookup[ $custom_post_id ] = array(
				'title'     => $title,
				'edit_link' => (string) $edit_link,
			);
		}

		return $posts;
	}

	/**
	 * Render column value.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Current post ID.
	 * @return void
	 */
	public function render_column( string $column, int $post_id ): void {
		if ( self::COLUMN_KEY !== $column ) {
			return;
		}

		$embedded_ids = $this->embedded_in_map[ $post_id ] ?? $this->get_embedded_ids_from_meta( $post_id );

		if ( empty( $embedded_ids ) ) {
			echo '&mdash;';
			return;
		}

		$links = array();
		foreach ( $embedded_ids as $embedded_id ) {
			$custom_post_data = $this->custom_post_lookup[ $embedded_id ] ?? null;

			if ( ! is_array( $custom_post_data ) ) {
				continue;
			}

			$links[] = sprintf(
				'<a href="%1$s" title="%2$s">#%3$d: %4$s</a>',
				esc_url( $custom_post_data['edit_link'] ),
				esc_attr( $custom_post_data['title'] ),
				(int) $embedded_id,
				esc_html( $custom_post_data['title'] )
			);
		}

		if ( empty( $links ) ) {
			echo '&mdash;';
			return;
		}

		echo wp_kses(
			implode( '<br />', $links ),
			array(
				'a'  => array(
					'href'  => array(),
					'title' => array(),
				),
				'br' => array(),
			)
		);
	}

	/**
	 * Read embedded relation IDs for one post.
	 *
	 * @param int $post_id Source post ID.
	 * @return int[]
	 */
	private function get_embedded_ids_from_meta( int $post_id ): array {
		$meta = get_post_meta( $post_id, Sync_Handler::META_KEY_EMBEDDED_IN, true );

		if ( ! is_array( $meta ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'absint', $meta ) ) );
	}
}
