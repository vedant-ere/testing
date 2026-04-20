<?php
/**
 * Test Dashboard Widgets functionality.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Tests\Unit;

use WP_UnitTestCase;
use RT_Movie_Library\Classes\Dashboard\Dashboard_Widgets;
use RT_Movie_Library\Classes\Tmdb\Tmdb_Client;
use ReflectionProperty;
use WP_Error;

class Test_Dashboard_Widgets extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		
		// Ensure custom post types and taxonomies are registered for tests
		\RT_Movie_Library\Classes\Plugin::get_instance()->register();
		
		// Trigger init to ensure taxonomy registration completes
		do_action( 'init' );
		
		// Grant capabilities to administrator role so get_edit_post_link works
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'edit_rt-movie' );
			$admin_role->add_cap( 'edit_rt-movies' );
			$admin_role->add_cap( 'edit_others_rt-movies' );
			$admin_role->add_cap( 'publish_rt-movies' );
		}
		
		// CRITICAL: Set a logged-in admin user BEFORE firing dashboard hooks.
		// register_widgets() checks current_user_can(), so no user = no widgets registered.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		
		// Run widget registration hooks
		Dashboard_Widgets::get_instance();
		require_once ABSPATH . 'wp-admin/includes/post.php';
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		
		// Explicitly initialize global for tests with full structure
		global $wp_meta_boxes;
		if ( ! isset( $wp_meta_boxes ) ) {
			$wp_meta_boxes = array();
		}
		$wp_meta_boxes['dashboard'] = array(
			'normal' => array(),
			'side'   => array(),
			'column3' => array(),
			'column4' => array(),
		);
		
		do_action( 'wp_dashboard_setup' );
	}

	/**
	 * Helper to assign a genre to a movie to prevent "Force to Draft" logic.
	 */
	private function assign_genre( $post_id ) {
		$genre_id = $this->factory->term->create( array( 'taxonomy' => 'rt-movie-genre', 'name' => 'Test Genre' ) );
		
		if ( is_wp_error( $genre_id ) ) {
			// Fallback if term already exists
			$term = get_term_by( 'name', 'Test Genre', 'rt-movie-genre' );
			$genre_id = $term ? $term->term_id : 0;
		}
		
		if ( $genre_id > 0 ) {
			wp_set_object_terms( $post_id, array( $genre_id ), 'rt-movie-genre' );
		}
	}

	public function tearDown(): void {
		// Clean up Singleton mock
		$ref_instance = new ReflectionProperty( Tmdb_Client::class, 'instance' );
		$ref_instance->setValue( null, null );
		
		parent::tearDown();
	}

	public function test_widgets_are_registered() {
		global $wp_meta_boxes;
		
		// setUp already set an admin user and fired wp_dashboard_setup.
		// Widgets should now be in the registry.
		$found_recent = false;
		$found_rated  = false;
		$found_tmdb   = false;

		foreach ( $wp_meta_boxes['dashboard'] as $context ) {
			foreach ( $context as $priority ) {
				if ( isset( $priority['rt_widget_recent_movies'] ) ) $found_recent = true;
				if ( isset( $priority['rt_widget_top_rated_movies'] ) ) $found_rated = true;
				if ( isset( $priority['rt_widget_upcoming_movies'] ) ) $found_tmdb = true;
			}
		}

		$this->assertTrue( $found_recent, 'rt_widget_recent_movies should be registered.' );
		$this->assertTrue( $found_rated, 'rt_widget_top_rated_movies should be registered.' );
		$this->assertTrue( $found_tmdb, 'rt_widget_upcoming_movies should be registered.' );
	}

	public function test_recent_movies_crud() {
		$dashboard = Dashboard_Widgets::get_instance();
		
		ob_start();
		$dashboard->render_recent_movies();
		$html_empty = ob_get_clean();
		
		$this->assertStringContainsString( 'No movies found.', $html_empty );
		$this->assertStringNotContainsString( '<ul', $html_empty );

		// Create Movies (CRUD - Create) — user already set in setUp
		$movie_1 = $this->factory->post->create( array( 
			'post_type'   => 'rt-movie', 
			'post_title'  => 'First Dashboard Movie',
			'post_date'   => '2025-01-01 12:00:00',
			'post_status' => 'publish',
		) );
		$this->assign_genre( $movie_1 );

		ob_start();
		$dashboard->render_recent_movies();
		$html_populated = ob_get_clean();

		// Read Movies (CRUD - Read)
		$this->assertStringContainsString( 'First Dashboard Movie', $html_populated );
		$this->assertStringContainsString( 'rt-dashboard-widget-list', $html_populated );
		$this->assertStringContainsString( 'Post Created On: 2025-01-01', $html_populated );
		$this->assertStringContainsString( 'href="' . get_edit_post_link( $movie_1 ), $html_populated );

		// Update Movie Meta (CRUD - Update)
		update_post_meta( $movie_1, 'rt-movie-meta-basic-release-date', '2026-12-25' );
		
		ob_start();
		$dashboard->render_recent_movies();
		$html_updated = ob_get_clean();
		$this->assertStringContainsString( 'Release Date: 2026-12-25', $html_updated );

		// Delete Post (CRUD - Delete)
		wp_delete_post( $movie_1, true );
		
		ob_start();
		$dashboard->render_recent_movies();
		$html_deleted = ob_get_clean();

		$this->assertStringNotContainsString( 'First Dashboard Movie', $html_deleted );
		$this->assertStringContainsString( 'No movies found.', $html_deleted );
	}

	public function test_top_rated_movies_crud() {
		$dashboard = Dashboard_Widgets::get_instance();
		
		// Create Movies with ratings (CRUD - Create/Update meta) — user already set in setUp
		$movie_id_low = $this->factory->post->create( array( 'post_type' => 'rt-movie', 'post_title' => 'Low Rated Movie', 'post_status' => 'publish' ) );
		$this->assign_genre( $movie_id_low );
		update_post_meta( $movie_id_low, 'rt-movie-meta-basic-rating', 4.0 );
		
		$movie_id_high = $this->factory->post->create( array( 'post_type' => 'rt-movie', 'post_title' => 'High Rated Movie', 'post_status' => 'publish' ) );
		$this->assign_genre( $movie_id_high );
		update_post_meta( $movie_id_high, 'rt-movie-meta-basic-rating', 9.5 );

		ob_start();
		$dashboard->render_top_rated_movies();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'High Rated Movie', $html );
		$this->assertStringContainsString( '9.5', $html );
		$this->assertStringContainsString( 'Low Rated Movie', $html );
		$this->assertStringContainsString( '4.0', $html );
		
		// Verify Ordering (Senior check)
		$pos_high = strpos( $html, 'High Rated Movie' );
		$pos_low  = strpos( $html, 'Low Rated Movie' );
		$this->assertLessThan( $pos_low, $pos_high, 'High rated movie should appear before low rated movie.' );

		// Update meta and check again (CRUD - Update)
		update_post_meta( $movie_id_low, 'rt-movie-meta-basic-rating', 9.9 );
		
		ob_start();
		$dashboard->render_top_rated_movies();
		$html_v2 = ob_get_clean();
		$this->assertLessThan( strpos( $html_v2, 'High Rated Movie' ), strpos( $html_v2, 'Low Rated Movie' ), 'Low rated movie (now 9.9) should appear first.' );
	}

	public function test_upcoming_movies_mocked_api() {
		// Mock Tmdb_Client to prevent live API requests
		$mock_client = $this->getMockBuilder( Tmdb_Client::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_upcoming_movies' ) )
			->getMock();

		$mock_client->expects( $this->once() )
			->method( 'get_upcoming_movies' )
			->willReturn( array(
				array( 'title' => 'Mocked Movie Alpha', 'release_date' => '2027-01-01' ),
				array( 'title' => 'Mocked Movie Beta', 'release_date' => '2027-02-01' ),
			) );

		// Inject mock into Singleton instance
		$ref_instance = new ReflectionProperty( Tmdb_Client::class, 'instance' );
		$ref_instance->setValue( null, $mock_client );

		$dashboard = Dashboard_Widgets::get_instance();

		ob_start();
		$dashboard->render_upcoming_movies();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Mocked Movie Alpha', $html );
		$this->assertStringContainsString( 'Release Date: 2027-01-01', $html );
		$this->assertStringContainsString( 'Mocked Movie Beta', $html );
		$this->assertStringContainsString( 'rt-dashboard-widget-list', $html );
	}

	public function test_upcoming_movies_empty_api_response() {
		$mock_client = $this->getMockBuilder( Tmdb_Client::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_upcoming_movies' ) )
			->getMock();

		$mock_client->expects( $this->once() )
			->method( 'get_upcoming_movies' )
			->willReturn( array() );

		$ref_instance = new ReflectionProperty( Tmdb_Client::class, 'instance' );
		$ref_instance->setValue( null, $mock_client );

		$dashboard = Dashboard_Widgets::get_instance();

		ob_start();
		$dashboard->render_upcoming_movies();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'No upcoming movies found.', $html );
	}

	public function test_upcoming_movies_limit_enforcement() {
		$mock_client = $this->getMockBuilder( Tmdb_Client::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_upcoming_movies' ) )
			->getMock();

		// Return 7 movies, but widget should only show 5
		$movies = array();
		for ( $i = 1; $i <= 7; $i++ ) {
			$movies[] = array( 'title' => "Movie $i", 'release_date' => '2030-01-01' );
		}

		$mock_client->expects( $this->once() )
			->method( 'get_upcoming_movies' )
			->willReturn( $movies );

		$ref_instance = new ReflectionProperty( Tmdb_Client::class, 'instance' );
		$ref_instance->setValue( null, $mock_client );

		$dashboard = Dashboard_Widgets::get_instance();

		ob_start();
		$dashboard->render_upcoming_movies();
		$html = ob_get_clean();

		// Count <li> tags
		$this->assertSame( 5, substr_count( $html, '<li>' ), 'Widget should only render 5 movies regardless of API response size.' );
		$this->assertStringContainsString( 'Movie 5', $html );
		$this->assertStringNotContainsString( 'Movie 6', $html );
	}

	public function test_upcoming_movies_api_error_handling() {
		// Mock Tmdb_Client to throw WP_Error and see if graceful degradation happens
		$mock_client = $this->getMockBuilder( Tmdb_Client::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_upcoming_movies' ) )
			->getMock();

		$mock_client->expects( $this->once() )
			->method( 'get_upcoming_movies' )
			->willReturn( new WP_Error( 'api_fail', 'Service Unavailable Mock Error' ) );

		$ref_instance = new ReflectionProperty( Tmdb_Client::class, 'instance' );
		$ref_instance->setAccessible( true );
		$ref_instance->setValue( null, $mock_client );

		$dashboard = Dashboard_Widgets::get_instance();

		ob_start();
		$dashboard->render_upcoming_movies();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Service Unavailable Mock Error', $html );
		$this->assertStringContainsString( 'rt-widget-error', $html );
	}

	/**
	 * Unit test for the private build_release_date_markup method.
	 */
	public function test_build_release_date_markup_unit() {
		$dashboard = Dashboard_Widgets::get_instance();
		$method    = new \ReflectionMethod( Dashboard_Widgets::class, 'build_release_date_markup' );

		// Case 1: Empty date
		$res1 = $method->invoke( $dashboard, '' );
		$this->assertSame( '', $res1 );

		// Case 2: Valid date
		$res2 = $method->invoke( $dashboard, '2025-12-30' );
		$this->assertStringContainsString( 'Release Date: 2025-12-30', $res2 );
		$this->assertStringContainsString( 'rt-widget-date', $res2 );
	}
}
