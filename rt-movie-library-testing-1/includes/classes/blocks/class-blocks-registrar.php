<?php
/**
 * Gutenberg block registrar for RT Movie Library.
 *
 * This file is the single entry point for block registration in the plugin.
 * It maps each compiled `build/{block}` folder to its server-side render
 * class so editor registration and frontend rendering stay synchronized.
 * Keeping this mapping centralized avoids scattered `register_block_type()`
 * calls across unrelated classes and makes future block additions predictable.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Blocks;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Blocks_Registrar
 *
 * Centralizes block registration so metadata paths and render callbacks stay
 * discoverable in one place as more blocks are added.
 */
class Blocks_Registrar {

	use Singleton;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
	}

	/**
	 * Prepend the custom RT Movie Library plugin category to the block inserter.
	 *
	 * @param array<int,array<string,string|null>> $categories Existing block categories.
	 *
	 * @return array<int,array<string,string|null>>
	 */
	public function register_block_category( array $categories ): array {
		return array_merge(
			array(
				array(
					'slug'  => 'rt-movie-library',
					'title' => __( 'RT Movie Library', 'rt-movie-library' ),
					'icon'  => null,
				),
			),
			$categories
		);
	}

	/**
	 * Register dynamic blocks from built metadata.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		$build_dir = RT_MOVIE_LIBRARY_PATH . 'build/';

		register_block_type(
			$build_dir . 'movies',
			array(
				'render_callback' => array( Movies_Block::get_instance(), 'render' ),
			)
		);

		register_block_type(
			$build_dir . 'movie',
			array(
				'render_callback' => array( Movie_Block::get_instance(), 'render' ),
			)
		);

		register_block_type(
			$build_dir . 'persons',
			array(
				'render_callback' => array( Persons_Block::get_instance(), 'render' ),
			)
		);

		register_block_type(
			$build_dir . 'person',
			array(
				'render_callback' => array( Person_Block::get_instance(), 'render' ),
			)
		);
	}
}
