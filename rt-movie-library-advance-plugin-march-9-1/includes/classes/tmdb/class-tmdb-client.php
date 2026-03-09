<?php
/**
 * TMDB HTTP client — shared across Dashboard Widgets and Cron Sync.
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
	 * @return array|\WP_Error
	 */
	public function get_upcoming_movies(): array|\WP_Error {
		$cached = get_transient( self::TRANSIENT_UPCOMING );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$api_key = sanitize_text_field( (string) get_option( Settings::OPTION_API_KEY, '' ) );

		if ( '' === $api_key ) {
			return new \WP_Error(
				'rt_tmdb_no_api_key',
				__( 'TMDB API key is not configured. Please add it in Settings.', 'rt-movie-library' )
			);
		}

		$url = add_query_arg(
			array(
				'api_key'  => $api_key,
				'language' => 'en-US',
				'page'     => 1,
			),
			self::BASE_URL . '/movie/upcoming'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 10,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== (int) $code ) {
			return new \WP_Error(
				'rt_tmdb_request_failed',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'TMDB returned HTTP %d. Check your API key.', 'rt-movie-library' ),
					(int) $code
				)
			);
		}

		$body    = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$results = is_array( $body ) && isset( $body['results'] ) && is_array( $body['results'] )
			? $body['results']
			: array();

		$movies = $this->parse_tmdb_results( $results );

		set_transient( self::TRANSIENT_UPCOMING, $movies, self::TRANSIENT_TTL );

		return $movies;
	}

	/**
	 * Search TMDB for a movie by title and return exact title match.
	 *
	 * @param string $title Movie title.
	 * @return array|\WP_Error
	 */
	public function search_movie( string $title ): array|\WP_Error {
		$api_key = sanitize_text_field( (string) get_option( Settings::OPTION_API_KEY, '' ) );

		if ( '' === $api_key ) {
			return new \WP_Error(
				'rt_tmdb_no_api_key',
				__( 'TMDB API key is not configured.', 'rt-movie-library' )
			);
		}

		$url = add_query_arg(
			array(
				'api_key'  => $api_key,
				'query'    => $title,
				'language' => 'en-US',
				'page'     => 1,
			),
			self::BASE_URL . '/search/movie'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 10,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== (int) $code ) {
			return new \WP_Error(
				'rt_tmdb_request_failed',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'TMDB returned HTTP %d.', 'rt-movie-library' ),
					(int) $code
				)
			);
		}

		$body    = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$results = is_array( $body ) && isset( $body['results'] ) && is_array( $body['results'] )
			? $body['results']
			: array();

		$normalized_query = strtolower( trim( $title ) );

		foreach ( $results as $movie ) {
			if ( ! is_array( $movie ) ) {
				continue;
			}

			$candidate = strtolower( trim( (string) ( $movie['title'] ?? '' ) ) );

			if ( $candidate === $normalized_query ) {
				return $movie;
			}
		}

		return new \WP_Error(
			'rt_tmdb_not_found',
			sprintf(
				/* translators: %s: movie title */
				__( 'No exact TMDB match found for "%s".', 'rt-movie-library' ),
				$title
			)
		);
	}

	/**
	 * Parse and sanitize raw TMDB result items for upcoming list.
	 *
	 * @param array<int, mixed> $results Raw TMDB results.
	 * @return array<int, array<string, string>>
	 */
	private function parse_tmdb_results( array $results ): array {
		$movies = array();

		foreach ( $results as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$title = isset( $item['title'] ) ? sanitize_text_field( (string) $item['title'] ) : '';

			if ( '' === $title ) {
				continue;
			}

			$release_date = isset( $item['release_date'] )
				? sanitize_text_field( (string) $item['release_date'] )
				: '';

			$movies[] = array(
				'title'        => $title,
				'release_date' => $release_date,
			);
		}

		return $movies;
	}
}
