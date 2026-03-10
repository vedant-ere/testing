<?php
/**
 * Dashboard widgets for the RT Movie Library plugin.
 *
 * Registers three admin dashboard widgets:
 *  1. Most Recent Movies  — local WP_Query
 *  2. Top Rated Movies    — local WP_Query ordered by meta rating
 *  3. Upcoming Movies     — TMDB HTTP API with 4-hour transient cache
 *
 * @package RT_Movie_Library
 * @since   1.0.0
 */

namespace RT_Movie_Library\Classes\Dashboard;

use RT_Movie_Library\Traits\Singleton;
use RT_Movie_Library\Classes\Tmdb\Tmdb_Client;

defined( 'ABSPATH' ) || exit;

/**
 * Class Dashboard_Widgets
 *
 * Owns registration and rendering of all three movie dashboard widgets.
 * Uses the Singleton trait so it is booted once via ::get_instance().
 */
class Dashboard_Widgets {

	use Singleton;

	/**
	 * WordPress post type slug for movies.
	 *
	 * @var string
	 */
	private const POST_TYPE = 'rt-movie';

	/**
	 * Post meta key that stores the TMDB / synced rating value.
	 *
	 * @var string
	 */
	private const META_RATING = 'rt-movie-meta-basic-rating';

	/**
	 * Number of posts shown per widget list.
	 *
	 * @var int
	 */
	private const WIDGET_POST_LIMIT = 5;

	/**
	 * Bootstrap action hooks.
	 */
	protected function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widgets' ) );
	}

	// =========================================================================
	// Registration
	// =========================================================================

	/**
	 * Register all three dashboard widgets.
	 *
	 * Hooked to wp_dashboard_setup. Each widget is gated behind
	 * edit_rt-movies so only admins and movie-managers see it.
	 *
	 * @return void
	 */
	public function register_widgets(): void {
		if ( ! current_user_can( 'edit_rt-movies' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'rt_widget_recent_movies',
			esc_html__( 'Recent Movies', 'rt-movie-library' ),
			array( $this, 'render_recent_movies' )
		);

		wp_add_dashboard_widget(
			'rt_widget_top_rated_movies',
			esc_html__( 'Top Rated Movies', 'rt-movie-library' ),
			array( $this, 'render_top_rated_movies' )
		);

		wp_add_dashboard_widget(
			'rt_widget_upcoming_movies',
			esc_html__( 'Upcoming Movies (TMDB)', 'rt-movie-library' ),
			array( $this, 'render_upcoming_movies' )
		);
	}

	// =========================================================================
	// Widget 1 — Most Recent Movies
	// =========================================================================

	/**
	 * Render the Most Recent Movies widget.
	 *
	 * Queries the local database for the most recently published movie posts.
	 * No transient needed — WP_Query is backed by the WordPress object cache.
	 *
	 * @return void
	 */
	public function render_recent_movies(): void {
		$query = new \WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => self::WIDGET_POST_LIMIT,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);

		if ( ! $query->have_posts() ) {
			echo '<p>' . esc_html__( 'No movies found.', 'rt-movie-library' ) . '</p>';
			wp_reset_postdata();
			return;
		}

		echo '<ul class="rt-dashboard-widget-list">';

		foreach ( $query->posts as $post ) {
			$edit_link = get_edit_post_link( $post->ID );

			if ( empty( $edit_link ) ) {
				continue;
			}

			printf(
				'<li><a href="%s">%s</a> <span class="rt-widget-date">(%s)</span></li>',
				esc_url( $edit_link ),
				esc_html( get_the_title( $post ) ),
				esc_html( get_the_date( 'Y-m-d', $post ) )
			);
		}

		echo '</ul>';

		wp_reset_postdata();
	}

	// =========================================================================
	// Widget 2 — Top Rated Movies
	// =========================================================================

	/**
	 * Render the Top Rated Movies widget.
	 *
	 * Queries the local database ordered by the numeric rating meta key.
	 * Only posts that have a rating assigned are included.
	 *
	 * @return void
	 */
	public function render_top_rated_movies(): void {
		$query = new \WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => self::WIDGET_POST_LIMIT,
				'meta_key'               => self::META_RATING,
				'orderby'                => array(
					'meta_value_num' => 'DESC',
					'title'          => 'ASC',
				),
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- intentional: filters movies with a rating assigned.
					array(
						'key'     => self::META_RATING,
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'ignore_custom_sort'     => true,
			)
		);

		if ( ! $query->have_posts() ) {
			echo '<p>' . esc_html__( 'No rated movies found.', 'rt-movie-library' ) . '</p>';
			wp_reset_postdata();
			return;
		}

		echo '<ul class="rt-dashboard-widget-list">';

		foreach ( $query->posts as $post ) {
			$edit_link = get_edit_post_link( $post->ID );
			$rating    = (float) get_post_meta( $post->ID, self::META_RATING, true );

			if ( empty( $edit_link ) ) {
				continue;
			}

			printf(
				'<li><a href="%s">%s</a> <span class="rt-widget-rating">&#9733; %s</span></li>',
				esc_url( $edit_link ),
				esc_html( get_the_title( $post ) ),
				esc_html( number_format( $rating, 1 ) )
			);
		}

		echo '</ul>';

		wp_reset_postdata();
	}

	// =========================================================================
	// Widget 3 — Upcoming Movies from TMDB
	// =========================================================================

	/**
	 * Render the Upcoming Movies (TMDB) widget.
	 *
	 * Fetches upcoming movies from the TMDB API. The response is cached for
	 * 4 hours via the Transient API to avoid repeated remote calls.
	 * Degrades gracefully when the API key is missing or the request fails.
	 *
	 * @return void
	 */
	public function render_upcoming_movies(): void {
		$client = new Tmdb_Client();
		$movies = $client->get_upcoming_movies();

		if ( is_wp_error( $movies ) ) {
			printf(
				'<p class="rt-widget-error">%s</p>',
				esc_html( $movies->get_error_message() )
			);
			return;
		}

		if ( empty( $movies ) ) {
			echo '<p>' . esc_html__( 'No upcoming movies found.', 'rt-movie-library' ) . '</p>';
			return;
		}

		echo '<ul class="rt-dashboard-widget-list">';
		$count = 0;

		foreach ( $movies as $movie ) {
			if ( $count >= self::WIDGET_POST_LIMIT ) {
				break;
			}

			$title        = isset( $movie['title'] ) ? (string) $movie['title'] : '';
			$release_date = isset( $movie['release_date'] ) ? (string) $movie['release_date'] : '';

			if ( '' === $title ) {
				continue;
			}

			printf(
				'<li>%s%s</li>',
				esc_html( $title ),
				'' !== $release_date
					? ' <span class="rt-widget-date">(' . esc_html( $release_date ) . ')</span>'
					: ''
			);

			++$count;
		}

		echo '</ul>';
	}
}
