<?php
/**
 * WP Cron handler for TMDB metadata sync.
 *
 * @package RT_Movie_Library
 * @since   1.0.0
 */

namespace RT_Movie_Library\Classes\Tmdb;

use RT_Movie_Library\Traits\Singleton;
use RT_Movie_Library\Classes\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Tmdb_Sync
 */
class Tmdb_Sync {

	use Singleton;

	/**
	 * Cron hook fired on each sync run.
	 *
	 * @var string
	 */
	public const CRON_HOOK = 'rt_tmdb_sync';

	/**
	 * Slug of custom 30-minute interval.
	 *
	 * @var string
	 */
	private const CRON_INTERVAL = 'rt_every_30_min';

	/**
	 * Meta key storing last sync timestamp.
	 *
	 * @var string
	 */
	private const META_SYNCED_AT = '_mw_tmdb_synced_at';

	/**
	 * Bootstrap hooks.
	 */
	protected function __construct() {
		add_filter( 'cron_schedules', array( $this, 'register_cron_interval' ) );
		add_action( self::CRON_HOOK, array( $this, 'run_sync' ) );
	}

	/**
	 * Register custom 30-minute cron interval.
	 *
	 * @param array<string, array<string, mixed>> $schedules Existing schedules.
	 * @return array<string, array<string, mixed>>
	 */
	public function register_cron_interval( array $schedules ): array {
		if ( ! isset( $schedules[ self::CRON_INTERVAL ] ) ) {
			$schedules[ self::CRON_INTERVAL ] = array(
				'interval' => 30 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 30 Minutes', 'rt-movie-library' ),
			);
		}

		return $schedules;
	}

	/**
	 * Schedule cron event on activation.
	 *
	 * @return void
	 */
	public static function schedule(): void {
		self::get_instance();

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), self::CRON_INTERVAL, self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule cron event on deactivation.
	 *
	 * @return void
	 */
	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Cron callback for TMDB metadata sync.
	 *
	 * @return void
	 */
	public function run_sync(): void {
		try {
			$limit = absint( get_option( Settings::OPTION_MOVIE_LIMIT, 5 ) );
			if ( $limit < 1 ) {
				$limit = 5;
			}

			$client = new Tmdb_Client();
			$movies = $this->get_movies_to_sync( $limit );

			if ( empty( $movies ) ) {
				return;
			}

			foreach ( $movies as $post ) {
				$this->sync_single_movie( $post, $client );
			}
		} catch ( \Throwable $e ) {
			error_log(
				sprintf(
					'[RT Movie Library] Cron sync failed: %s in %s on line %d',
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
				)
			);
		}
	}

	/**
	 * Get movies sorted by least recently synced first.
	 *
	 * @param int $limit Max posts to sync.
	 * @return array<int, \WP_Post>
	 */
	private function get_movies_to_sync( int $limit ): array {
		$query = new \WP_Query(
			array(
				'post_type'              => 'rt-movie',
				'post_status'            => 'publish',
				'posts_per_page'         => $limit,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_key'               => self::META_SYNCED_AT,
				'orderby'                => array(
					'meta_value_num' => 'ASC',
					'ID'             => 'ASC',
				),
				'meta_query'             => array(
					'relation' => 'OR',
					array(
						'key'     => self::META_SYNCED_AT,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => self::META_SYNCED_AT,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		return $query->posts;
	}

	/**
	 * Sync single movie metadata from TMDB.
	 *
	 * @param \WP_Post    $post   Movie post.
	 * @param Tmdb_Client $client Shared TMDB client.
	 * @return void
	 */
	private function sync_single_movie( \WP_Post $post, Tmdb_Client $client ): void {
		$tmdb_data = $client->search_movie( $post->post_title );

		if ( is_wp_error( $tmdb_data ) ) {
			error_log(
				sprintf(
					'[RT Movie Library] TMDB sync skipped for post %d (%s): %s',
					$post->ID,
					$post->post_title,
					$tmdb_data->get_error_message()
				)
			);

			update_post_meta( $post->ID, self::META_SYNCED_AT, time() );
			return;
		}

		$new_rating      = (float) ( $tmdb_data['vote_average'] ?? 0 );
		$existing_rating = (float) get_post_meta( $post->ID, 'rt-movie-meta-basic-rating', true );

		if ( $existing_rating !== $new_rating ) {
			update_post_meta( $post->ID, 'rt-movie-meta-basic-rating', $new_rating );
		}

		$new_date      = trim( (string) ( $tmdb_data['release_date'] ?? '' ) );
		$existing_date = trim( (string) get_post_meta( $post->ID, 'rt-movie-meta-basic-release-date', true ) );

		if ( '' !== $new_date && $existing_date !== $new_date ) {
			update_post_meta( $post->ID, 'rt-movie-meta-basic-release-date', $new_date );
		}

		update_post_meta( $post->ID, self::META_SYNCED_AT, time() );
	}
}
