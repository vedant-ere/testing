<?php
/**
 * Validator for CSV rows and payload.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Helpers;

use RT_Movie_Library\Classes\Cli\Exceptions\Cli_Validation_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Import_Payload_Validator
 */
class Import_Payload_Validator {

	/**
	 * Allowed content ratings for movie import.
	 *
	 * Mirrors REST validation.
	 *
	 * @var array<int, string>
	 */
	private const ALLOWED_CONTENT_RATINGS = array( 'U', 'U/A', 'G', 'PG', 'PG-13', 'R', 'NC-17' );

	/**
	 * Validate CSV header.
	 *
	 * @param array<string> $header Parsed header.
	 * @return void
	 * @throws Cli_Validation_Exception For invalid header.
	 */
	public function validate_header( array $header ): void {
		if ( Csv_Helper::HEADERS !== $header ) {
			throw new Cli_Validation_Exception( esc_html__( 'Invalid CSV header. Please use a file from `wp rt-movie export`.', 'rt-movie-library' ) );
		}
	}

	/**
	 * Validate minimum CSV columns.
	 *
	 * @param array<string> $columns Parsed columns.
	 * @param int           $row_number CSV row number.
	 * @return void
	 * @throws Cli_Validation_Exception For invalid row.
	 */
	public function validate_row_columns( array $columns, int $row_number ): void {
		if ( count( $columns ) < count( Csv_Helper::HEADERS ) ) {
			throw new Cli_Validation_Exception(
				sprintf(
					/* translators: %d: row number in CSV. */
					esc_html__( 'Skipping invalid row %d (not enough columns).', 'rt-movie-library' ),
					absint( $row_number )
				)
			);
		}
	}

	/**
	 * Validate payload array shape.
	 *
	 * @param mixed $payload Decoded payload.
	 * @return void
	 * @throws Cli_Validation_Exception For invalid payload.
	 */
	public function validate_payload( $payload ): void {
		if ( ! is_array( $payload ) || empty( $payload['post'] ) || ! is_array( $payload['post'] ) ) {
			throw new Cli_Validation_Exception( esc_html__( 'Skipping row: missing post data.', 'rt-movie-library' ) );
		}
	}

	/**
	 * Validate one parsed CSV row before import.
	 *
	 * @param array<string, string> $row        Row data.
	 * @param int                   $row_number CSV row number.
	 * @return void
	 * @throws Cli_Validation_Exception When row values are invalid.
	 */
	public function validate_row( array $row, int $row_number ): void {
		$this->validate_basic_meta_fields( $row, $row_number );
		$this->validate_media_urls( $row, $row_number );
		$this->validate_comments_json( $row, $row_number );
	}

