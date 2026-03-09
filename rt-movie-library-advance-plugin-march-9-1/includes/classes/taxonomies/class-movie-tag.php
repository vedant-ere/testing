<?php
/**
 * Movie Tags Taxonomy Registration.
 *
 * Defines and registers a non-hierarchical "Tag" taxonomy for the
 * Movie (`rt-movie`) custom post type, similar to default WordPress
 * post tags, enabling flexible, free-form tagging of movies.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Taxonomies;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Tag.
 *
 * Handles registration and configuration of the Movie Tag taxonomy,
 * including labels, non-hierarchical behavior, admin visibility,
 * rewrite rules, and REST API exposure for Movie posts.
 */
class Movie_Tag {

	use Singleton;

	/**
	 * Registers the "Movie Tag" taxonomy with WordPress.
	 *
	 * Configures a non-hierarchical taxonomy with tag-like behavior,
	 * admin UI support, rewrite rules, and REST API exposure for
	 * the Movie post type.
	 *
	 * @return void
	 */
	public function register() {

		$labels = array(
			'name'                       => __( 'Movie Tags', 'rt-movie-library' ),
			'singular_name'              => __( 'Movie Tag', 'rt-movie-library' ),
			'search_items'               => __( 'Search Movie Tags', 'rt-movie-library' ),
			'popular_items'              => __( 'Popular Movie Tags', 'rt-movie-library' ),
			'all_items'                  => __( 'All Movie Tags', 'rt-movie-library' ),
			'edit_item'                  => __( 'Edit Movie Tag', 'rt-movie-library' ),
			'update_item'                => __( 'Update Movie Tag', 'rt-movie-library' ),
			'add_new_item'               => __( 'Add New Movie Tag', 'rt-movie-library' ),
			'new_item_name'              => __( 'New Movie Tag Name', 'rt-movie-library' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'rt-movie-library' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'rt-movie-library' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'rt-movie-library' ),
			'menu_name'                  => __( 'Movie Tags', 'rt-movie-library' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,

			'rewrite'           => array(
				'slug'       => 'movie-tag',
				'with_front' => false,
			),
		);

		register_taxonomy(
			'rt-movie-tag',
			array( 'rt-movie' ),
			$args
		);
	}
}
