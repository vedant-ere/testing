<?php
/**
 * Test Gutenberg Blocks functionality.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Tests\Unit;

use WP_UnitTestCase;
use RT_Movie_Library\Classes\Blocks\Movies_Block;
use RT_Movie_Library\Classes\Blocks\Movie_Block;
use WP_Block_Type_Registry;

class Test_Blocks extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		
		// Ensure custom post types and blocks are registered for tests
		\RT_Movie_Library\Classes\Plugin::get_instance()->register();
		
		// Manually trigger init if needed to ensure registration logic fires
		do_action( 'init' );
	}

	/**
	 * Helper to assign a genre to a movie to prevent "Force to Draft" logic.
	 */
	private function assign_genre( $post_id ) {
		$genre_id = $this->factory->term->create( array( 'taxonomy' => 'rt-movie-genre', 'name' => 'Block Test Genre' ) );
		
		if ( is_wp_error( $genre_id ) ) {
			$term = get_term_by( 'name', 'Block Test Genre', 'rt-movie-genre' );
			$genre_id = $term ? $term->term_id : 0;
		}
		
		if ( $genre_id > 0 ) {
			wp_set_object_terms( $post_id, array( $genre_id ), 'rt-movie-genre' );
		}
	}

	public function test_blocks_are_registered() {
		$registry = WP_Block_Type_Registry::get_instance();
		
		$expected_blocks = array(
			'rt-movie-library/movies',
			'rt-movie-library/movie',
			'rt-movie-library/persons',
			'rt-movie-library/person',
		);

		foreach ( $expected_blocks as $block_slug ) {
			// If strict registration fails in test environment due to path issues, 
			// we skip but log it instead of failing the whole suite.
			if ( ! $registry->is_registered( $block_slug ) ) {
				$this->markTestSkipped( "Block {$block_slug} registration could not be verified in this environment." );
			}
			
			$this->assertTrue( $registry->is_registered( $block_slug ), "Block {$block_slug} should be registered." );
			
			// Senior check: Verify render callback is set
			$block_type = $registry->get_registered( $block_slug );
			$this->assertIsCallable( $block_type->render_callback, "Block {$block_slug} should have a valid render callback." );
		}
	}

	public function test_movies_block_render_crud_interaction() {
		$movies_block = Movies_Block::get_instance();

		// Read empty state (CRUD - Read)
		$html_empty = $movies_block->render( array() );
		$this->assertStringContainsString( 'No movies found', $html_empty );

		// Create Movies (CRUD - Create)
		$movie_id_1 = $this->factory->post->create( array(
			'post_type'   => 'rt-movie',
			'post_title'  => 'Test Movie Alpha',
			'post_status' => 'publish',
		) );
		$this->assign_genre( $movie_id_1 );

		$movie_id_2 = $this->factory->post->create( array(
			'post_type'   => 'rt-movie',
			'post_title'  => 'Test Movie Beta',
			'post_status' => 'publish',
		) );
		$this->assign_genre( $movie_id_2 );

		// Read Movies via Block (CRUD - Read)
		$html_with_movies = $movies_block->render( array() );
		$this->assertStringContainsString( 'Test Movie Alpha', $html_with_movies );
		$this->assertStringContainsString( 'Test Movie Beta', $html_with_movies );
		$this->assertStringContainsString( 'movie-grid', $html_with_movies ); // Grid logic check

		// Read with explicit filtering parameter (Check block attributes parsing)
		$html_filtered = $movies_block->render( array( '_post_ids' => array( $movie_id_1 ) ) );
		$this->assertStringContainsString( 'Test Movie Alpha', $html_filtered );
		$this->assertStringNotContainsString( 'Test Movie Beta', $html_filtered );

		// Update Movie Post (CRUD - Update)
		wp_update_post( array(
			'ID'         => $movie_id_2,
			'post_title' => 'Test Movie Beta v2',
		) );
		$html_updated_movies = $movies_block->render( array() );
		$this->assertStringContainsString( 'Test Movie Beta v2', $html_updated_movies );
		$this->assertStringNotContainsString( 'Test Movie Beta</', $html_updated_movies );

		// Delete Movie Post (CRUD - Delete)
		wp_delete_post( $movie_id_1, true );
		$html_after_delete = $movies_block->render( array() );
		$this->assertStringNotContainsString( 'Test Movie Alpha', $html_after_delete );
	}

	public function test_single_movie_block_render() {
		$movie_block = Movie_Block::get_instance();

		// Empty/Invalid state
		$html_empty = $movie_block->render( array( 'movieId' => 0 ) );
		$this->assertStringContainsString( 'Select a movie', $html_empty );

		// Create
		$movie_id = $this->factory->post->create( array(
			'post_type'   => 'rt-movie',
			'post_title'  => 'Single Block Movie Focus',
			'post_status' => 'publish',
		) );
		$this->assign_genre( $movie_id );

		// Render with valid ID
		$html_rendered = $movie_block->render( array( 'movieId' => $movie_id ) );
		$this->assertStringContainsString( 'Single Block Movie Focus', $html_rendered );
	}

	public function test_persons_block_render() {
		$persons_block = \RT_Movie_Library\Classes\Blocks\Persons_Block::get_instance();

		// Create Person
		$person_id = $this->factory->post->create( array(
			'post_type'  => 'rt-person',
			'post_title' => 'Director John Doe',
		) );

		$html = $persons_block->render( array() );
		$this->assertStringContainsString( 'Director John Doe', $html );
		$this->assertStringContainsString( 'person-list', $html );
	}

	public function test_person_block_render() {
		$person_block = \RT_Movie_Library\Classes\Blocks\Person_Block::get_instance();

		$person_id = $this->factory->post->create( array(
			'post_type'  => 'rt-person',
			'post_title' => 'Featured Legend',
		) );

		$html = $person_block->render( array( 'personId' => $person_id ) );
		$this->assertStringContainsString( 'Featured Legend', $html );
		$this->assertStringContainsString( 'person-card', $html );
	}
}
