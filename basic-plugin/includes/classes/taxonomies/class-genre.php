<?php
/**
 * Genre Taxonomy Registration.
 *
 * Defines and registers the "Genre" taxonomy for the Movie (`rt-movie`) CPT
 * enabling categorization of movies by genre
 * within the WordPress admin and REST API.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Taxonomies;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Genre.
 *
 * Handles registration and configuration of the Genre taxonomy,
 * including labels, hierarchy, admin visibility, rewrite rules,
 * and REST API exposure for Movie posts.
 */
class Genre {

	use Singleton;

	/**
	 * Registers the "Genre" taxonomy with WordPress.
	 *
	 * Configures labels, hierarchical behavior, admin UI visibility,
	 * rewrite rules, and REST API support for the Movie post type.
	 *
	 * @return void
	 */
	public function register() {
		register_taxonomy(
			'rt-movie-genre',
			array( 'rt-movie' ),
			array(
				'labels'            => array(
					'name'              => __( 'Genres', 'rt-movie-library' ),
					'singular_name'     => __( 'Genre', 'rt-movie-library' ),
					'search_items'      => __( 'Search Genres', 'rt-movie-library' ),
					'all_items'         => __( 'All Genres', 'rt-movie-library' ),
					'parent_item'       => __( 'Parent Genre', 'rt-movie-library' ),
					'parent_item_colon' => __( 'Parent Genre:', 'rt-movie-library' ),
					'edit_item'         => __( 'Edit Genre', 'rt-movie-library' ),
					'update_item'       => __( 'Update Genre', 'rt-movie-library' ),
					'add_new_item'      => __( 'Add New Genre', 'rt-movie-library' ),
					'new_item_name'     => __( 'New Genre Name', 'rt-movie-library' ),
					'menu_name'         => __( 'Genres', 'rt-movie-library' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug' => 'movie-genre',
				),
			)
		);
	}
}
