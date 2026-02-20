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
			'labels'        => $labels,
			'public'        => true,
			'show_in_rest'  => true,

			'menu_icon'     => 'dashicons-video-alt2',
			'menu_position' => 24,

			'has_archive'   => true,
			'rewrite'       => array(
				'slug'       => 'movies',
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
				'comments',
			),
		);

		register_post_type( 'rt-movie', $args );
	}
}
