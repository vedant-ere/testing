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
use WP_Block_Type_Registry;

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

		$this->register_dynamic_block(
			'rt-movie-library/movies',
			$build_dir . 'movies',
			array( Movies_Block::get_instance(), 'render' )
		);

		$this->register_dynamic_block(
			'rt-movie-library/movie',
			$build_dir . 'movie',
			array( Movie_Block::get_instance(), 'render' )
		);

		$this->register_dynamic_block(
			'rt-movie-library/persons',
			$build_dir . 'persons',
			array( Persons_Block::get_instance(), 'render' )
		);

		$this->register_dynamic_block(
			'rt-movie-library/person',
			$build_dir . 'person',
			array( Person_Block::get_instance(), 'render' )
		);
	}

	/**
	 * Register a dynamic block from metadata when not already registered.
	 *
	 * Tests and some bootstrap paths can trigger `init` multiple times.
	 * Guarding by slug keeps registration idempotent and prevents
	 * "already registered" doing_it_wrong notices.
	 *
	 * @param string   $slug            Block name (namespace/slug).
	 * @param string   $metadata_path   Directory containing block.json.
	 * @param callable $render_callback Server-side render callback.
	 *
	 * @return void
	 */
	private function register_dynamic_block(
		string $slug,
		string $metadata_path,
		callable $render_callback
	): void {
		$registry = WP_Block_Type_Registry::get_instance();

		if ( $registry->is_registered( $slug ) ) {
			return;
		}

		register_block_type(
			$metadata_path,
			array(
				'render_callback' => $render_callback,
			)
		);
	}
}
