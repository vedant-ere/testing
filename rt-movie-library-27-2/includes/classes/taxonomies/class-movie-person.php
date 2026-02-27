<?php
/**
 * Movie–Person Shadow Taxonomy Registration.
 *
 * Defines and registers a non-public, internal taxonomy used to
 * associate Movie (`rt-movie`) posts with Person (`rt-person`) posts.
 *
 * This taxonomy is intended strictly for internal relationship
 * management and is not exposed via the admin UI or REST API.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Taxonomies;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Person.
 *
 * Handles registration of an internal shadow taxonomy that enables
 * linking Movies to Persons without exposing the relationship
 * to end users or editors.
 */
class Movie_Person {

	use Singleton;

	/**
	 * Registers the internal Movie–Person shadow taxonomy.
	 *
	 * Configures a private taxonomy with no UI or REST exposure,
	 * used exclusively for maintaining Movie-to-Person relationships.
	 *
	 * @return void
	 */
	public function register() {

		register_taxonomy(
			'_rt-movie-person',
			array( 'rt-movie' ),
			array(
				'public'       => false,
				'show_ui'      => false,
				'show_in_rest' => false,
				'rewrite'      => false,
				'query_var'    => false,
				'has_archive'  => false,
			)
		);
	}
}
