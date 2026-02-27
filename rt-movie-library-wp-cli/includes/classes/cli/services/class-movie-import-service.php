<?php
/**
 * Movie import service.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Services;

use RT_Movie_Library\Classes\Cli\Helpers\Csv_Helper;
use RT_Movie_Library\Classes\Cli\Helpers\Wp_Filesystem_Helper;
use RT_Movie_Library\Classes\Cli\Helpers\Movie_Cli_Transformer;
use RT_Movie_Library\Classes\Cli\Helpers\Import_Payload_Validator;
use RT_Movie_Library\Classes\Cli\Repositories\Movie_Repository;
use RT_Movie_Library\Classes\Cli\Repositories\Person_Repository;
use RT_Movie_Library\Classes\Cli\Repositories\Attachment_Repository;
use RT_Movie_Library\Classes\Cli\Exceptions\Cli_Validation_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Import_Service
 */
class Movie_Import_Service {

	/**
	 * Import movies from CSV.
	 *
	 * @param string $file_path CSV file path.
	 * @return array<string, mixed>
	 * @throws Cli_Validation_Exception When import input is invalid.
	 */
	public function import_movies( string $file_path ): array {
		$filesystem            = new Wp_Filesystem_Helper();
		$csv_helper            = new Csv_Helper();
		$validator             = new Import_Payload_Validator();
		$repository            = new Movie_Repository();
		$attachment_repository = new Attachment_Repository();
		$transformer           = new Movie_Cli_Transformer( new Person_Repository(), $attachment_repository );

		if ( ! $filesystem->exists( $file_path ) ) {
			throw new Cli_Validation_Exception(
				sprintf(
					/* translators: %s: import file path. */
					esc_html__( 'Import file not found: %s', 'rt-movie-library' ),
					esc_html( $file_path )
				)
			);
		}

		$contents = $filesystem->read( $file_path );
		if ( '' === trim( $contents ) ) {
			throw new Cli_Validation_Exception( esc_html__( 'Import file is empty or unreadable.', 'rt-movie-library' ) );
		}

		$lines = $csv_helper->split_lines( $contents );
		if ( empty( $lines ) ) {
			throw new Cli_Validation_Exception( esc_html__( 'Import file has no parsable rows.', 'rt-movie-library' ) );
		}

		$header = $csv_helper->parse_line( (string) array_shift( $lines ) );
		$validator->validate_header( $header );

		$created  = 0;
		$skipped  = 0;
		$failed   = 0;
		$warnings = array();

		foreach ( $lines as $index => $line ) {
			if ( '' === trim( (string) $line ) ) {
				continue;
			}

			$row_number = $index + 2;
			$columns    = $csv_helper->parse_line( $line );

			try {
				$validator->validate_row_columns( $columns, $row_number );
			} catch ( Cli_Validation_Exception $exception ) {
				++$failed;
				$warnings[] = $exception->getMessage();
				continue;
			}

			$row = $this->map_row( $header, $columns );

			try {
				$validator->validate_row( $row, $row_number );
			} catch ( Cli_Validation_Exception $exception ) {
				++$failed;
				$warnings[] = $exception->getMessage();
				continue;
			}

			$result = $this->import_row( $row, $repository, $transformer, $attachment_repository, $warnings );

			if ( 'created' === $result ) {
				++$created;
			} elseif ( 'skipped' === $result ) {
				++$skipped;
			} else {
				++$failed;
			}
		}

		return array(
			'created'  => $created,
			'skipped'  => $skipped,
			'failed'   => $failed,
			'warnings' => $warnings,
		);
	}

	/**
	 * Map CSV columns to associative row.
	 *
	 * @param array<string> $header Header row.
	 * @param array<string> $columns Data row.
	 * @return array<string, string>
	 */
	private function map_row( array $header, array $columns ): array {
		$mapped = array();

		foreach ( $header as $index => $column_name ) {
			$mapped[ $column_name ] = (string) ( $columns[ $index ] ?? '' );
		}

		return $mapped;
	}

