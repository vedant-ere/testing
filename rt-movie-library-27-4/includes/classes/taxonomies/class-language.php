<?php
/**
 * Movie Language Taxonomy Registration.
 *
 * Defines and registers the "Language" taxonomy for the Movie (`rt-movie`)
 * custom post type, enabling movies to be grouped by spoken or released
 * languages such as English, Hindi, etc.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Taxonomies;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Language.
 *
 * Handles registration and configuration of the Language taxonomy,
 * including labels, hierarchy, admin visibility, rewrite rules,
 * and REST API exposure for Movie posts.
 */
class Language {

	use Singleton;

	/**
	 * Registers the "Language" taxonomy with WordPress.
	 *
	 * Configures labels, hierarchical behavior, admin UI visibility,
	 * rewrite rules, and REST API support for the Movie post type.
	 *
	 * @return void
	 */
	public function register() {

		$labels = array(
			'name'              => __( 'Languages', 'rt-movie-library' ),
			'singular_name'     => __( 'Language', 'rt-movie-library' ),
			'search_items'      => __( 'Search Languages', 'rt-movie-library' ),
			'all_items'         => __( 'All Languages', 'rt-movie-library' ),
			'parent_item'       => __( 'Parent Language', 'rt-movie-library' ),
			'parent_item_colon' => __( 'Parent Language:', 'rt-movie-library' ),
			'edit_item'         => __( 'Edit Language', 'rt-movie-library' ),
			'update_item'       => __( 'Update Language', 'rt-movie-library' ),
			'add_new_item'      => __( 'Add New Language', 'rt-movie-library' ),
			'new_item_name'     => __( 'New Language Name', 'rt-movie-library' ),
			'menu_name'         => __( 'Languages', 'rt-movie-library' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => array(
				'slug' => 'movie-language',
			),
		);

		register_taxonomy(
			'rt-movie-language',
			array( 'rt-movie' ),
			$args
		);
	}
}
