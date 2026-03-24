<?php
/**
 * Production Company Taxonomy Registration.
 *
 * Defines and registers the "Production Company" taxonomy for the
 * Movie (`rt-movie`) custom post type, allowing movies to be grouped
 * and filtered by the studio or company responsible for production.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Taxonomies;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Production_Company.
 *
 * Handles registration and configuration of the Production Company
 * taxonomy, including labels, hierarchical behavior, admin visibility,
 * rewrite rules, and REST API exposure for Movie posts.
 */
class Production_Company {

	use Singleton;

	/**
	 * Registers the "Production Company" taxonomy with WordPress.
	 *
	 * Configures a hierarchical taxonomy with admin UI support,
	 * rewrite rules, and REST API exposure for the Movie post type.
	 *
	 * @return void
	 */
	public function register() {

		$labels = array(
			'name'              => __( 'Production Companies', 'rt-movie-library' ),
			'singular_name'     => __( 'Production Company', 'rt-movie-library' ),
			'search_items'      => __( 'Search Production Companies', 'rt-movie-library' ),
			'all_items'         => __( 'All Production Companies', 'rt-movie-library' ),
			'parent_item'       => __( 'Parent Production Company', 'rt-movie-library' ),
			'parent_item_colon' => __( 'Parent Production Company:', 'rt-movie-library' ),
			'edit_item'         => __( 'Edit Production Company', 'rt-movie-library' ),
			'update_item'       => __( 'Update Production Company', 'rt-movie-library' ),
			'add_new_item'      => __( 'Add New Production Company', 'rt-movie-library' ),
			'new_item_name'     => __( 'New Production Company Name', 'rt-movie-library' ),
			'menu_name'         => __( 'Production Companies', 'rt-movie-library' ),
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
				'slug'       => 'production-company',
				'with_front' => false,
			),
		);

		register_taxonomy(
			'rt-movie-production-company',
			array( 'rt-movie' ),
			$args
		);
	}
}
