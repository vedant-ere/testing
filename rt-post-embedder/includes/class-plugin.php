<?php
/**
 * Main plugin bootstrap.
 *
 * @package RT_Post_Embedder
 */

namespace RT_Post_Embedder;

use RT_Post_Embedder\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 */
class Plugin {

	use Singleton;

	/**
	 * Wire subsystem services.
	 */
	protected function __construct() {
		Custom_Posts_Cpt::get_instance();
		Gutenberg_Controller::get_instance();
		Rest_Controller::get_instance();
		Block_Registrar::get_instance();
		Sync_Handler::get_instance();
		Admin_Columns::get_instance();
		Pre_Publish_Checker::get_instance();
		Template_Controller::get_instance();
	}
}
