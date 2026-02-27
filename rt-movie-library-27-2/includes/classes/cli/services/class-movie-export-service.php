<?php
/**
 * Movie export service.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Services;

use RT_Movie_Library\Classes\Cli\Helpers\Csv_Helper;
use RT_Movie_Library\Classes\Cli\Helpers\Wp_Filesystem_Helper;
use RT_Movie_Library\Classes\Cli\Helpers\Movie_Cli_Transformer;
use RT_Movie_Library\Classes\Cli\Repositories\Movie_Repository;
use RT_Movie_Library\Classes\Cli\Repositories\Person_Repository;
use RT_Movie_Library\Classes\Cli\Repositories\Attachment_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Export_Service
 */
class Movie_Export_Service {

	/**
	 * Export movies into CSV file.
	 *
	 * @param string $file_path Output path.
	 * @return array<string, mixed>
	 */
	public function export_movies( string $file_path ): array {
		$filesystem  = new Wp_Filesystem_Helper();
		$csv_helper  = new Csv_Helper();
		$repository  = new Movie_Repository();
		$transformer = new Movie_Cli_Transformer( new Person_Repository(), new Attachment_Repository() );

		$resolved_path = $filesystem->resolve_export_path( $file_path );
		$movies        = $repository->get_all_movies();

		$rows         = array();
		$warnings     = array();
		$export_count = 0;

		$rows[] = $csv_helper->to_line( Csv_Helper::HEADERS );

		foreach ( $movies as $movie_post ) {
			$row = $this->build_row( $movie_post->ID, $repository, $transformer );

			$rows[] = $csv_helper->to_line( $row );
			++$export_count;
		}

		$filesystem->write( $resolved_path, implode( '', $rows ) );

		return array(
			'count'    => $export_count,
			'path'     => $resolved_path,
			'warnings' => $warnings,
		);
	}

	/**
	 * Build CSV row for one movie.
	 *
	 * @param int                   $movie_id Movie post ID.
	 * @param Movie_Repository      $repository Movie repository.
	 * @param Movie_Cli_Transformer $transformer Data transformer.
	 * @return array<int, string>
	 */
	private function build_row( int $movie_id, Movie_Repository $repository, Movie_Cli_Transformer $transformer ): array {
		$post = get_post( $movie_id );
		$meta = $repository->get_meta( $movie_id );

		$taxonomies = $repository->get_taxonomies( $movie_id );
		$comments   = $repository->get_comments( $movie_id );

		$director_names = $this->export_meta_json_array( $transformer, 'rt-movie-meta-crew-director', $meta );
		$producer_names = $this->export_meta_json_array( $transformer, 'rt-movie-meta-crew-producer', $meta );
		$writer_names   = $this->export_meta_json_array( $transformer, 'rt-movie-meta-crew-writer', $meta );
		$actor_names    = $this->export_meta_json_array( $transformer, 'rt-movie-meta-crew-actor', $meta );

		$actor_characters = $this->export_meta_json( $transformer, 'rt-movie-meta-crew-actor-characters', $meta );
		$gallery_images   = $this->export_meta_json( $transformer, 'rt-media-meta-img', $meta );
		$gallery_videos   = $this->export_meta_json( $transformer, 'rt-media-meta-video', $meta );

		$carousel_url = $this->export_meta_string( $transformer, 'rt-movie-meta-carousel-poster', $meta );

		$featured_image_url = get_the_post_thumbnail_url( $movie_id, 'full' );
		$featured_image_url = is_string( $featured_image_url ) ? $featured_image_url : '';

		$basic_rating         = get_post_meta( $movie_id, 'rt-movie-meta-basic-rating', true );
		$basic_runtime        = get_post_meta( $movie_id, 'rt-movie-meta-basic-runtime', true );
		$basic_release_date   = get_post_meta( $movie_id, 'rt-movie-meta-basic-release-date', true );
		$basic_content_rating = get_post_meta( $movie_id, 'rt-movie-meta-basic-content-rating', true );

		$row_for_hash = array(
			'post_title' => (string) $post->post_title,
			'post_date'  => (string) $post->post_date,
			'movie_id'   => $movie_id,
		);

		$export_hash = hash( 'sha256', (string) wp_json_encode( $row_for_hash ) );

		return array(
			(string) $movie_id,
			$export_hash,
			(string) $post->post_title,
			(string) $post->post_content,
			(string) $post->post_excerpt,
			(string) $post->post_status,
			(string) $post->post_date,
			(string) (int) $post->post_author,
			$this->json_string( $taxonomies['rt-movie-genre'] ?? array() ),
			$this->json_string( $taxonomies['rt-movie-label'] ?? array() ),
			$this->json_string( $taxonomies['rt-movie-language'] ?? array() ),
			$this->json_string( $taxonomies['rt-movie-production-company'] ?? array() ),
			$this->json_string( $taxonomies['rt-movie-tag'] ?? array() ),
			$director_names,
			$producer_names,
			$writer_names,
			$actor_names,
			$actor_characters,
			(string) $basic_rating,
			(string) $basic_runtime,
			(string) $basic_release_date,
			(string) $basic_content_rating,
			$featured_image_url,
			$gallery_images,
			$gallery_videos,
			$carousel_url,
			$this->json_string( $comments ),
		);
	}

	/**
	 * Export first meta value as transformed JSON string.
	 *
	 * @param Movie_Cli_Transformer      $transformer Transformer.
	 * @param string                     $meta_key Meta key.
	 * @param array<string, array<mixed>> $meta Meta map.
	 * @return string
	 */
	private function export_meta_json( Movie_Cli_Transformer $transformer, string $meta_key, array $meta ): string {
		if ( empty( $meta[ $meta_key ] ) || ! is_array( $meta[ $meta_key ] ) ) {
			return $this->json_string( array() );
		}

		$raw = maybe_unserialize( $meta[ $meta_key ][0] );
		$out = $transformer->export_meta_value( $meta_key, $raw );

		return $this->json_string( $out );
	}

	/**
	 * Export first meta value as transformed JSON array string.
	 *
	 * @param Movie_Cli_Transformer      $transformer Transformer.
	 * @param string                     $meta_key Meta key.
	 * @param array<string, array<mixed>> $meta Meta map.
	 * @return string
	 */
	private function export_meta_json_array( Movie_Cli_Transformer $transformer, string $meta_key, array $meta ): string {
		if ( empty( $meta[ $meta_key ] ) || ! is_array( $meta[ $meta_key ] ) ) {
			return $this->json_string( array() );
		}

		$raw = maybe_unserialize( $meta[ $meta_key ][0] );
		$out = $transformer->export_meta_value( $meta_key, $raw );

		return $this->json_string( is_array( $out ) ? $out : array() );
	}

	/**
	 * Export first meta value as string.
	 *
	 * @param Movie_Cli_Transformer      $transformer Transformer.
	 * @param string                     $meta_key Meta key.
	 * @param array<string, array<mixed>> $meta Meta map.
	 * @return string
	 */
	private function export_meta_string( Movie_Cli_Transformer $transformer, string $meta_key, array $meta ): string {
		if ( empty( $meta[ $meta_key ] ) || ! is_array( $meta[ $meta_key ] ) ) {
			return '';
		}

		$raw = maybe_unserialize( $meta[ $meta_key ][0] );
		$out = $transformer->export_meta_value( $meta_key, $raw );

		return is_string( $out ) ? $out : '';
	}

	/**
	 * Encode value as JSON string.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function json_string( $value ): string {
		$encoded = wp_json_encode( $value );
		return false === $encoded ? '[]' : $encoded;
	}
}
