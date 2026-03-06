<?php
/**
 * Movie repository for movie CLI.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Repositories;

use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Repository
 */
class Movie_Repository {

	/**
	 * Count movies available for export.
	 *
	 * @return int
	 */
	public function count_movies_for_export(): int {
		$query = new WP_Query(
			array(
				'post_type'              => 'rt-movie',
				'post_status'            => array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Iterate movies for export in batches to avoid loading all posts at once.
	 *
	 * @param int $batch_size Number of posts to fetch per query.
	 * @return \Generator<int, WP_Post>
	 */
	public function iterate_movies( int $batch_size = 200 ): \Generator {
		$page = 1;

		do {
			$query = new WP_Query(
				array(
					'post_type'              => 'rt-movie',
					'post_status'            => array( 'publish', 'future', 'draft', 'pending', 'private' ),
					'posts_per_page'         => max( 1, $batch_size ),
					'paged'                  => $page,
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'no_found_rows'          => false,
					'update_post_meta_cache' => true,
				)
			);

			foreach ( $query->posts as $post ) {
				if ( $post instanceof WP_Post ) {
					yield $post;
				}
			}

			++$page;
		} while ( $page <= (int) $query->max_num_pages );
	}

	/**
	 * Get movie taxonomies by term names.
	 *
	 * @param int $movie_id Movie post ID.
	 * @return array<string, array<string>>
	 */
	public function get_taxonomies( int $movie_id ): array {
		$data       = array();
		$taxonomies = get_object_taxonomies( 'rt-movie', 'names' );

		foreach ( $taxonomies as $taxonomy ) {
			if ( '_rt-movie-person' === $taxonomy ) {
				continue;
			}

			$terms = get_the_terms( $movie_id, $taxonomy );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			$data[ $taxonomy ] = array_values(
				array_filter(
					array_map(
						static fn( $term ) => isset( $term->name ) ? (string) $term->name : '',
						$terms
					)
				)
			);
		}

		return $data;
	}

	/**
	 * Get raw movie meta.
	 *
	 * @param int $movie_id Movie post ID.
	 * @return array<string, array<mixed>>
	 */
	public function get_meta( int $movie_id ): array {
		return get_post_meta( $movie_id );
	}

	/**
	 * Get movie comments with meta.
	 *
	 * @param int $movie_id Movie post ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_comments( int $movie_id ): array {
		$comments = get_comments(
			array(
				'post_id' => $movie_id,
				'status'  => 'all',
				'orderby' => 'comment_ID',
				'order'   => 'ASC',
			)
		);

		$output = array();

		foreach ( $comments as $comment ) {
			$comment_meta = get_comment_meta( $comment->comment_ID );
			$meta_output  = array();

			foreach ( $comment_meta as $meta_key => $meta_values ) {
				$meta_output[ $meta_key ] = array_map( 'maybe_unserialize', $meta_values );
			}

			$output[] = array(
				'comment_id'           => (int) $comment->comment_ID,
				'comment_author'       => (string) $comment->comment_author,
				'comment_author_email' => (string) $comment->comment_author_email,
				'comment_author_url'   => (string) $comment->comment_author_url,
				'comment_author_IP'    => (string) $comment->comment_author_IP,
				'comment_date'         => (string) $comment->comment_date,
				'comment_content'      => (string) $comment->comment_content,
				'comment_approved'     => (string) $comment->comment_approved,
				'comment_type'         => (string) $comment->comment_type,
				'user_id'              => (int) $comment->user_id,
				'comment_parent_id'    => (int) $comment->comment_parent,
				'meta'                 => $meta_output,
			);
		}

		return $output;
	}

	/**
	 * Check if movie exists by slug.
	 *
	 * @param string $slug Slug.
	 * @return bool
	 */
	public function movie_exists_by_slug( string $slug ): bool {
		$query = new WP_Query(
			array(
				'post_type'      => 'rt-movie',
				'name'           => $slug,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		return ! empty( $query->posts );
	}

	/**
	 * Insert a movie post.
	 *
	 * @param array<string, mixed> $postarr Post data.
	 * @return int|\WP_Error
	 */
	public function insert_movie( array $postarr ) {
		return wp_insert_post( $postarr, true );
	}

	/**
	 * Assign taxonomy terms by term name.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $taxonomies Taxonomies payload.
	 * @return void
	 */
	public function import_taxonomies( int $post_id, array $taxonomies ): void {
		foreach ( $taxonomies as $taxonomy => $term_names ) {
			$taxonomy = sanitize_key( (string) $taxonomy );
			if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) || ! is_array( $term_names ) ) {
				continue;
			}

			$term_ids = array();

			foreach ( $term_names as $term_name ) {
				$term_name = wp_strip_all_tags( (string) $term_name );
				$term_name = sanitize_text_field( $term_name );
				$term_name = trim( $term_name );
				if ( '' === $term_name ) {
					continue;
				}

				$existing = get_term_by( 'name', $term_name, $taxonomy );
				if ( $existing && ! is_wp_error( $existing ) ) {
					$term_ids[] = (int) $existing->term_id;
					continue;
				}

				$created = wp_insert_term( $term_name, $taxonomy );
				if ( ! is_wp_error( $created ) && isset( $created['term_id'] ) ) {
					$term_ids[] = (int) $created['term_id'];
				}
			}

			wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
		}
	}

	/**
	 * Replace all post meta from payload.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $meta_payload Meta payload.
	 * @return void
	 */
	public function import_meta( int $post_id, array $meta_payload ): void {
		foreach ( $meta_payload as $meta_key => $meta_values ) {
			if ( ! is_string( $meta_key ) || ! is_array( $meta_values ) ) {
				continue;
			}

			delete_post_meta( $post_id, $meta_key );

			foreach ( $meta_values as $value ) {
				add_post_meta( $post_id, $meta_key, $value );
			}
		}
	}

	/**
	 * Import comments for a movie.
	 *
	 * @param int                      $post_id Post ID.
	 * @param array<int, array<mixed>> $comments Comments payload.
	 * @return void
	 */
	public function import_comments( int $post_id, array $comments ): void {
		$inserted_comment_ids = array();

		foreach ( $comments as $comment_data ) {
			if ( ! is_array( $comment_data ) ) {
				continue;
			}

			$original_id     = absint( $comment_data['comment_id'] ?? 0 );
			$original_parent = absint( $comment_data['comment_parent_id'] ?? 0 );
			$parent_id       = 0;

			if ( $original_parent > 0 && isset( $inserted_comment_ids[ $original_parent ] ) ) {
				$parent_id = (int) $inserted_comment_ids[ $original_parent ];
			}

			$commentarr = array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => sanitize_text_field( (string) ( $comment_data['comment_author'] ?? '' ) ),
				'comment_author_email' => sanitize_email( (string) ( $comment_data['comment_author_email'] ?? '' ) ),
				'comment_author_url'   => esc_url_raw( (string) ( $comment_data['comment_author_url'] ?? '' ) ),
				'comment_author_IP'    => sanitize_text_field( (string) ( $comment_data['comment_author_IP'] ?? '' ) ),
				'comment_date'         => $this->sanitize_comment_date( (string) ( $comment_data['comment_date'] ?? '' ) ),
				'comment_content'      => (string) ( $comment_data['comment_content'] ?? '' ),
				'comment_approved'     => sanitize_text_field( (string) ( $comment_data['comment_approved'] ?? '1' ) ),
				'comment_type'         => sanitize_key( (string) ( $comment_data['comment_type'] ?? '' ) ),
				'user_id'              => absint( $comment_data['user_id'] ?? 0 ),
				'comment_parent'       => $parent_id,
			);

			$comment_id = wp_insert_comment( $commentarr );
			if ( ! $comment_id ) {
				continue;
			}

			if ( $original_id > 0 ) {
				$inserted_comment_ids[ $original_id ] = (int) $comment_id;
			}

			$meta = $comment_data['meta'] ?? array();
			if ( ! is_array( $meta ) ) {
				continue;
			}

			foreach ( $meta as $meta_key => $meta_values ) {
				if ( ! is_string( $meta_key ) || ! is_array( $meta_values ) ) {
					continue;
				}

				foreach ( $meta_values as $meta_value ) {
					add_comment_meta( $comment_id, $meta_key, $meta_value );
				}
			}
		}
	}

	/**
	 * Set featured image.
	 *
	 * @param int $post_id Post ID.
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public function set_featured_image( int $post_id, int $attachment_id ): void {
		if ( $attachment_id > 0 ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}
	}

	/**
	 * Sync internal shadow movie-person terms.
	 *
	 * @param int        $post_id Movie post ID.
	 * @param array<int> $person_ids Person IDs.
	 * @return void
	 */
	public function sync_shadow_terms( int $post_id, array $person_ids ): void {
		$person_ids = array_values( array_unique( array_filter( array_map( 'absint', $person_ids ) ) ) );
		$term_ids   = array();

		foreach ( $person_ids as $person_id ) {
			$person = get_post( $person_id );
			if ( ! $person || 'rt-person' !== $person->post_type ) {
				continue;
			}

			$slug = sanitize_title( $person->post_name . '-' . $person_id );
			$term = term_exists( $slug, '_rt-movie-person' );

			if ( ! $term ) {
				$term = wp_insert_term(
					$person->post_title,
					'_rt-movie-person',
					array( 'slug' => $slug )
				);
			}

			if ( is_wp_error( $term ) ) {
				continue;
			}

			if ( is_array( $term ) && isset( $term['term_id'] ) ) {
				$term_ids[] = (int) $term['term_id'];
				continue;
			}

			if ( is_int( $term ) ) {
				$term_ids[] = $term;
			}
		}

		wp_set_object_terms( $post_id, $term_ids, '_rt-movie-person', false );
	}

	/**
	 * Sanitize comment date to MySQL datetime format.
	 *
	 * Accepts only strict `Y-m-d H:i:s` (or `Y-m-dTH:i:s`) values. Invalid or
	 * missing values fall back to current site time.
	 *
	 * @param string $comment_date Raw comment date.
	 * @return string
	 */
	private function sanitize_comment_date( string $comment_date ): string {
		$comment_date = trim( $comment_date );
		if ( '' === $comment_date ) {
			return current_time( 'mysql' );
		}

		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2}):(\d{2})$/', $comment_date, $matches ) ) {
			return current_time( 'mysql' );
		}

		$year   = (int) $matches[1];
		$month  = (int) $matches[2];
		$day    = (int) $matches[3];
		$hour   = (int) $matches[4];
		$minute = (int) $matches[5];
		$second = (int) $matches[6];

		if ( ! wp_checkdate( $month, $day, $year, sprintf( '%04d-%02d-%02d', $year, $month, $day ) ) ) {
			return current_time( 'mysql' );
		}

		if ( $hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $second < 0 || $second > 59 ) {
			return current_time( 'mysql' );
		}

		return sprintf( '%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second );
	}
}
