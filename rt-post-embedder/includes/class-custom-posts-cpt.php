<?php
/**
 * Custom Posts post type registration.
 *
 * @package RT_Post_Embedder
 */

namespace RT_Post_Embedder;

use RT_Post_Embedder\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Custom_Posts_Cpt
 */
class Custom_Posts_Cpt {

	use Singleton;

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	public const POST_TYPE = 'custom-post';

	/**
	 * Register hooks.
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register CPT.
	 *
	 * @return void
	 */
	public function register(): void {
		// phpcs:ignore WordPress.NamingConventions.ValidPostTypeSlug.NotStringLiteral -- Slug is centralized in a class constant used across services.
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => array(
					'name'               => __( 'Custom Posts', 'rt-post-embedder' ),
					'singular_name'      => __( 'Custom Post', 'rt-post-embedder' ),
					'add_new_item'       => __( 'Add New Custom Post', 'rt-post-embedder' ),
					'edit_item'          => __( 'Edit Custom Post', 'rt-post-embedder' ),
					'new_item'           => __( 'New Custom Post', 'rt-post-embedder' ),
					'view_item'          => __( 'View Custom Post', 'rt-post-embedder' ),
					'search_items'       => __( 'Search Custom Posts', 'rt-post-embedder' ),
					'not_found'          => __( 'No custom posts found.', 'rt-post-embedder' ),
					'not_found_in_trash' => __( 'No custom posts found in Trash.', 'rt-post-embedder' ),
					'all_items'          => __( 'All Custom Posts', 'rt-post-embedder' ),
				),
				'public'             => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'menu_icon'          => 'dashicons-layout',
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
				'has_archive'        => false,
				'rewrite'            => array( 'slug' => 'custom-posts' ),
				'publicly_queryable' => true,
			),
		);
	}
}
