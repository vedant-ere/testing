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
	 * Meta key storing last synced TMDB poster path.
	 *
	 * @var string
	 */
	private const META_POSTER_PATH = '_mw_tmdb_poster_path';

	/**
	 * Meta key storing sideloaded poster attachment ID.
	 *
	 * @var string
	 */
	private const META_POSTER_ATTACHMENT_ID = '_mw_tmdb_poster_attachment_id';

	/**
	 * Base URL for TMDB poster images.
	 *
	 * @var string
	 */
	private const TMDB_POSTER_BASE_URL = 'https://image.tmdb.org/t/p/w500';

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
			update_option(
				'rt_tmdb_last_sync_error',
				array(
					'message' => sanitize_text_field( $e->getMessage() ),
					'time'    => time(),
				),
				false
			);

			/**
			 * Fires when the TMDB cron sync fails unexpectedly.
			 *
			 * @param \Throwable $e Exception that interrupted sync.
			 */
			do_action( 'rt_movie_library_tmdb_sync_error', $e );

			return;
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
				'orderby'                => array(
					'meta_value_num' => 'ASC',
					'ID'             => 'ASC',
				),
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- intentional: prioritises unsynced movies first.
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

		$new_poster_path = trim( (string) ( $tmdb_data['poster_path'] ?? '' ) );
		$this->sync_movie_poster( $post->ID, $new_poster_path );

		update_post_meta( $post->ID, self::META_SYNCED_AT, time() );
	}

	/**
	 * Download and attach TMDB poster when poster path changes.
	 *
	 * @param int    $post_id     Movie post ID.
	 * @param string $poster_path TMDB poster path.
	 * @return void
	 */
	private function sync_movie_poster( int $post_id, string $poster_path ): void {
		if ( '' === $poster_path ) {
			return;
		}

		$existing_path          = trim( (string) get_post_meta( $post_id, self::META_POSTER_PATH, true ) );
		$existing_attachment_id = absint( get_post_meta( $post_id, self::META_POSTER_ATTACHMENT_ID, true ) );

		// Skip remote download when poster path is already synced.
		if ( $poster_path === $existing_path ) {
			if ( has_post_thumbnail( $post_id ) ) {
				return;
			}

			if ( $existing_attachment_id > 0 && get_post( $existing_attachment_id ) instanceof \WP_Post ) {
				set_post_thumbnail( $post_id, $existing_attachment_id );
				return;
			}
		}

		$this->load_media_dependencies();

		$poster_url = esc_url_raw( self::TMDB_POSTER_BASE_URL . $poster_path );

		if ( '' === $poster_url ) {
			return;
		}

		$attachment_id = media_sideload_image( $poster_url, $post_id, null, 'id' );

		if ( is_wp_error( $attachment_id ) ) {
			return;
		}

		$attachment_id = absint( $attachment_id );

		if ( $attachment_id < 1 ) {
			return;
		}

		set_post_thumbnail( $post_id, $attachment_id );
		update_post_meta( $post_id, self::META_POSTER_PATH, $poster_path );
		update_post_meta( $post_id, self::META_POSTER_ATTACHMENT_ID, $attachment_id );
	}

	/**
	 * Load WordPress media APIs required by media_sideload_image().
	 *
	 * @return void
	 */
	private function load_media_dependencies(): void {
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
	}
}
