<?php
/**
 * Main Plugin Bootstrap Class.
 *
 * Responsible for initializing the plugin, registering core services,
 * and coordinating the loading of post types, taxonomies, and other
 * plugin components.
 *
 * This class acts as the single entry point for setting up
 * the RT Movie Library plugin.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes;

use RT_Movie_Library\Traits\Singleton;

use RT_Movie_Library\Classes\Post_Types\Movie;
use RT_Movie_Library\Classes\Post_Types\Person;

use RT_Movie_Library\Classes\Taxonomies\Career;
use RT_Movie_Library\Classes\Taxonomies\Genre;
use RT_Movie_Library\Classes\Taxonomies\Label;
use RT_Movie_Library\Classes\Taxonomies\Language;
use RT_Movie_Library\Classes\Taxonomies\Production_Company;
use RT_Movie_Library\Classes\Taxonomies\Movie_Tag;
use RT_Movie_Library\Classes\Taxonomies\Movie_Person;

use RT_Movie_Library\Classes\Meta_Boxes\Movie_Meta_Box;
use RT_Movie_Library\Classes\Meta_Boxes\Movie_Crew_Meta_Box;

use RT_Movie_Library\Classes\Meta_Boxes\Person_Basic_Meta_Box;
use RT_Movie_Library\Classes\Meta_Boxes\Person_Social_Meta_Box;

use RT_Movie_Library\Classes\Meta_Boxes\Media_Image_Meta_Box;
use RT_Movie_Library\Classes\Meta_Boxes\Media_Video_Meta_Box;
use RT_Movie_Library\Classes\Meta_Boxes\Movie_Poster_Meta_Box;

use RT_Movie_Library\Classes\Shortcodes\Movie_Shortcode;
use RT_Movie_Library\Classes\Shortcodes\Person_Shortcode;

use RT_Movie_Library\Helpers\Admin_Filters;
use RT_Movie_Library\Classes\Settings;



defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin.
 *
 * Acts as the central orchestrator for the plugin by bootstrapping
 * and registering all custom post types, taxonomies, and internal
 * relationships required by the RT Movie Library.
 */
class Plugin {


	use Singleton;

	/**
	 * Initializes the plugin by registering the main hook
	 * used to load all plugin features.
	 *
	 * The constructor is protected to enforce the Singleton pattern.
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_action(
			'wp_enqueue_scripts',
			array( $this, 'enqueue_frontend_assets' )
		);
	}
	/**
	 * Enqueue frontend assets for shortcodes.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {

		// Movie shortcode styles.
		wp_enqueue_style(
			'rt-movie-shortcode',
			RT_MOVIE_LIBRARY_URL . 'assets/css/admin/movie-shortcode.css',
			array(),
			RT_MOVIE_LIBRARY_VERSION
		);

		// Person shortcode styles.
		wp_enqueue_style(
			'rt-person-shortcode',
			RT_MOVIE_LIBRARY_URL . 'assets/css/admin/person-shortcode.css',
			array(),
			RT_MOVIE_LIBRARY_VERSION
		);
	}


	/**
	 * Registers all plugin features with WordPress.
	 *
	 * This includes custom post types, public taxonomies,
	 * and internal shadow taxonomies required for managing
	 * movie-related data and relationships.
	 *
	 * @return void
	 */
	public function register() {
		Movie::get_instance()->register();
		Person::get_instance()->register();

		Career::get_instance()->register();
		Genre::get_instance()->register();
		Label::get_instance()->register();
		Production_Company::get_instance()->register();
		Language::get_instance()->register();

		Movie_Tag::get_instance()->register();
		Movie_Person::get_instance()->register();
		Movie_Meta_Box::get_instance();
		Movie_Crew_Meta_Box::get_instance();
		Person_Basic_Meta_Box::get_instance();
		Person_Social_Meta_Box::get_instance();

		Media_Image_Meta_Box::get_instance();
		Media_Video_Meta_Box::get_instance();
		Movie_Poster_Meta_Box::get_instance();

		Movie_Shortcode::get_instance();
		Person_Shortcode::get_instance();

		Admin_Filters::init();
		Settings::get_instance();
	}
}
