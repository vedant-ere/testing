<?php
/**
 * REST endpoint bootstrap.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Rest;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Cpt_Endpoints
 */
class Cpt_Endpoints {

	use Singleton;

	/**
	 * Bootstraps route registration.
	 */
	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all custom routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		( new Movie_Controller() )->register_routes();
		( new Person_Controller() )->register_routes();
	}
}