	/**
	 * Import one CSV row.
	 *
	 * @param array<string, string> $row Row data.
	 * @param Movie_Repository      $repository Movie repository.
	 * @param Movie_Cli_Transformer $transformer Transformer.
	 * @param Attachment_Repository $attachment_repository Attachment repository.
	 * @param array<int, string>    $warnings Warning collector.
	 * @return string created|skipped|failed
	 */
	private function import_row( array $row, Movie_Repository $repository, Movie_Cli_Transformer $transformer, Attachment_Repository $attachment_repository, array &$warnings ): string {
		$post_title = sanitize_text_field( $row['post_title'] ?? '' );
		$slug       = sanitize_title( $post_title );

		if ( '' === $slug ) {
			$warnings[] = __( 'Skipping row: missing valid post slug.', 'rt-movie-library' );
			return 'failed';
		}

		if ( $repository->movie_exists_by_slug( $slug ) ) {
			$warnings[] = sprintf(
				/* translators: %s: movie slug. */
				__( 'Skipping duplicate movie: %s', 'rt-movie-library' ),
				$slug
			);
			return 'skipped';
		}

		$postarr = array(
			'post_type'      => 'rt-movie',
			'post_title'     => $post_title,
			'post_name'      => $slug,
			'post_content'   => (string) ( $row['post_content'] ?? '' ),
			'post_excerpt'   => (string) ( $row['post_excerpt'] ?? '' ),
			'post_status'    => sanitize_key( (string) ( $row['post_status'] ?? 'draft' ) ),
			'post_date'      => (string) ( $row['post_date'] ?? current_time( 'mysql' ) ),
			'post_author'    => absint( $row['post_author'] ?? 0 ),
			'comment_status' => 'open',
			'ping_status'    => 'closed',
		);

		$post_id = $repository->insert_movie( $postarr );

		if ( is_wp_error( $post_id ) ) {
			$warnings[] = sprintf(
				/* translators: 1: title. 2: error message. */
				__( 'Failed to import movie %1$s: %2$s', 'rt-movie-library' ),
				$post_title,
				$post_id->get_error_message()
			);
			return 'failed';
		}

		$post_id = (int) $post_id;

		$repository->import_taxonomies( $post_id, $this->build_taxonomy_payload( $row ) );

		$meta = $this->build_meta_payload( $row, $transformer );
		$repository->import_meta( $post_id, $meta );

		$comments = $this->decode_json_array( $row['comments_json'] ?? '', true );
		$repository->import_comments( $post_id, is_array( $comments ) ? $comments : array() );

		$this->sync_shadow_terms( $post_id, $meta, $transformer, $repository );

		$featured_image_url = esc_url_raw( (string) ( $row['featured_image_url'] ?? '' ) );
		if ( '' !== $featured_image_url ) {
			$attachment_id = $attachment_repository->id_from_url( $featured_image_url );
			if ( $attachment_id > 0 ) {
				$repository->set_featured_image( $post_id, $attachment_id );
			} else {
				$warnings[] = sprintf(
					/* translators: %s: image URL. */
					__( 'Could not resolve featured image URL: %s', 'rt-movie-library' ),
					$featured_image_url
				);
			}
		}

		return 'created';
	}

	/**
	 * Build taxonomy payload from CSV row.
	 *
	 * @param array<string, string> $row Row data.
	 * @return array<string, array<string>>
	 */
	private function build_taxonomy_payload( array $row ): array {
		return array(
			'rt-movie-genre'              => $this->decode_json_string_array( $row['genres'] ?? '' ),
			'rt-movie-label'              => $this->decode_json_string_array( $row['labels'] ?? '' ),
			'rt-movie-language'           => $this->decode_json_string_array( $row['languages'] ?? '' ),
			'rt-movie-production-company' => $this->decode_json_string_array( $row['production_companies'] ?? '' ),
			'rt-movie-tag'                => $this->decode_json_string_array( $row['tags'] ?? '' ),
		);
	}

