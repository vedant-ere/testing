<?php
/**
 * Person Post Type.
 *
 * Registers the `rt-person` custom post type.
 *
 * Field Mapping:
 * - Title     → Name
 * - Content   → Biography
 * - Thumbnail → Profile Picture
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Post_Types;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Person.
 */
class Person {

	use Singleton;

	/**
	 * Registers the "Person" custom post type with labels,
	 * supported editor features, rewrite rules, and REST API support.
	 *
	 * @return void
	 */
	public function register() {

		$labels = array(
			'name'                  => __( 'People', 'rt-movie-library' ),
			'singular_name'         => __( 'Person', 'rt-movie-library' ),
			'menu_name'             => __( 'People', 'rt-movie-library' ),
			'name_admin_bar'        => __( 'Person', 'rt-movie-library' ),
			'add_new'               => __( 'Add New', 'rt-movie-library' ),
			'add_new_item'          => __( 'Add New Person', 'rt-movie-library' ),
			'edit_item'             => __( 'Edit Person', 'rt-movie-library' ),
			'new_item'              => __( 'New Person', 'rt-movie-library' ),
			'view_item'             => __( 'View Person', 'rt-movie-library' ),
			'view_items'            => __( 'View People', 'rt-movie-library' ),
			'search_items'          => __( 'Search People', 'rt-movie-library' ),
			'not_found'             => __( 'No people found.', 'rt-movie-library' ),
			'not_found_in_trash'    => __( 'No people found in Trash.', 'rt-movie-library' ),
			'all_items'             => __( 'All People', 'rt-movie-library' ),
			'archives'              => __( 'People Archives', 'rt-movie-library' ),
			'attributes'            => __( 'Person Attributes', 'rt-movie-library' ),
			'insert_into_item'      => __( 'Insert into person', 'rt-movie-library' ),
			'uploaded_to_this_item' => __( 'Uploaded to this person', 'rt-movie-library' ),
		);

		$args = array(
			'labels'        => $labels,
			'public'        => true,
			'show_in_rest'  => true,

			'menu_icon'     => 'dashicons-admin-users',
			'menu_position' => 25,

			'has_archive'   => true,
			'rewrite'       => array(
				'slug'       => 'rt-person',
				'with_front' => false,
			),

			/**
			 * Editor support as per assignment requirements.
			 */
			'supports'      => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				'author',
			),
		);

		register_post_type( 'rt-person', $args );
	}
}
