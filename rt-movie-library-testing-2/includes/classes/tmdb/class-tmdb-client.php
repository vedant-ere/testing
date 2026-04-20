<?php
/**
 * TMDB HTTP client shared across dashboard widgets and cron sync.
 *
 * @package RT_Movie_Library
 * @since   1.0.0
 */

namespace RT_Movie_Library\Classes\Tmdb;

use RT_Movie_Library\Classes\Settings;
use RT_Movie_Library\Helpers\Logger;
use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Tmdb_Client
 *
 * Shared TMDB HTTP layer for the plugin.
 * Uses the Singleton trait so every consumer shares one instance and
 * any future internal state (rate-limit counters, etc.) is consistent.
 */
class Tmdb_Client {

	use Singleton;

	/**
	 * TMDB API v3 base URL used by the generic request_tmdb() method.
	 *
	 * @var string
	 */
	private const BASE_URL = 'https://api.themoviedb.org/3';

	/**
	 * Transient key for upcoming movies cache.
	 *
	 * @var string
	 */
	private const TRANSIENT_UPCOMING = 'rt_tmdb_upcoming_movies';

	/**
	 * Lock transient key used to prevent cache stampede.
	 *
	 * @var string
	 */
	private const LOCK_KEY = 'rt_tmdb_upcoming_lock';

	/**
	 * Transient TTL — 4 hours balances freshness against TMDB's rate
	 * limits (approx 40 req/10s for free tier). Most upcoming-movie metadata
	 * does not change more often than daily.
	 *
	 * @var int
	 */
	private const TRANSIENT_TTL = 4 * HOUR_IN_SECONDS;

	/**
	 * Lock duration in seconds — long enough for one API round-trip
	 * but short enough to self-heal if the process dies mid-request.
	 *
	 * @var int
	 */
	private const LOCK_TTL = 30;

	/**
	 * How many days ahead to look for upcoming releases via Discover.
	 *
	 * @var int
	 */
	private const DISCOVER_WINDOW_DAYS = 90;

	/**
	 * Return upcoming movies from cache or TMDB API.
	 *
	 * Uses /discover/movie with explicit date filters so only genuinely
	 * future releases are returned, sorted by popularity on the API side
	 * then re-sorted locally by release_date ASC so the soonest-releasing
	 * popular film appears first in the widget.
	 *
	 * @return array<int, array<string, string>>|\WP_Error
	 */
	public function get_upcoming_movies(): array|\WP_Error {
		$cached = get_transient( self::TRANSIENT_UPCOMING );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		// Prevent cache stampede: if another request is already fetching
		// from TMDB, return early rather than piling on concurrent API
		// calls that would all write the same data.
		if ( false !== get_transient( self::LOCK_KEY ) ) {
			Logger::error( 'Upcoming movies request skipped — lock active (stampede protection).', 'tmdb' );

			return new \WP_Error(
				'rt_tmdb_locked',
				__( 'TMDB data is being refreshed. Please try again shortly.', 'rt-movie-library' )
			);
		}

		$bearer_token = $this->get_validated_bearer_token();

		if ( is_wp_error( $bearer_token ) ) {
			return $bearer_token;
		}

		// Acquire lock before the outbound request.
		set_transient( self::LOCK_KEY, 1, self::LOCK_TTL );

		$today    = gmdate( 'Y-m-d' );
		$end_date = gmdate( 'Y-m-d', strtotime( '+' . self::DISCOVER_WINDOW_DAYS . ' days' ) );

		$data = $this->request_tmdb(
			'/discover/movie',
			array(
				'language'                 => 'en-US',
				'page'                     => 1,
				'sort_by'                  => 'popularity.desc',
				'primary_release_date.gte' => $today,
				'primary_release_date.lte' => $end_date,
			),
			$bearer_token
		);

		// Release lock regardless of success/failure so the next request
		// can retry instead of waiting for the TTL to expire.
		delete_transient( self::LOCK_KEY );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
			Logger::error( 'TMDB response missing expected "results" array.', 'tmdb' );

			return new \WP_Error(
				'rt_tmdb_invalid_response',
				__( 'TMDB returned an unrecognised response format.', 'rt-movie-library' )
			);
		}

		$movies = $this->parse_upcoming_results( $data['results'] );

		// Re-sort by release date ascending so the widget shows the
		// soonest-releasing popular movie at the top.
		// Movies without a release date are pushed to the end.
		usort(
			$movies,
			static function ( array $a, array $b ): int {
				$a_empty = '' === $a['release_date'];
				$b_empty = '' === $b['release_date'];

				if ( $a_empty !== $b_empty ) {
					return $a_empty ? 1 : -1;
				}

				return strcmp( $a['release_date'], $b['release_date'] );
			}
		);