	/**
	 * Build transformed meta payload from CSV row.
	 *
	 * @param array<string, string> $row Row data.
	 * @param Movie_Cli_Transformer $transformer Transformer.
	 * @return array<string, array<mixed>>
	 */
	private function build_meta_payload( array $row, Movie_Cli_Transformer $transformer ): array {
		$meta = array();

		$meta['rt-movie-meta-crew-director']         = array(
			$transformer->import_meta_value( 'rt-movie-meta-crew-director', $this->decode_json_string_array( $row['director_names'] ?? '' ) ),
		);
		$meta['rt-movie-meta-crew-producer']         = array(
			$transformer->import_meta_value( 'rt-movie-meta-crew-producer', $this->decode_json_string_array( $row['producer_names'] ?? '' ) ),
		);
		$meta['rt-movie-meta-crew-writer']           = array(
			$transformer->import_meta_value( 'rt-movie-meta-crew-writer', $this->decode_json_string_array( $row['writer_names'] ?? '' ) ),
		);
		$meta['rt-movie-meta-crew-actor']            = array(
			$transformer->import_meta_value( 'rt-movie-meta-crew-actor', $this->decode_json_string_array( $row['actor_names'] ?? '' ) ),
		);
		$meta['rt-movie-meta-crew-actor-characters'] = array(
			$transformer->import_meta_value( 'rt-movie-meta-crew-actor-characters', $this->decode_json_array( $row['actor_characters'] ?? '', true ) ),
		);

		$meta['rt-media-meta-img']             = array(
			$transformer->import_meta_value( 'rt-media-meta-img', $this->decode_json_string_array( $row['gallery_image_urls'] ?? '' ) ),
		);
		$meta['rt-media-meta-video']           = array(
			$transformer->import_meta_value( 'rt-media-meta-video', $this->decode_json_string_array( $row['gallery_video_urls'] ?? '' ) ),
		);
		$meta['rt-movie-meta-carousel-poster'] = array(
			$transformer->import_meta_value( 'rt-movie-meta-carousel-poster', (string) ( $row['carousel_url'] ?? '' ) ),
		);

		$rating = trim( (string) ( $row['basic_rating'] ?? '' ) );
		if ( '' !== $rating ) {
			$meta['rt-movie-meta-basic-rating'] = array( $rating );
		}

		$runtime = trim( (string) ( $row['basic_runtime'] ?? '' ) );
		if ( '' !== $runtime ) {
			$meta['rt-movie-meta-basic-runtime'] = array( $runtime );
		}

		$release_date = trim( (string) ( $row['basic_release_date'] ?? '' ) );
		if ( '' !== $release_date ) {
			$meta['rt-movie-meta-basic-release-date'] = array( $release_date );
		}

		$content_rating = trim( (string) ( $row['basic_content_rating'] ?? '' ) );
		if ( '' !== $content_rating ) {
			$meta['rt-movie-meta-basic-content-rating'] = array( $content_rating );
		}

		return $meta;
	}

	/**
	 * Decode JSON as array.
	 *
	 * @param string $json JSON string.
	 * @param bool   $assoc Decode as associative array.
	 * @return mixed
	 */
	private function decode_json_array( string $json, bool $assoc = false ) {
		$json = trim( $json );
		if ( '' === $json ) {
			return array();
		}

		$decoded = json_decode( $json, $assoc );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Decode JSON array of strings.
	 *
	 * @param string $json JSON string.
	 * @return array<string>
	 */
	private function decode_json_string_array( string $json ): array {
		$decoded = $this->decode_json_array( $json, true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$output = array();
		foreach ( $decoded as $value ) {
			$value = sanitize_text_field( (string) $value );
			if ( '' !== $value ) {
				$output[] = $value;
			}
		}

		return array_values( array_unique( $output ) );
	}

	/**
	 * Sync movie-person shadow terms from transformed crew meta.
	 *
	 * @param int                   $post_id Movie post ID.
	 * @param array<string, mixed>  $meta Transformed meta payload.
	 * @param Movie_Cli_Transformer $transformer Transformer.
	 * @param Movie_Repository      $repository Movie repository.
	 * @return void
	 */
	private function sync_shadow_terms( int $post_id, array $meta, Movie_Cli_Transformer $transformer, Movie_Repository $repository ): void {
		$person_meta_keys = array(
			'rt-movie-meta-crew-director',
			'rt-movie-meta-crew-producer',
			'rt-movie-meta-crew-writer',
			'rt-movie-meta-crew-actor',
		);

		$person_ids = array();

		foreach ( $person_meta_keys as $meta_key ) {
			if ( empty( $meta[ $meta_key ] ) || ! is_array( $meta[ $meta_key ] ) ) {
				continue;
			}

			$first_value = (string) $meta[ $meta_key ][0];
			$ids         = $transformer->decode_id_list( $first_value );
			$person_ids  = array_merge( $person_ids, $ids );
		}

		$repository->sync_shadow_terms( $post_id, $person_ids );
	}
}
