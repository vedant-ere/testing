<?php
/**
 * Tests for dashboard widgets.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Tests\Unit;

use ReflectionProperty;
use RT_Movie_Library\Classes\Dashboard\Dashboard_Widgets;
use RT_Movie_Library\Classes\Tmdb\Tmdb_Client;
use WP_Error;
use WP_UnitTestCase;

/**
 * Covers widget registration, CRUD rendering, and TMDB error states.
 */
class Test_Dashboard_Widgets extends WP_UnitTestCase {

	/**
	 * Shared admin user for dashboard capability checks.
	 *
	 * @var int
	 */
	private int $admin_id = 0;

	/**
	 * Prepare dashboard context and plugin state for each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		\RT_Movie_Library\Classes\Plugin::get_instance()->register();
		do_action( 'init' );

		// Factory post creation triggers save hooks before terms are attached.
		remove_all_actions( 'save_post_rt-movie' );

		add_filter(
			'map_meta_cap',
			function ( $caps, $cap, $user_id, $args ) {
				$rt_caps = array(
					'edit_rt-movie',
					'edit_rt-movies',
					'edit_others_rt-movies',
					'publish_rt-movies',
					'edit_dashboard',
				);

				if ( 'edit_post' === $cap && ! empty( $args[0] ) ) {
					$post_type = get_post_type( (int) $args[0] );
					if ( 'rt-movie' === $post_type ) {
						return array( 'exist' );
					}
				}

				if ( array_intersect( $caps, $rt_caps ) ) {
					return array( 'exist' );
				}

				return $caps;
			},
			10,
			4
		);

		$this->admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_id );

		Dashboard_Widgets::get_instance();
		require_once ABSPATH . 'wp-admin/includes/post.php';
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';

		set_current_screen( 'dashboard' );
		do_action( 'wp_dashboard_setup' );
	}

	/**
	 * Restore global test state modified in setUp.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		add_action(
			'save_post_rt-movie',
			array( \RT_Movie_Library\Classes\Post_Types\Movie::get_instance(), 'validate_required_genre' ),
			10,
			2
		);

		$ref_instance = new ReflectionProperty( Tmdb_Client::class, 'instance' );
		$ref_instance->setValue( null, null );

		parent::tearDown();
	}

	/**
	 * Attach a genre term required by movie validation logic.
	 *
	 * @param int $post_id Movie post ID.
	 * @return void
	 */
	private function assign_genre( int $post_id ): void {
		$genre_id = $this->factory->term->create(
			array(
				'taxonomy' => 'rt-movie-genre',
				'name'     => 'Test Genre',
			)
		);

		if ( is_wp_error( $genre_id ) ) {
			$term     = get_term_by( 'name', 'Test Genre', 'rt-movie-genre' );
			$genre_id = $term ? $term->term_id : 0;
		}

		if ( 0 < $genre_id ) {
			wp_set_object_terms( $post_id, array( $genre_id ), 'rt-movie-genre' );
		}
	}

	/**
	 * Verify all widget IDs are present after dashboard setup.
	 *
	 * @return void
	 */
	public function test_widgets_are_registered(): void {
		global $wp_meta_boxes;

		$found_recent = false;
		$found_rated  = false;
		$found_tmdb   = false;

		foreach ( $wp_meta_boxes['dashboard'] as $context ) {
			foreach ( $context as $priority ) {
				if ( isset( $priority['rt_widget_recent_movies'] ) ) {
					$found_recent = true;
				}
				if ( isset( $priority['rt_widget_top_rated_movies'] ) ) {
					$found_rated = true;
				}
				if ( isset( $priority['rt_widget_upcoming_movies'] ) ) {
					$found_tmdb = true;
				}
			}
		}

		$this->assertTrue( $found_recent, 'rt_widget_recent_movies should be registered.' );
		$this->assertTrue( $found_rated, 'rt_widget_top_rated_movies should be registered.' );
		$this->assertTrue( $found_tmdb, 'rt_widget_upcoming_movies should be registered.' );
	}

	/**
	 * Validate recent-movies widget output through create, update, and delete.
	 *
	 * @return void
	 */
	public function test_recent_movies_crud(): void {
		$dashboard = Dashboard_Widgets::get_instance();

		ob_start();
		$dashboard->render_recent_movies();
		$html_empty = ob_get_clean();

		$this->assertStringContainsString( 'No movies found.', $html_empty );
		$this->assertStringNotContainsString( '<ul', $html_empty );

		$movie_1 = $this->factory->post->create(
			array(
				'post_type'   => 'rt-movie',
				'post_title'  => 'First Dashboard Movie',
				'post_date'   => '2025-01-01 12:00:00',
				'post_status' => 'publish',
			)
		);
		$this->assign_genre( $movie_1 );

		ob_start();
		$dashboard->render_recent_movies();
		$html_populated = ob_get_clean();

		$this->assertStringContainsString( 'First Dashboard Movie', $html_populated );
		$this->assertStringContainsString( 'rt-dashboard-widget-list', $html_populated );
		$this->assertStringContainsString( 'Post Created On: 2025-01-01', $html_populated );

		update_post_meta( $movie_1, 'rt-movie-meta-basic-release-date', '2026-12-25' );

		ob_start();
		$dashboard->render_recent_movies();
		$html_updated = ob_get_clean();
		$this->assertStringContainsString( 'Release Date: 2026-12-25', $html_updated );

		wp_delete_post( $movie_1, true );

		ob_start();
		$dashboard->render_recent_movies();
		$html_deleted = ob_get_clean();

		$this->assertStringNotContainsString( 'First Dashboard Movie', $html_deleted );
		$this->assertStringContainsString( 'No movies found.', $html_deleted );
	}

