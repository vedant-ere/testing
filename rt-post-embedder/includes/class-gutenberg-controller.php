<?php
/**
 * Gutenberg availability controller.
 *
 * @package RT_Post_Embedder
 */

namespace RT_Post_Embedder;

use RT_Post_Embedder\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Gutenberg_Controller
 */
class Gutenberg_Controller {

	use Singleton;

	/**
	 * Register filters.
	 */
	protected function __construct() {
		add_filter(
			'use_block_editor_for_post_type',
			array( $this, 'allow_only_custom_posts_editor' ),
			10,
			2
		);
	}

	/**
	 * Enable Gutenberg only for the assignment CPT.
	 *
	 * Keeping this centralized avoids per-screen conditions and guarantees
	 * that every other type falls back to the classic editing experience.
	 *
	 * @param bool   $use_block_editor Current filter value.
	 * @param string $post_type        Target post type.
	 * @return bool
	 */
	public function allow_only_custom_posts_editor( bool $use_block_editor, string $post_type ): bool {
		if ( Custom_Posts_Cpt::POST_TYPE === $post_type ) {
			return $use_block_editor;
		}

		return false;
	}
}
