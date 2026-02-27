<?php
/**
 * Person repository for movie CLI.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Repositories;

use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class Person_Repository
 */
class Person_Repository {

	/**
	 * Resolve person IDs to names.
	 *
	 * @param array<int> $ids Person IDs.
	 * @return array<string>
	 */
	public function person_ids_to_names( array $ids ): array {
		$names = array();

		foreach ( $ids as $id ) {
			$name = get_the_title( absint( $id ) );
			if ( is_string( $name ) && '' !== $name ) {
				$names[] = $name;
			}
		}

		return array_values( array_unique( $names ) );
	}

	/**
	 * Find or create person by name and assign career.
	 *
	 * @param string $person_name Person name.
	 * @param string $role Role slug.
	 * @return int
	 */
	public function find_or_create_person( string $person_name, string $role ): int {
		$person_name = sanitize_text_field( $person_name );
		if ( '' === $person_name ) {
			return 0;
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'rt-person',
				's'              => $person_name,
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'fields'         => 'all',
				'no_found_rows'  => true,
			)
		);

		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $person_post ) {
				if ( 0 === strcasecmp( (string) $person_post->post_title, $person_name ) ) {
					$person_id = (int) $person_post->ID;
					$this->assign_person_career( $person_id, $role );
					return $person_id;
				}
			}
		}

		$person_id = wp_insert_post(
			array(
				'post_type'   => 'rt-person',
				'post_status' => 'publish',
				'post_title'  => $person_name,
			),
			true
		);

		if ( is_wp_error( $person_id ) ) {
			return 0;
		}

		$this->assign_person_career( (int) $person_id, $role );
		return (int) $person_id;
	}

	/**
	 * Assign person career taxonomy term.
	 *
	 * @param int    $person_id Person ID.
	 * @param string $role Role slug.
	 * @return void
	 */
	private function assign_person_career( int $person_id, string $role ): void {
		$allowed = array(
			'director' => 'director',
			'producer' => 'producer',
			'writer'   => 'writer',
			'actor'    => 'actor',
		);

		$slug = $allowed[ $role ] ?? '';
		if ( '' === $slug ) {
			return;
		}

		$term = get_term_by( 'slug', $slug, 'rt-person-career' );

		if ( ! $term || is_wp_error( $term ) ) {
			$inserted = wp_insert_term(
				ucfirst( $slug ),
				'rt-person-career',
				array( 'slug' => $slug )
			);

			if ( is_wp_error( $inserted ) || empty( $inserted['term_id'] ) ) {
				return;
			}

			$term_id = (int) $inserted['term_id'];
		} else {
			$term_id = (int) $term->term_id;
		}

		wp_set_object_terms( $person_id, array( $term_id ), 'rt-person-career', true );
	}
}
