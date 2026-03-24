<?php
/**
 * Movie Label Taxonomy Registration.
 *
 * Defines and registers the "Label" taxonomy for the Movie (`rt-movie`)
 * custom post type, enabling movies to be grouped by status or
 * display context such as Featured, Trending, or Slider.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Taxonomies;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Label.
 *
 * Handles registration and configuration of the Label taxonomy,
 * including labels, hierarchy, admin visibility, rewrite rules,
 * and REST API exposure for Movie posts.
 */
class Label {

	use Singleton;

	/**
	 * Registers the "Label" taxonomy with WordPress.
	 *
	 * Configures labels, hierarchical behavior, admin UI visibility,
	 * rewrite rules, and REST API support for the Movie post type.
	 *
	 * @return void
	 */
	public function register() {

		$labels = array(
			'name'              => __( 'Labels', 'rt-movie-library' ),
			'singular_name'     => __( 'Label', 'rt-movie-library' ),
			'search_items'      => __( 'Search Labels', 'rt-movie-library' ),
			'all_items'         => __( 'All Labels', 'rt-movie-library' ),
			'parent_item'       => __( 'Parent Label', 'rt-movie-library' ),
			'parent_item_colon' => __( 'Parent Label:', 'rt-movie-library' ),
			'edit_item'         => __( 'Edit Label', 'rt-movie-library' ),
			'update_item'       => __( 'Update Label', 'rt-movie-library' ),
			'add_new_item'      => __( 'Add New Label', 'rt-movie-library' ),
			'new_item_name'     => __( 'New Label Name', 'rt-movie-library' ),
			'menu_name'         => __( 'Labels', 'rt-movie-library' ),
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
				'slug'       => 'movie-label',
				'with_front' => false,
			),
		);

		register_taxonomy(
			'rt-movie-label',
			array( 'rt-movie' ),
			$args
		);
	}
}
