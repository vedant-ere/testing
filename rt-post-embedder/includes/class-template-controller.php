<?php
/**
 * Template fallback controller.
 *
 * @package RT_Post_Embedder
 */

namespace RT_Post_Embedder;

use RT_Post_Embedder\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Template_Controller
 */
class Template_Controller {

	use Singleton;

	/**
	 * Register hooks.
	 */
	protected function __construct() {
		add_filter( 'template_include', array( $this, 'maybe_override_single_template' ) );
	}

	/**
	 * Provide plugin fallback template for custom-post singles.
	 *
	 * The active theme currently has neither single.php nor a dedicated
	 * single-custom-post.php, so WordPress falls back to index.php which renders
	 * excerpts only. This fallback guarantees full `the_content()` rendering.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public function maybe_override_single_template( string $template ): string {
		if ( ! is_singular( Custom_Posts_Cpt::POST_TYPE ) ) {
			return $template;
		}

		$theme_template = locate_template(
			array(
				'single-' . Custom_Posts_Cpt::POST_TYPE . '.php',
				'single.php',
			)
		);

		if ( '' !== $theme_template ) {
			return $template;
		}

		$plugin_template = RT_POST_EMBEDDER_PATH . 'templates/single-custom-post.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}
}
