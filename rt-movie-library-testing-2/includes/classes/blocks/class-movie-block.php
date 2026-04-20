<?php
/**
 * Server-side renderer for the `rt-movie-library/movie` block.
 *
 * This file handles single-movie rendering selected from block attributes.
 * It delegates presentation to the shared Movies renderer so list and single
 * variants use one markup pipeline and do not drift in styling or data rules.
 * That shared path reduces maintenance risk when archive card output changes.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Blocks;

use RT_Movie_Library\Traits\Singleton;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Block
 *
 * Handles server-side rendering for the single Movie block.
 */
class Movie_Block {

	use Singleton;

	/**
	 * Constructor.
	 */
	protected function __construct() {}

	/**
	 * Render single selected movie.
	 *
	 * Reusing Movies_Block keeps one render pipeline for movie card markup,
	 * which avoids drift between list and single variants over time.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render( array $attributes ): string {
		$movie_id = absint( $attributes['movieId'] ?? 0 );

		if ( $movie_id < 1 ) {
			return '<p class="rt-block-movie__placeholder">' .
				esc_html__( 'Select a movie in block settings.', 'rt-movie-library' ) .
				'</p>';
		}

		$movie_post = get_post( $movie_id );

		if ( ! $movie_post instanceof WP_Post || 'rt-movie' !== $movie_post->post_type ) {
			return '<p class="rt-block-movie__error">' .
				esc_html__( 'Selected movie was not found.', 'rt-movie-library' ) .
				'</p>';
		}

		return Movies_Block::get_instance()->render(
			array(
				'count'         => 1,
				'_post_ids'     => array( $movie_id ),
				'_show_heading' => false,
			)
		);
	}
}
