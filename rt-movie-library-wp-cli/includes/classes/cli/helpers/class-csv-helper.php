<?php
/**
 * CSV helper for movie CLI import/export.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Csv_Helper
 */
class Csv_Helper {

	/**
	 * CSV header.
	 *
	 * @var array<string>
	 */
	public const HEADERS = array(
		'export_id',
		'export_hash',
		'post_title',
		'post_content',
		'post_excerpt',
		'post_status',
		'post_date',
		'post_author',
		'genres',
		'labels',
		'languages',
		'production_companies',
		'tags',
		'director_names',
		'producer_names',
		'writer_names',
		'actor_names',
		'actor_characters',
		'basic_rating',
		'basic_runtime',
		'basic_release_date',
		'basic_content_rating',
		'featured_image_url',
		'gallery_image_urls',
		'gallery_video_urls',
		'carousel_url',
		'comments_json',
	);

	/**
	 * Build one CSV line.
	 *
	 * @param array<mixed> $values Row values.
	 * @return string
	 */
	public function to_line( array $values ): string {
		$escaped = array_map(
			static function ( $value ): string {
				$value = (string) $value;
				$value = str_replace( '"', '""', $value );
				return '"' . $value . '"';
			},
			$values
		);

		return implode( ',', $escaped ) . "\n";
	}

	/**
	 * Parse one CSV line.
	 *
	 * @param string $line CSV line.
	 * @return array<string>
	 */
	public function parse_line( string $line ): array {
		$parsed = str_getcsv( $line );
		return is_array( $parsed ) ? $parsed : array();
	}

	/**
	 * Split CSV content into logical CSV rows.
	 *
	 * Handles quoted multiline fields so import does not break when
	 * post content/excerpt/comments contain line breaks.
	 *
	 * @param string $contents File content.
	 * @return array<string>
	 */
	public function split_lines( string $contents ): array {
		$contents = str_replace( array( "\r\n", "\r" ), "\n", $contents );
		$length   = strlen( $contents );

		$rows      = array();
		$current   = '';
		$in_quotes = false;

		for ( $index = 0; $index < $length; $index++ ) {
			$char = $contents[ $index ];

			if ( '"' === $char ) {
				$next_char = ( $index + 1 < $length ) ? $contents[ $index + 1 ] : '';

				if ( $in_quotes && '"' === $next_char ) {
					$current .= '""';
					++$index;
					continue;
				}

				$in_quotes = ! $in_quotes;
				$current  .= $char;
				continue;
			}

			if ( "\n" === $char && ! $in_quotes ) {
				if ( '' !== $current ) {
					$rows[]  = $current;
					$current = '';
				}
				continue;
			}

			$current .= $char;
		}

		if ( '' !== $current ) {
			$rows[] = $current;
		}

		return $rows;
	}
}
