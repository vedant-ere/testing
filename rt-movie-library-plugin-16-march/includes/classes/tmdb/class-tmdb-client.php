<?php
/**
 * TMDB HTTP client shared across dashboard widgets and cron sync.
 *
 * @package RT_Movie_Library
 * @since   1.0.0
 */

namespace RT_Movie_Library\Classes\Tmdb;

use RT_Movie_Library\Classes\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Tmdb_Client
 */
class Tmdb_Client {

	/**
	 * TMDB API v3 base URL.
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
	 * Transient TTL for upcoming movies cache.
	 *
	 * @var int
	 */
	private const TRANSIENT_TTL = 4 * HOUR_IN_SECONDS;

	/**
	 * Get upcoming movies from TMDB.
	 *
	 * @return array<int, array<string, string>>|\WP_Error
	 */
	public function get_upcoming_movies(): array|\WP_Error {
		$cached = get_transient( self::TRANSIENT_UPCOMING );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$api_key = $this->get_api_key();

		if ( is_wp_error( $api_key ) ) {
			return $api_key;
		}

		$data = $this->request_tmdb(
			'/movie/upcoming',
			array(
				'language' => 'en-US',
				'page'     => 1,
			),
			$api_key
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
			return new \WP_Error(
				'rt_tmdb_invalid_response',
				esc_html__( 'TMDB returned an unrecognised response format.', 'rt-movie-library' )
			);
		}

		$movies = $this->parse_upcoming_results( $data['results'] );

		set_transient( self::TRANSIENT_UPCOMING, $movies, self::TRANSIENT_TTL );

		return $movies;
	}

	/**
	 * Search TMDB for a movie by title and return exact title match.
	 *
	 * @param string $title Movie title.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function search_movie( string $title ): array|\WP_Error {
		$api_key = $this->get_api_key();

		if ( is_wp_error( $api_key ) ) {
			return $api_key;
		}

		$search_title = sanitize_text_field( $title );

		if ( '' === $search_title ) {
			return new \WP_Error(
				'rt_tmdb_invalid_title',
				esc_html__( 'Movie title is empty.', 'rt-movie-library' )
			);
		}

		$data = $this->request_tmdb(
			'/search/movie',
			array(
				'query'    => $search_title,
				'language' => 'en-US',
				'page'     => 1,
			),
			$api_key
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
			return new \WP_Error(
				'rt_tmdb_invalid_response',
				esc_html__( 'TMDB returned an unrecognised response format.', 'rt-movie-library' )
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

		return new \WP_Error(
			'rt_tmdb_not_found',
			sprintf(
				/* translators: %s: movie title. */
				esc_html__( 'No exact TMDB match found for "%s".', 'rt-movie-library' ),
				$search_title
			)
		);
	}

	/**
	 * Execute TMDB GET request using TMDB v3 API key query auth.
	 *
	 * @param string               $endpoint   Endpoint path, e.g. /movie/upcoming.
	 * @param array<string, mixed> $query_args Query args excluding api_key.
	 * @param string               $api_key    TMDB key from settings.
	 * @return array<string, mixed>|\WP_Error
	 */
	private function request_tmdb( string $endpoint, array $query_args, string $api_key ): array|\WP_Error {
		$base_url     = self::BASE_URL . $endpoint;
		$request_url  = add_query_arg(
			array_merge(
				$query_args,
				array(
					'api_key' => $api_key,
				)
			),
			$base_url
		);
		$request_args = array(
			'timeout'   => 3,
			'sslverify' => true,
			'headers'   => array(
				'Accept' => 'application/json',
			),
		);

		$response = wp_remote_get( $request_url, $request_args );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'rt_tmdb_request_failed',
				esc_html__( 'Could not connect to TMDB. Please try again later.', 'rt-movie-library' )
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			return new \WP_Error(
				'rt_tmdb_bad_response',
				sprintf(
					/* translators: %d: HTTP status code returned by TMDB. */
					esc_html__( 'TMDB returned an unexpected status: %d.', 'rt-movie-library' ),
					$status_code
				)
			);
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return new \WP_Error(
				'rt_tmdb_invalid_response',
				esc_html__( 'TMDB returned an unrecognised response format.', 'rt-movie-library' )
			);
		}

		return $data;
	}

	/**
	 * Fetch and sanitize API key from plugin settings.
	 *
	 * @return string|\WP_Error
	 */
	private function get_api_key(): string|\WP_Error {
		$api_key = (string) get_option( Settings::OPTION_API_KEY, '' );
		$api_key = sanitize_text_field( $api_key );

		if ( '' === $api_key ) {
			return new \WP_Error(
				'rt_tmdb_no_api_key',
				esc_html__( 'TMDB API key is not configured. Please add it in Settings.', 'rt-movie-library' )
			);
		}

		if ( 1 !== preg_match( '/^[a-f0-9]{32}$/i', $api_key ) ) {
			return new \WP_Error(
				'rt_tmdb_invalid_api_key',
				esc_html__( 'TMDB API key format looks invalid. Please use a valid v3 API key.', 'rt-movie-library' )
			);
		}

		return $api_key;
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
			'title'        => '',
			'release_date' => '',
			'vote_average' => 0.0,
			'poster_path'  => '',
		);

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

		if ( '' === $poster_path || '/' !== $poster_path[0] ) {
			return '';
		}

		return $poster_path;
	}

	/**
	 * Validate and sanitize release date format.
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
