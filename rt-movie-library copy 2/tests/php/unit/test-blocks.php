<?php
/**
 * Tests for Gutenberg movie/person block behavior.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Tests\Unit;

use RT_Movie_Library\Classes\Blocks\Movie_Block;
use RT_Movie_Library\Classes\Blocks\Movies_Block;
use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * Verifies block registration and dynamic render output.
 */
class Test_Blocks extends WP_UnitTestCase {

	/**
	 * Boot plugin registration hooks needed by block tests.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		\RT_Movie_Library\Classes\Plugin::get_instance()->register();
		do_action( 'init' );
	}

	/**
	 * Assign a genre and force publish so movie posts are usable in tests.
	 *
	 * @param int $post_id Movie post ID.
	 * @return void
	 */
	private function assign_genre( int $post_id ): void {
		$genre_id = $this->factory->term->create(
			array(
				'taxonomy' => 'rt-movie-genre',
				'name'     => 'Block Test Genre',
			)
		);

		if ( is_wp_error( $genre_id ) ) {
			$term     = get_term_by( 'name', 'Block Test Genre', 'rt-movie-genre' );
			$genre_id = $term ? $term->term_id : 0;
		}

		if ( 0 < $genre_id ) {
			wp_set_object_terms( $post_id, array( $genre_id ), 'rt-movie-genre' );
		}

		// The movie save hook can draft posts without a required genre.
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Ensure all expected blocks are registered with callable render callbacks.
	 *
	 * @return void
	 */
	public function test_blocks_are_registered(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$expected_blocks = array(
			'rt-movie-library/movies',
			'rt-movie-library/movie',
			'rt-movie-library/persons',
			'rt-movie-library/person',
		);

		foreach ( $expected_blocks as $block_slug ) {
			$this->assertTrue(
				$registry->is_registered( $block_slug ),
				"Block {$block_slug} should be registered."
			);

			$block_type = $registry->get_registered( $block_slug );
			$this->assertIsCallable( $block_type->render_callback, "Block {$block_slug} should have a valid render callback." );
		}
	}

	/**
	 * Validate movies block CRUD behavior through rendered output.
	 *
	 * @return void
	 */
	public function test_movies_block_render_crud_interaction(): void {
		$movies_block = Movies_Block::get_instance();

		$html_empty = $movies_block->render( array() );
		$this->assertStringContainsString( 'No movies found', $html_empty );

		$movie_id_1 = $this->factory->post->create(
			array(
				'post_type'   => 'rt-movie',
				'post_title'  => 'Test Movie Alpha',
				'post_status' => 'publish',
			)
		);
		$this->assign_genre( $movie_id_1 );

		$movie_id_2 = $this->factory->post->create(
			array(
				'post_type'   => 'rt-movie',
				'post_title'  => 'Test Movie Beta',
				'post_status' => 'publish',
			)
		);
		$this->assign_genre( $movie_id_2 );

		$html_with_movies = $movies_block->render( array() );
		$this->assertStringContainsString( 'Test Movie Alpha', $html_with_movies );
		$this->assertStringContainsString( 'Test Movie Beta', $html_with_movies );
		$this->assertStringContainsString( 'movie-grid', $html_with_movies );

		$html_filtered = $movies_block->render( array( '_post_ids' => array( $movie_id_1 ) ) );
		$this->assertStringContainsString( 'Test Movie Alpha', $html_filtered );
		$this->assertStringNotContainsString( 'Test Movie Beta', $html_filtered );

		wp_update_post(
			array(
				'ID'         => $movie_id_2,
				'post_title' => 'Test Movie Beta v2',
			)
		);
		$html_updated_movies = $movies_block->render( array() );
		$this->assertStringContainsString( 'Test Movie Beta v2', $html_updated_movies );
		$this->assertStringNotContainsString( 'Test Movie Beta</', $html_updated_movies );

		wp_delete_post( $movie_id_1, true );
		$html_after_delete = $movies_block->render( array() );
		$this->assertStringNotContainsString( 'Test Movie Alpha', $html_after_delete );
	}

	/**
	 * Validate single-movie block rendering for empty and populated states.
	 *
	 * @return void
	 */
	public function test_single_movie_block_render(): void {
		$movie_block = Movie_Block::get_instance();

		$html_empty = $movie_block->render( array( 'movieId' => 0 ) );
		$this->assertStringContainsString( 'Select a movie', $html_empty );

		$movie_id = $this->factory->post->create(
			array(
				'post_type'   => 'rt-movie',
				'post_title'  => 'Single Block Movie Focus',
				'post_status' => 'publish',
			)
		);
		$this->assign_genre( $movie_id );

		$html_rendered = $movie_block->render( array( 'movieId' => $movie_id ) );
		$this->assertStringContainsString( 'Single Block Movie Focus', $html_rendered );
	}

	/**
	 * Validate persons archive block rendering.
	 *
	 * @return void
	 */
	public function test_persons_block_render(): void {
		$persons_block = \RT_Movie_Library\Classes\Blocks\Persons_Block::get_instance();

		$person_id = $this->factory->post->create(
			array(
				'post_type'  => 'rt-person',
				'post_title' => 'Director John Doe',
			)
		);

		$html = $persons_block->render( array() );
		$this->assertStringContainsString( 'Director John Doe', $html );
		$this->assertStringContainsString( 'person-list', $html );
	}

	/**
	 * Validate single-person block rendering.
	 *
	 * @return void
	 */
	public function test_person_block_render(): void {
		$person_block = \RT_Movie_Library\Classes\Blocks\Person_Block::get_instance();

		$person_id = $this->factory->post->create(
			array(
				'post_type'  => 'rt-person',
				'post_title' => 'Featured Legend',
			)
		);

		$html = $person_block->render( array( 'personId' => $person_id ) );
		$this->assertStringContainsString( 'Featured Legend', $html );
		$this->assertStringContainsString( 'person-card', $html );
	}
}
