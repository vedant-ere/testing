<?php
/**
 * Validator for CSV rows and payload.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Helpers;

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
	 * @throws \RuntimeException For invalid header.
	 */
	public function validate_header( array $header ): void {
		if ( Csv_Helper::HEADERS !== $header ) {
			throw new \RuntimeException( __( 'Invalid CSV header. Please use a file from `wp rt-movie export`.', 'rt-movie-library' ) );
		}
	}

	/**
	 * Validate minimum CSV columns.
	 *
	 * @param array<string> $columns Parsed columns.
	 * @param int           $row_number CSV row number.
	 * @return void
	 * @throws \RuntimeException For invalid row.
	 */
	public function validate_row_columns( array $columns, int $row_number ): void {
		if ( count( $columns ) < count( Csv_Helper::HEADERS ) ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %d: row number in CSV. */
					__( 'Skipping invalid row %d (not enough columns).', 'rt-movie-library' ),
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
	 * @throws \RuntimeException For invalid payload.
	 */
	public function validate_payload( $payload ): void {
		if ( ! is_array( $payload ) || empty( $payload['post'] ) || ! is_array( $payload['post'] ) ) {
			throw new \RuntimeException( __( 'Skipping row: missing post data.', 'rt-movie-library' ) );
		}
	}

	/**
	 * Validate one parsed CSV row before import.
	 *
	 * @param array<string, string> $row        Row data.
	 * @param int                   $row_number CSV row number.
	 * @return void
	 * @throws \RuntimeException When row values are invalid.
	 */
	public function validate_row( array $row, int $row_number ): void {
		$this->validate_taxonomy_fields( $row, $row_number );
		$this->validate_basic_meta_fields( $row, $row_number );
		$this->validate_media_urls( $row, $row_number );
		$this->validate_comments_json( $row, $row_number );
	}

	/**
	 * Validate taxonomy JSON fields are arrays of non-empty strings.
	 *
	 * @param array<string, string> $row Row data.
	 * @param int                   $row_number CSV row number.
	 * @return void
	 * @throws \RuntimeException When taxonomy payload is invalid.
	 */
	private function validate_taxonomy_fields( array $row, int $row_number ): void {
		$taxonomy_fields = array(
			'genres',
			'labels',
			'languages',
			'production_companies',
			'tags',
		);

		foreach ( $taxonomy_fields as $field_name ) {
			$raw = trim( (string) ( $row[ $field_name ] ?? '' ) );
			if ( '' === $raw ) {
				continue;
			}

			$decoded = json_decode( $raw, true );
			if ( ! is_array( $decoded ) ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: 1: CSV row number. 2: field name. */
						__( 'Skipping row %1$d: %2$s must be a JSON array of strings.', 'rt-movie-library' ),
						absint( $row_number ),
						(string) $field_name
					)
				);
			}

			foreach ( $decoded as $term_value ) {
				if ( ! is_scalar( $term_value ) ) {
					throw new \RuntimeException(
						sprintf(
							/* translators: 1: CSV row number. 2: field name. */
							__( 'Skipping row %1$d: %2$s contains invalid taxonomy values.', 'rt-movie-library' ),
							absint( $row_number ),
							(string) $field_name
						)
					);
				}
			}
		}
	}

	/**
	 * Validate rating/runtime/release-date/content-rating fields.
	 *
	 * @param array<string, string> $row        Row data.
	 * @param int                   $row_number CSV row number.
	 * @return void
	 * @throws \RuntimeException When values are invalid.
	 */
	private function validate_basic_meta_fields( array $row, int $row_number ): void {
		$rating = trim( (string) ( $row['basic_rating'] ?? '' ) );
		if ( '' !== $rating ) {
			if ( ! is_numeric( $rating ) ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: %d: CSV row number. */
						__( 'Skipping row %d: basic_rating must be numeric.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}

			$rating_value = (float) $rating;

			if ( $rating_value < 1 || $rating_value > 10 ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: %d: CSV row number. */
						__( 'Skipping row %d: basic_rating must be between 1 and 10.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}
		}

		$runtime = trim( (string) ( $row['basic_runtime'] ?? '' ) );
		if ( '' !== $runtime ) {
			if ( ! ctype_digit( $runtime ) ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: %d: CSV row number. */
						__( 'Skipping row %d: basic_runtime must be an integer.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}

			$runtime_value = (int) $runtime;

			if ( $runtime_value < 1 || $runtime_value > 300 ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: %d: CSV row number. */
						__( 'Skipping row %d: basic_runtime must be between 1 and 300.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}
		}

		$release_date = trim( (string) ( $row['basic_release_date'] ?? '' ) );
		if ( '' !== $release_date ) {
			$date = \DateTime::createFromFormat( 'Y-m-d', $release_date );

			if ( ! $date || $date->format( 'Y-m-d' ) !== $release_date ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: %d: CSV row number. */
						__( 'Skipping row %d: basic_release_date must be in Y-m-d format.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}
		}

		$content_rating = trim( (string) ( $row['basic_content_rating'] ?? '' ) );
		if ( '' !== $content_rating && ! in_array( $content_rating, self::ALLOWED_CONTENT_RATINGS, true ) ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: 1: CSV row number. 2: invalid value. */
					__( 'Skipping row %1$d: unsupported basic_content_rating %2$s.', 'rt-movie-library' ),
					absint( $row_number ),
					(string) $content_rating
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
	 * @throws \RuntimeException When URLs are invalid.
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
	 * @throws \RuntimeException When comments payload is invalid.
	 */
	private function validate_comments_json( array $row, int $row_number ): void {
		$raw_comments = trim( (string) ( $row['comments_json'] ?? '' ) );
		if ( '' === $raw_comments ) {
			return;
		}

		$decoded = json_decode( $raw_comments, true );
		if ( ! is_array( $decoded ) ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %d: CSV row number. */
					__( 'Skipping row %d: comments_json must be valid JSON array.', 'rt-movie-library' ),
					absint( $row_number )
				)
			);
		}

		foreach ( $decoded as $comment ) {
			if ( ! is_array( $comment ) ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: %d: CSV row number. */
						__( 'Skipping row %d: comments_json entries must be objects.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}

			$this->validate_comment_scalar_fields( $comment, $row_number );
			$this->validate_url_value(
				(string) ( $comment['comment_author_url'] ?? '' ),
				$row_number,
				'comment_author_url'
			);

			$author_email = trim( (string) ( $comment['comment_author_email'] ?? '' ) );
			if ( '' !== $author_email && ! is_email( $author_email ) ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: %d: CSV row number. */
						__( 'Skipping row %d: comment_author_email must be a valid email.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}

			$author_ip = trim( (string) ( $comment['comment_author_IP'] ?? '' ) );
			if ( '' !== $author_ip && false === filter_var( $author_ip, FILTER_VALIDATE_IP ) ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: %d: CSV row number. */
						__( 'Skipping row %d: comment_author_IP must be a valid IP address.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}

			$comment_date = trim( (string) ( $comment['comment_date'] ?? '' ) );
			if ( '' !== $comment_date && ! $this->is_valid_comment_date( $comment_date ) ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: %d: CSV row number. */
						__( 'Skipping row %d: comment_date must be in Y-m-d H:i:s format.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}

			$approved = trim( (string) ( $comment['comment_approved'] ?? '' ) );
			if ( '' !== $approved ) {
				$allowed_approved = array( '0', '1', 'spam', 'trash', 'post-trashed' );
				if ( ! in_array( $approved, $allowed_approved, true ) ) {
					throw new \RuntimeException(
						sprintf(
							/* translators: %d: CSV row number. */
							__( 'Skipping row %d: comment_approved contains unsupported value.', 'rt-movie-library' ),
							absint( $row_number )
						)
					);
				}
			}

			$meta = $comment['meta'] ?? array();
			if ( ! is_array( $meta ) ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: %d: CSV row number. */
						__( 'Skipping row %d: comment meta must be an object.', 'rt-movie-library' ),
						absint( $row_number )
					)
				);
			}

			foreach ( $meta as $meta_key => $meta_values ) {
				if ( ! is_string( $meta_key ) || ! is_array( $meta_values ) ) {
					throw new \RuntimeException(
						sprintf(
							/* translators: %d: CSV row number. */
							__( 'Skipping row %d: comment meta must be key/value arrays.', 'rt-movie-library' ),
							absint( $row_number )
						)
					);
				}
			}
		}
	}

	/**
	 * Validate comment ID-ish scalar fields.
	 *
	 * @param array<string, mixed> $comment Comment payload.
	 * @param int                  $row_number CSV row number.
	 * @return void
	 * @throws \RuntimeException When fields are invalid.
	 */
	private function validate_comment_scalar_fields( array $comment, int $row_number ): void {
		$int_fields = array( 'comment_id', 'comment_parent_id', 'user_id' );

		foreach ( $int_fields as $field_name ) {
			if ( ! array_key_exists( $field_name, $comment ) ) {
				continue;
			}

			$value = $comment[ $field_name ];
			if ( '' === (string) $value ) {
				continue;
			}

			if ( ! is_numeric( $value ) || (int) $value < 0 ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: 1: CSV row number. 2: field name. */
						__( 'Skipping row %1$d: %2$s must be a non-negative integer.', 'rt-movie-library' ),
						absint( $row_number ),
						(string) $field_name
					)
				);
			}
		}
	}

	/**
	 * Validate comment date format and calendar/time ranges.
	 *
	 * @param string $comment_date Comment date.
	 * @return bool
	 */
	private function is_valid_comment_date( string $comment_date ): bool {
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2}):(\d{2})$/', $comment_date, $matches ) ) {
			return false;
		}

		$year   = (int) $matches[1];
		$month  = (int) $matches[2];
		$day    = (int) $matches[3];
		$hour   = (int) $matches[4];
		$minute = (int) $matches[5];
		$second = (int) $matches[6];

		if ( ! wp_checkdate( $month, $day, $year, sprintf( '%04d-%02d-%02d', $year, $month, $day ) ) ) {
			return false;
		}

		if ( $hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $second < 0 || $second > 59 ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate one URL string.
	 *
	 * @param string $url URL value.
	 * @param int    $row_number CSV row number.
	 * @param string $field_name Source field.
	 * @return void
	 * @throws \RuntimeException When URL is invalid.
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
			throw new \RuntimeException(
				sprintf(
					/* translators: 1: CSV row number. 2: field name. */
					__( 'Skipping row %1$d: %2$s must contain valid absolute URL(s).', 'rt-movie-library' ),
					absint( $row_number ),
					(string) $field_name
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
