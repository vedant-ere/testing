<?php
/**
 * Career Taxonomy for Persons.
 *
 * Registers the `rt-person-career` hierarchical taxonomy.
 *
 * Example terms:
 * - Director
 * - Producer
 * - Writer
 * - Actor / Star
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Taxonomies;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Career.
 */
class Career {

	use Singleton;

	/**
	 * Register Career taxonomy.
	 *
	 * @return void
	 */
	public function register() {

		$labels = array(
			'name'              => __( 'Careers', 'rt-movie-library' ),
			'singular_name'     => __( 'Career', 'rt-movie-library' ),
			'search_items'      => __( 'Search Careers', 'rt-movie-library' ),
			'all_items'         => __( 'All Careers', 'rt-movie-library' ),
			'parent_item'       => __( 'Parent Career', 'rt-movie-library' ),
			'parent_item_colon' => __( 'Parent Career:', 'rt-movie-library' ),
			'edit_item'         => __( 'Edit Career', 'rt-movie-library' ),
			'update_item'       => __( 'Update Career', 'rt-movie-library' ),
			'add_new_item'      => __( 'Add New Career', 'rt-movie-library' ),
			'new_item_name'     => __( 'New Career Name', 'rt-movie-library' ),
			'menu_name'         => __( 'Careers', 'rt-movie-library' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'person-career',
				'with_front' => false,
			),
		);

		register_taxonomy(
			'rt-person-career',
			array( 'rt-person' ),
			$args
		);
	}
}
