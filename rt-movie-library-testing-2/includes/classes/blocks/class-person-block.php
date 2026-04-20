<?php
/**
 * Server-side renderer for the `rt-movie-library/person` block.
 *
 * This file resolves one selected person ID and renders it through the shared
 * Persons block pipeline. Reusing the list renderer ensures single-person and
 * persons-list cards stay visually and semantically consistent as markup and
 * archive styling evolve.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Blocks;

use RT_Movie_Library\Traits\Singleton;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class Person_Block
 *
 * Handles server-side rendering for the single Person block.
 */
class Person_Block {

	use Singleton;

	/**
	 * Constructor.
	 */
	protected function __construct() {}

	/**
	 * Render single selected person.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render( array $attributes ): string {
		$person_id = absint( $attributes['personId'] ?? 0 );

		if ( $person_id < 1 ) {
			return '<p class="rt-person-empty">' .
				esc_html__( 'Select a person in block settings.', 'rt-movie-library' ) .
				'</p>';
		}

		$person_post = get_post( $person_id );

		if ( ! $person_post instanceof WP_Post || 'rt-person' !== $person_post->post_type ) {
			return '<p class="rt-person-empty">' .
				esc_html__( 'Selected person was not found.', 'rt-movie-library' ) .
				'</p>';
		}

		return Persons_Block::get_instance()->render(
			array(
				'count'         => 1,
				'_post_ids'     => array( $person_id ),
				'_show_heading' => false,
			)
		);
	}
}