	/**
	 * Validate rating sort order and updates in top-rated widget output.
	 *
	 * @return void
	 */
	public function test_top_rated_movies_crud(): void {
		$dashboard = Dashboard_Widgets::get_instance();

		$movie_id_low = $this->factory->post->create(
			array(
				'post_type'   => 'rt-movie',
				'post_title'  => 'Low Rated Movie',
				'post_status' => 'publish',
			)
		);
		$this->assign_genre( $movie_id_low );
		update_post_meta( $movie_id_low, 'rt-movie-meta-basic-rating', 4.0 );

		$movie_id_high = $this->factory->post->create(
			array(
				'post_type'   => 'rt-movie',
				'post_title'  => 'High Rated Movie',
				'post_status' => 'publish',
			)
		);
		$this->assign_genre( $movie_id_high );
		update_post_meta( $movie_id_high, 'rt-movie-meta-basic-rating', 9.5 );

		ob_start();
		$dashboard->render_top_rated_movies();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'High Rated Movie', $html );
		$this->assertStringContainsString( '9.5', $html );
		$this->assertStringContainsString( 'Low Rated Movie', $html );
		$this->assertStringContainsString( '4.0', $html );

		$pos_high = strpos( $html, 'High Rated Movie' );
		$pos_low  = strpos( $html, 'Low Rated Movie' );
		$this->assertLessThan( $pos_low, $pos_high, 'High rated movie should appear before low rated movie.' );

		update_post_meta( $movie_id_low, 'rt-movie-meta-basic-rating', 9.9 );

		ob_start();
		$dashboard->render_top_rated_movies();
		$html_v2 = ob_get_clean();
		$this->assertLessThan( strpos( $html_v2, 'High Rated Movie' ), strpos( $html_v2, 'Low Rated Movie' ), 'Low rated movie (now 9.9) should appear first.' );
	}

	/**
	 * Validate TMDB widget rendering using mocked successful API payload.
	 *
	 * @return void
	 */
	public function test_upcoming_movies_mocked_api(): void {
		$mock_client = $this->getMockBuilder( Tmdb_Client::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_upcoming_movies' ) )
			->getMock();

		$mock_client->expects( $this->once() )
			->method( 'get_upcoming_movies' )
			->willReturn(
				array(
					array(
						'title'        => 'Mocked Movie Alpha',
						'release_date' => '2027-01-01',
					),
					array(
						'title'        => 'Mocked Movie Beta',
						'release_date' => '2027-02-01',
					),
				)
			);

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

	/**
	 * Validate empty TMDB response handling.
	 *
	 * @return void
	 */
	public function test_upcoming_movies_empty_api_response(): void {
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

	/**
	 * Validate hard limit of five movies in upcoming widget output.
	 *
	 * @return void
	 */
	public function test_upcoming_movies_limit_enforcement(): void {
		$mock_client = $this->getMockBuilder( Tmdb_Client::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_upcoming_movies' ) )
			->getMock();

		$movies = array();
		for ( $i = 1; $i <= 7; $i++ ) {
			$movies[] = array(
				'title'        => "Movie $i",
				'release_date' => '2030-01-01',
			);
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

		$this->assertSame( 5, substr_count( $html, '<li>' ), 'Widget should only render 5 movies regardless of API response size.' );
		$this->assertStringContainsString( 'Movie 5', $html );
		$this->assertStringNotContainsString( 'Movie 6', $html );
	}

	/**
	 * Validate error message rendering when TMDB client returns WP_Error.
	 *
	 * @return void
	 */
	public function test_upcoming_movies_api_error_handling(): void {
		$mock_client = $this->getMockBuilder( Tmdb_Client::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_upcoming_movies' ) )
			->getMock();

		$mock_client->expects( $this->once() )
			->method( 'get_upcoming_movies' )
			->willReturn( new WP_Error( 'api_fail', 'Service Unavailable Mock Error' ) );

		$ref_instance = new ReflectionProperty( Tmdb_Client::class, 'instance' );
		$ref_instance->setValue( null, $mock_client );

		$dashboard = Dashboard_Widgets::get_instance();

		ob_start();
		$dashboard->render_upcoming_movies();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Service Unavailable Mock Error', $html );
		$this->assertStringContainsString( 'rt-widget-error', $html );
	}

	/**
	 * Validate release-date markup helper output.
	 *
	 * @return void
	 */
	public function test_build_release_date_markup_unit(): void {
		$dashboard = Dashboard_Widgets::get_instance();
		$method    = new \ReflectionMethod( Dashboard_Widgets::class, 'build_release_date_markup' );

		$res1 = $method->invoke( $dashboard, '' );
		$this->assertSame( '', $res1 );

		$res2 = $method->invoke( $dashboard, '2025-12-30' );
		$this->assertStringContainsString( 'Release Date: 2025-12-30', $res2 );
		$this->assertStringContainsString( 'rt-widget-date', $res2 );
	}
}