		set_transient( self::TRANSIENT_UPCOMING, $movies, self::TRANSIENT_TTL );

		return $movies;
	}

	/**
	/**
	 * Search TMDB for a movie by title and return exact title match.
	 *
	 * @param string $title Movie title.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function search_movie( string $title ): array|\WP_Error {
		$bearer_token = $this->get_validated_bearer_token();

		if ( is_wp_error( $bearer_token ) ) {
			return $bearer_token;
		}

		$search_title = sanitize_text_field( $title );

		if ( '' === $search_title ) {
			Logger::error( 'search_movie() called with empty title.', 'tmdb' );

			return new \WP_Error(
				'rt_tmdb_invalid_title',
				__( 'Movie title is empty.', 'rt-movie-library' )
			);
		}

		$data = $this->request_tmdb(
			'/search/movie',
			array(
				'query'    => $search_title,
				'language' => 'en-US',
				'page'     => 1,
			),
			$bearer_token
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
			Logger::error( 'TMDB search response missing "results" array.', 'tmdb' );

			return new \WP_Error(
				'rt_tmdb_invalid_response',
				__( 'TMDB returned an unrecognised response format.', 'rt-movie-library' )
			);
		}

		$normalized_query = strtolower( trim( $search_title ) );

		foreach ( $data['results'] as $movie ) {
			if ( ! is_array( $movie ) ) {
				continue;
			}

			$raw_title = '';

			if ( isset( $movie['title'] ) ) {
				$raw_title = (string) $movie['title'];
			}

			$candidate = strtolower( trim( $raw_title ) );

			if ( $candidate === $normalized_query ) {
				return $this->sanitize_movie_payload( $movie );
			}
		}

		Logger::error( sprintf( 'No exact TMDB match for "%s".', $search_title ), 'tmdb' );

		return new \WP_Error(
			'rt_tmdb_not_found',
			sprintf(
				/* translators: %s: movie title. */
				__( 'No exact TMDB match found for "%s".', 'rt-movie-library' ),
				$search_title
			)
		);
	}

	/**
	 * Execute TMDB GET request using Bearer token auth.
	 *
	 * @param string               $endpoint     Endpoint path, e.g. /movie/upcoming.
	 * @param array<string, mixed> $query_args   Query args.
	 * @param string               $bearer_token TMDB v4 Read Access Token.
	 * @return array<string, mixed>|\WP_Error
	 */
	private function request_tmdb( string $endpoint, array $query_args, string $bearer_token ): array|\WP_Error {
		$request_url = add_query_arg(
			$query_args,
			self::BASE_URL . $endpoint
		);

		// Short timeout avoids blocking the dashboard render for too long;
		// SSL verification is mandatory for third-party API traffic.
		// Bearer token is sent via Authorization header instead of the query
		// string to avoid exposure in proxy logs, server access logs, and
		// referrer headers.
		$response = wp_remote_get(
			$request_url,
			array(
				'timeout'   => 3,
				'sslverify' => true,
				'headers'   => array(
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . $bearer_token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			Logger::error( 'TMDB HTTP request failed — ' . $response->get_error_message(), 'tmdb' );

			return new \WP_Error(
				'rt_tmdb_request_failed',
				__( 'Could not connect to TMDB. Please try again later.', 'rt-movie-library' )
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			Logger::error( sprintf( 'TMDB returned HTTP %d for %s.', $status_code, $endpoint ), 'tmdb' );

			return new \WP_Error(
				'rt_tmdb_bad_response',
				sprintf(
					/* translators: %d: HTTP status code returned by TMDB. */
					__( 'TMDB returned an unexpected status: %d.', 'rt-movie-library' ),
					$status_code
				)
			);
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			Logger::error( 'TMDB response body is not valid JSON.', 'tmdb' );

			return new \WP_Error(
				'rt_tmdb_invalid_response',
				__( 'TMDB returned an unrecognised response format.', 'rt-movie-library' )
			);
		}

		return $data;
	}

	/**
	 * Fetch and validate the TMDB Bearer token from plugin settings.
	 *
	 * Expects a TMDB v4 API Read Access Token (long alphanumeric/JWT string)
	 * which is sent via the Authorization: Bearer header. This avoids leaking
	 * credentials in query strings where they can be logged by proxies,
	 * server access logs, and referrer headers.
	 *
	 * Sanitize_text_field runs on save via the option's sanitize_callback,
	 * but the format check is still necessary: it catches tokens that pass
	 * sanitization yet would always 401 at TMDB, giving the admin immediate
	 * feedback instead of a confusing API error.
	 *
	 * @return string|\WP_Error Validated Bearer token or error.
	 */
	private function get_validated_bearer_token(): string|\WP_Error {
		$token = (string) get_option( Settings::OPTION_API_KEY, '' );
		$token = sanitize_text_field( $token );

		if ( '' === $token ) {
			Logger::error( 'TMDB API Read Access Token is empty — configure it in Settings.', 'tmdb' );

			return new \WP_Error(
				'rt_tmdb_no_bearer_token',
				__( 'TMDB API Read Access Token is not configured. Please add it in Settings.', 'rt-movie-library' )
			);
		}

		// TMDB v4 Read Access Tokens are long alphanumeric/JWT strings (typically eyJ…).
		if ( 1 !== preg_match( '/^[A-Za-z0-9._\-]{40,}$/', $token ) ) {
			Logger::error( 'TMDB Bearer token failed format validation (expected v4 Read Access Token).', 'tmdb' );

			return new \WP_Error(
				'rt_tmdb_invalid_bearer_token',
				__( 'TMDB token format looks invalid. Please use a valid API Read Access Token (Bearer token) from TMDB.', 'rt-movie-library' )
			);
		}

		return $token;
	}

	/**
	 * Parse and sanitize raw TMDB result items for upcoming list.
	 *
	 * @param array<int, mixed> $results Raw TMDB results.
	 * @return array<int, array<string, string>>
	 */
	private function parse_upcoming_results( array $results ): array {
		$movies = array();

		foreach ( $results as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$title = '';

			if ( isset( $item['title'] ) ) {
				$title = sanitize_text_field( (string) $item['title'] );
			}

			if ( '' === $title ) {
				continue;
			}

			$release_date = '';

			if ( isset( $item['release_date'] ) ) {
				$release_date = $this->sanitize_release_date( (string) $item['release_date'] );
			}

			$movies[] = array(
				'title'        => $title,
				'release_date' => $release_date,
			);
		}

		return $movies;
	}

	/**
	 * Sanitize TMDB movie payload for cron metadata sync.
	 *
	 * @param array<string, mixed> $movie Raw movie payload.
	 * @return array<string, mixed>
	 */
	private function sanitize_movie_payload( array $movie ): array {
		$sanitized = array(
			'id'           => 0,
			'title'        => '',
			'release_date' => '',
			'vote_average' => 0.0,
			'poster_path'  => '',
		);

		if ( isset( $movie['id'] ) ) {
			$sanitized['id'] = absint( $movie['id'] );
		}

		if ( isset( $movie['title'] ) ) {
			$sanitized['title'] = sanitize_text_field( (string) $movie['title'] );
		}

		if ( isset( $movie['release_date'] ) ) {
			$sanitized['release_date'] = $this->sanitize_release_date( (string) $movie['release_date'] );
		}

		if ( isset( $movie['vote_average'] ) ) {
			$sanitized['vote_average'] = (float) $movie['vote_average'];
		}

		if ( isset( $movie['poster_path'] ) ) {
			$sanitized['poster_path'] = $this->sanitize_poster_path( (string) $movie['poster_path'] );
		}

		return $sanitized;
	}

	/**
	 * Validate and sanitize TMDB poster path.
	 *
	 * Expected format from TMDB starts with '/'.
	 *
	 * @param string $raw_path Raw TMDB poster path.
	 * @return string
	 */
	private function sanitize_poster_path( string $raw_path ): string {
		$poster_path = sanitize_text_field( $raw_path );

		if ( '' === $poster_path ) {
			return '';
		}

		if ( '/' !== $poster_path[0] ) {
			return '';
		}

		return $poster_path;
	}

	/**
	 * Validate and sanitize release date format.
	 *
	 * Round-trip validation ensures the date is real (no Feb 30, etc.)
	 * and not a string that merely looks date-shaped.
	 *
	 * @param string $raw_date Raw TMDB release date string.
	 * @return string
	 */
	private function sanitize_release_date( string $raw_date ): string {
		$release_date = sanitize_text_field( $raw_date );

		if ( '' === $release_date ) {
			return '';
		}

		$datetime = \DateTime::createFromFormat( 'Y-m-d', $release_date );

		if ( false === $datetime || $datetime->format( 'Y-m-d' ) !== $release_date ) {
			return '';
		}

		return $release_date;
	}
}