	/**
	 * Validate rating/runtime/release-date/content-rating fields.
	 *
	 * @param array<string, string> $row        Row data.
	 * @param int                   $row_number CSV row number.
	 * @return void
	 * @throws Cli_Validation_Exception When values are invalid.
	 */
	private function validate_basic_meta_fields( array $row, int $row_number ): void {
		$rating = trim( (string) ( $row['basic_rating'] ?? '' ) );
		if ( '' !== $rating ) {
			if ( ! is_numeric( $rating ) ) {
				throw new Cli_Validation_Exception(
					sprintf(
						/* translators: %d: CSV row number. */
						esc_html__( 'Skipping row %d: basic_rating must be numeric.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}

			$rating_value = (float) $rating;

			if ( $rating_value < 1 || $rating_value > 10 ) {
				throw new Cli_Validation_Exception(
					sprintf(
						/* translators: %d: CSV row number. */
						esc_html__( 'Skipping row %d: basic_rating must be between 1 and 10.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}
		}

		$runtime = trim( (string) ( $row['basic_runtime'] ?? '' ) );
		if ( '' !== $runtime ) {
			if ( ! ctype_digit( $runtime ) ) {
				throw new Cli_Validation_Exception(
					sprintf(
						/* translators: %d: CSV row number. */
						esc_html__( 'Skipping row %d: basic_runtime must be an integer.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}

			$runtime_value = (int) $runtime;

			if ( $runtime_value < 1 || $runtime_value > 300 ) {
				throw new Cli_Validation_Exception(
					sprintf(
						/* translators: %d: CSV row number. */
						esc_html__( 'Skipping row %d: basic_runtime must be between 1 and 300.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}
		}

		$release_date = trim( (string) ( $row['basic_release_date'] ?? '' ) );
		if ( '' !== $release_date ) {
			$date = \DateTime::createFromFormat( 'Y-m-d', $release_date );

			if ( ! $date || $date->format( 'Y-m-d' ) !== $release_date ) {
				throw new Cli_Validation_Exception(
					sprintf(
						/* translators: %d: CSV row number. */
						esc_html__( 'Skipping row %d: basic_release_date must be in Y-m-d format.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}
		}

		$content_rating = trim( (string) ( $row['basic_content_rating'] ?? '' ) );
		if ( '' !== $content_rating && ! in_array( $content_rating, self::ALLOWED_CONTENT_RATINGS, true ) ) {
			throw new Cli_Validation_Exception(
				sprintf(
					/* translators: 1: CSV row number. 2: invalid value. */
					esc_html__( 'Skipping row %1$d: unsupported basic_content_rating %2$s.', 'rt-movie-library' ),
					absint( $row_number ),
					esc_html( $content_rating )
				)
			);
		}
	}

	/**
	 * Validate featured/carousel and gallery URLs.
	 *
	 * @param array<string, string> $row        Row data.
	 * @param int                   $row_number CSV row number.
	 * @return void
	 * @throws Cli_Validation_Exception When URLs are invalid.
	 */
	private function validate_media_urls( array $row, int $row_number ): void {
		$this->validate_url_value( (string) ( $row['featured_image_url'] ?? '' ), $row_number, 'featured_image_url' );
		$this->validate_url_value( (string) ( $row['carousel_url'] ?? '' ), $row_number, 'carousel_url' );

		$gallery_images = $this->decode_json_array( (string) ( $row['gallery_image_urls'] ?? '' ) );
		$gallery_videos = $this->decode_json_array( (string) ( $row['gallery_video_urls'] ?? '' ) );

		foreach ( $gallery_images as $url ) {
			$this->validate_url_value( (string) $url, $row_number, 'gallery_image_urls' );
		}

		foreach ( $gallery_videos as $url ) {
			$this->validate_url_value( (string) $url, $row_number, 'gallery_video_urls' );
		}
	}

	/**
	 * Validate comments JSON and URL-like fields inside comments/meta.
	 *
	 * @param array<string, string> $row        Row data.
	 * @param int                   $row_number CSV row number.
	 * @return void
	 * @throws Cli_Validation_Exception When comments payload is invalid.
	 */
	private function validate_comments_json( array $row, int $row_number ): void {
		$raw_comments = trim( (string) ( $row['comments_json'] ?? '' ) );
		if ( '' === $raw_comments ) {
			return;
		}

		$decoded = json_decode( $raw_comments, true );
		if ( ! is_array( $decoded ) ) {
			throw new Cli_Validation_Exception(
				sprintf(
					/* translators: %d: CSV row number. */
					esc_html__( 'Skipping row %d: comments_json must be valid JSON array.', 'rt-movie-library' ),
					absint( $row_number )
				)
			);
		}

		foreach ( $decoded as $comment ) {
			if ( ! is_array( $comment ) ) {
				continue;
			}

			$this->validate_url_value(
				(string) ( $comment['comment_author_url'] ?? '' ),
				$row_number,
				'comment_author_url'
			);

			$meta = $comment['meta'] ?? array();
			if ( ! is_array( $meta ) ) {
				continue;
			}

			foreach ( $meta as $meta_key => $meta_values ) {
				if ( ! is_string( $meta_key ) || ! is_array( $meta_values ) ) {
					continue;
				}
			}
		}
	}

	/**
	 * Validate one URL string.
	 *
	 * @param string $url URL value.
	 * @param int    $row_number CSV row number.
	 * @param string $field_name Source field.
	 * @return void
	 * @throws Cli_Validation_Exception When URL is invalid.
	 */
	private function validate_url_value( string $url, int $row_number, string $field_name ): void {
		$url = trim( $url );
		if ( '' === $url ) {
			return;
		}

		$sanitized = esc_url_raw( $url );
		$host      = wp_parse_url( $sanitized, PHP_URL_HOST );
		$scheme    = wp_parse_url( $sanitized, PHP_URL_SCHEME );

		if ( '' === $sanitized || ! is_string( $host ) || ! is_string( $scheme ) || ! in_array( strtolower( $scheme ), array( 'http', 'https' ), true ) ) {
			throw new Cli_Validation_Exception(
				sprintf(
					/* translators: 1: CSV row number. 2: field name. */
					esc_html__( 'Skipping row %1$d: %2$s must contain valid absolute URL(s).', 'rt-movie-library' ),
					absint( $row_number ),
					esc_html( $field_name )
				)
			);
		}
	}


	/**
	 * Decode JSON payload to array.
	 *
	 * @param string $json JSON string.
	 * @return array<mixed>
	 */
	private function decode_json_array( string $json ): array {
		$json = trim( $json );
		if ( '' === $json ) {
			return array();
		}

		$decoded = json_decode( $json, true );
		return is_array( $decoded ) ? $decoded : array();
	}
}
