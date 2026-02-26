<?php
/**
 * Data transformer for movie CLI import/export.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Helpers;

use RT_Movie_Library\Classes\Cli\Repositories\Person_Repository;
use RT_Movie_Library\Classes\Cli\Repositories\Attachment_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Cli_Transformer
 */
class Movie_Cli_Transformer {

	/**
	 * Person repository.
	 *
	 * @var Person_Repository
	 */
	private Person_Repository $person_repository;

	/**
	 * Attachment repository.
	 *
	 * @var Attachment_Repository
	 */
	private Attachment_Repository $attachment_repository;

	/**
	 * Constructor.
	 *
	 * @param Person_Repository     $person_repository Person repository.
	 * @param Attachment_Repository $attachment_repository Attachment repository.
	 */
	public function __construct( Person_Repository $person_repository, Attachment_Repository $attachment_repository ) {
		$this->person_repository     = $person_repository;
		$this->attachment_repository = $attachment_repository;
	}

	/**
	 * Transform meta value for export.
	 *
	 * @param string $meta_key Meta key.
	 * @param mixed  $value Meta value.
	 * @return mixed
	 */
	public function export_meta_value( string $meta_key, $value ) {
		$crew_roles = array(
			'rt-movie-meta-crew-director' => 'director',
			'rt-movie-meta-crew-producer' => 'producer',
			'rt-movie-meta-crew-writer'   => 'writer',
			'rt-movie-meta-crew-actor'    => 'actor',
		);

		if ( isset( $crew_roles[ $meta_key ] ) ) {
			$ids = $this->decode_id_list( $value );
			return $this->person_repository->person_ids_to_names( $ids );
		}

		if ( 'rt-movie-meta-crew-actor-characters' === $meta_key ) {
			$decoded = is_array( $value ) ? $value : json_decode( (string) $value, true );
			if ( ! is_array( $decoded ) ) {
				return array();
			}

			$characters = array();
			foreach ( $decoded as $person_id => $character_name ) {
				$name = get_the_title( absint( $person_id ) );
				if ( '' === (string) $name ) {
					continue;
				}

				$characters[] = array(
					'actor'     => (string) $name,
					'character' => (string) $character_name,
				);
			}

			return $characters;
		}

		if ( 'rt-movie-meta-carousel-poster' === $meta_key || '_thumbnail_id' === $meta_key ) {
			$url = $this->attachment_repository->url_from_id( absint( $value ) );
			return is_string( $url ) ? $url : '';
		}

		if ( 'rt-media-meta-img' === $meta_key || 'rt-media-meta-video' === $meta_key ) {
			$ids = $this->decode_id_list( $value );
			return $this->attachment_repository->attachment_ids_to_urls( $ids );
		}

		return $value;
	}

	/**
	 * Transform meta value for import.
	 *
	 * @param string $meta_key Meta key.
	 * @param mixed  $value Meta value.
	 * @return mixed
	 */
	public function import_meta_value( string $meta_key, $value ) {
		$role_map = array(
			'rt-movie-meta-crew-director' => 'director',
			'rt-movie-meta-crew-producer' => 'producer',
			'rt-movie-meta-crew-writer'   => 'writer',
			'rt-movie-meta-crew-actor'    => 'actor',
		);

		if ( isset( $role_map[ $meta_key ] ) ) {
			$ids = array();

			if ( is_array( $value ) ) {
				foreach ( $value as $person_name ) {
					$person_id = $this->person_repository->find_or_create_person( (string) $person_name, $role_map[ $meta_key ] );
					if ( $person_id > 0 ) {
						$ids[] = $person_id;
					}
				}
			}

			return wp_json_encode( array_values( array_unique( $ids ) ) );
		}

		if ( 'rt-movie-meta-crew-actor-characters' === $meta_key ) {
			$output = array();

			if ( is_array( $value ) ) {
				foreach ( $value as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}

					$actor_name = sanitize_text_field( (string) ( $row['actor'] ?? '' ) );
					$character  = sanitize_text_field( (string) ( $row['character'] ?? '' ) );

					if ( '' === $actor_name || '' === $character ) {
						continue;
					}

					$person_id = $this->person_repository->find_or_create_person( $actor_name, 'actor' );
					if ( $person_id > 0 ) {
						$output[ $person_id ] = $character;
					}
				}
			}

			return wp_json_encode( $output );
		}

		if ( 'rt-movie-meta-carousel-poster' === $meta_key || '_thumbnail_id' === $meta_key ) {
			$url = esc_url_raw( (string) $value );
			if ( '' === $url ) {
				return '';
			}

			return (string) $this->attachment_repository->id_from_url( $url );
		}

		if ( 'rt-media-meta-img' === $meta_key || 'rt-media-meta-video' === $meta_key ) {
			$ids = array();

			if ( is_array( $value ) ) {
				foreach ( $value as $url ) {
					$attachment_id = $this->attachment_repository->id_from_url( esc_url_raw( (string) $url ) );
					if ( $attachment_id > 0 ) {
						$ids[] = $attachment_id;
					}
				}
			}

			return wp_json_encode( array_values( array_unique( $ids ) ) );
		}

		return $value;
	}

	/**
	 * Decode ID list from JSON/array/scalar values.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int>
	 */
	public function decode_id_list( $value ): array {
		if ( is_array( $value ) ) {
			return array_values( array_filter( array_map( 'absint', $value ) ) );
		}

		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				return array_values( array_filter( array_map( 'absint', $decoded ) ) );
			}

			if ( is_numeric( $value ) ) {
				$id = absint( $value );
				return $id > 0 ? array( $id ) : array();
			}
		}

		return array();
	}
}
