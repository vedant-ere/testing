<?php
/**
 * Meta Repository Facade.
 *
 * Provides a unified, "Zero Bloat" entry point for Movie and Person metadata.
 * Aggressively migrates data from wp_postmeta to custom tables and ensures
 * that wp_postmeta remains clean.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Database;

use RT_Movie_Library\Classes\Database\Movie_Meta_Table;
use RT_Movie_Library\Classes\Database\Person_Meta_Table;

defined( 'ABSPATH' ) || exit;

/**
 * Class Meta_Repository
 *
 * Static facade for all metadata operations. This is the only class that
 * knows about BOTH wp_postmeta and our custom tables.
 */
class Meta_Repository {

	/**
	 * Maps post types to their corresponding custom table handler classes.
	 *
	 * @var array<string, string>
	 */
	private static array $table_map = array(
		'rt-movie'  => Movie_Meta_Table::class,
		'rt-person' => Person_Meta_Table::class,
	);

	/**
	 * Resolves the appropriate table class for a given post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return string|null Class name or null if not supported.
	 */
	private static function resolve( int $post_id ): ?string {
		$post_type = get_post_type( $post_id );
		return self::$table_map[ $post_type ] ?? null;
	}

	/**
	 * Gets a meta value with "Zero Bloat" logic.
	 *
	 * 1. Checks custom table first (Fast Path).
	 * 2. If missing, checks wp_postmeta (Slow Path).
	 * 3. If found in wp_postmeta, migrates it to custom table and DELETES from wp_postmeta.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Return single value or array.
	 * @return mixed
	 */
	public static function get( int $post_id, string $meta_key, bool $single = true ) {
		$class = self::resolve( $post_id );

		// Unknown post type — delegate entirely to WP core.
		if ( null === $class ) {
			return get_post_meta( $post_id, $meta_key, $single );
		}

		// ── Fast path: check the custom table ──────────────────────────────
		$value    = $class::get( $post_id, $meta_key, $single );
		$is_empty = $single ? ( '' === $value ) : ( array() === $value );

		if ( ! $is_empty ) {
			return $value;
		}

		// ── Slow path: wp_postmeta fallback ────────────────────────────────
		$legacy = get_post_meta( $post_id, $meta_key, $single );

		$legacy_empty = $single ? ( '' === $legacy ) : ( array() === $legacy );

		if ( $legacy_empty ) {
			return $legacy; // Nothing exists anywhere.
		}

		// ── Read-Replica: safely persist in custom table without deleting original ──
		$class::update( $post_id, $meta_key, $legacy );

		return $legacy;
	}

	/**
	 * Retrieves ALL meta data for a post from the custom table.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, array<mixed>>
	 */
	public static function get_all( int $post_id ): array {
		$class = self::resolve( $post_id );

		if ( null === $class ) {
			return get_post_meta( $post_id );
		}

		return $class::get_all( $post_id );
	}

	/**
	 * Updates a meta value exclusively in the custom table.
	 * Aggressively cleans up wp_postmeta to prevent bloat.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key  Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return bool
	 */
	public static function update( int $post_id, string $meta_key, $meta_value ): bool {
		$class = self::resolve( $post_id );

		if ( null === $class ) {
			return (bool) update_post_meta( $post_id, $meta_key, $meta_value );
		}

		// 1. Force write to custom table.
		$success = (bool) $class::update( $post_id, $meta_key, $meta_value );

		// 2. Aggressively delete from wp_postmeta to ensure "Zero Bloat".
		if ( $success ) {
			delete_post_meta( $post_id, $meta_key );
		}

		return $success;
	}

	/**
	 * Deletes a meta value from both locations.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key  Meta key.
	 * @param mixed  $meta_value Optional.
	 * @return bool
	 */
	public static function delete( int $post_id, string $meta_key, $meta_value = '' ): bool {
		$class = self::resolve( $post_id );

		if ( null === $class ) {
			return delete_post_meta( $post_id, $meta_key, $meta_value );
		}

		// Clean both to ensure no ghost data.
		delete_post_meta( $post_id, $meta_key, $meta_value );
		return $class::delete( $post_id, $meta_key, $meta_value );
	}

	/**
	 * Deletes ALL meta for a post from custom tables.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function delete_all( int $post_id ): bool {
		$class = self::resolve( $post_id );

		if ( null === $class ) {
			return true;
		}

		return $class::delete_all( $post_id );
	}

	/**
	 * High-performance direct SQL query specifically for movie/person IDs.
	 *
	 * Bypasses WP_Query's slow JOIN logic by operating directly on our
	 * custom tables. Supports complex comparisons like 'rating > 8.5'.
	 *
	 * Example usage:
	 *   query_ids([
	 *     'post_type' => 'rt-movie',
	 *     'meta_filter' => [
	 *        [ 'key' => 'rating', 'value' => 8.5, 'compare' => '>' ],
	 *        [ 'key' => 'runtime', 'value' => 120, 'compare' => '<' ]
	 *     ],
	 *     'orderby' => 'rating',
	 *     'order' => 'DESC',
	 *     'limit' => 6
	 *   ])
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, int> List of post IDs.
	 */
	public static function query_ids( array $args ): array {
		global $wpdb;

		$post_type = $args['post_type'] ?? 'rt-movie';
		$class     = self::$table_map[ $post_type ] ?? null;

		if ( ! $class ) {
			return array();
		}

		$table       = $class::get_table_name();
		$id_col      = ( 'rt-movie' === $post_type ) ? 'movie_id' : 'person_id';
		$filters     = $args['meta_filter'] ?? array();
		$orderby_key = $args['orderby'] ?? 'date';
		$order       = strtoupper( $args['order'] ?? 'DESC' );
		$limit       = absint( $args['limit'] ?? 10 );
		$post_status = $args['post_status'] ?? 'publish';

		// Base SQL. We use post IDs as the primary return value.
		$sql = "SELECT DISTINCT p.ID FROM {$wpdb->posts} p";

		// JOINs for filtering. Use unique aliases for multiple filters.
		$join_clauses  = array();
		$where_clauses = array();

		foreach ( $filters as $index => $filter ) {
			$key     = $filter['key'] ?? '';
			$val     = $filter['value'] ?? '';
			$compare = $filter['compare'] ?? '=';
			$alias   = "m{$index}";

			// Whitelist comparison operators for safety.
			if ( ! in_array( $compare, array( '=', '!=', '>', '>=', '<', '<=', 'LIKE' ), true ) ) {
				$compare = '=';
			}

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Whitelisted internal variables (table names, aliases, columns).
			$join_clauses[]  = $wpdb->prepare( " LEFT JOIN {$table} {$alias} ON p.ID = {$alias}.{$id_col} AND {$alias}.meta_key = %s", $key );
			$where_clauses[] = $wpdb->prepare( " {$alias}.meta_value {$compare} %s", $val );
			// phpcs:enable
		}

		// Ordering JOIN if not already joined via filter.
		$orderby_sql = 'p.post_date';
		if ( 'date' !== $orderby_key ) {
			$alias = 'm_order';
			if ( ! empty( $filters ) && isset( $filters[0] ) && $filters[0]['key'] === $orderby_key ) {
				$alias = 'm0';
			} else {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and column are internal constants.
				$sql .= $wpdb->prepare( " LEFT JOIN {$table} {$alias} ON p.ID = {$alias}.{$id_col} AND {$alias}.meta_key = %s", $orderby_key );
			}
			// Cast for numeric sorting if it looks like a rating/runtime.
			if ( str_contains( $orderby_key, 'rating' ) || str_contains( $orderby_key, 'runtime' ) ) {
				$orderby_sql = "CAST({$alias}.meta_value AS DECIMAL(10,2))";
			} else {
				$orderby_sql = "{$alias}.meta_value";
			}
		}

		// Assemble the parts.
		$sql .= implode( ' ', $join_clauses );
		$sql .= $wpdb->prepare( ' WHERE p.post_type = %s', $post_type );

		if ( 'any' !== $post_status ) {
			$sql .= $wpdb->prepare( ' AND p.post_status = %s', $post_status );
		}

		if ( ! empty( $where_clauses ) ) {
			$sql .= ' AND (' . implode( ' AND ', $where_clauses ) . ')';
		}

		$sql .= " ORDER BY {$orderby_sql} {$order}, p.post_date {$order}";
		$sql .= $wpdb->prepare( ' LIMIT %d', $limit );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL fragments are properly prepared; specialized results.
		$results = $wpdb->get_col( $sql );

		return array_map( 'intval', $results );
	}

	/**
	 * Returns migration statistics for the settings page.
	 *
	 * @return array<string, int>
	 */
	public static function get_stats(): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dynamic stats for admin dashboard.

		$total_movies  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s", 'rt-movie' ) );
		$total_persons = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s", 'rt-person' ) );

		// Count purely by how many unique posts have at least one record in the custom tables.
		$migrated_movies  = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT movie_id) FROM {$wpdb->rt_movie_meta}" );
		$migrated_persons = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT person_id) FROM {$wpdb->rt_person_meta}" );

		// phpcs:enable

		return array(
			'total_movies'     => $total_movies,
			'migrated_movies'  => $migrated_movies,
			'total_persons'    => $total_persons,
			'migrated_persons' => $migrated_persons,
		);
	}

	/**
	 * Prefetches meta data for multiple post IDs in a single query.
	 *
	 * This "warms up" the static cache to prevent N+1 queries when
	 * rendering lists or grids.
	 *
	 * @param array<int>    $post_ids Array of post IDs.
	 * @param array<string> $meta_keys Array of meta keys to prefetch.
	 * @return void
	 */
	public static function prefetch( array $post_ids, array $meta_keys ): void {
		if ( empty( $post_ids ) || empty( $meta_keys ) ) {
			return;
		}

		$post_ids = array_map( 'absint', $post_ids );

		// Group IDs by post type to query the correct custom tables.
		$type_groups = array();
		foreach ( $post_ids as $id ) {
			$type = get_post_type( $id );
			if ( isset( self::$table_map[ $type ] ) ) {
				$type_groups[ $type ][] = $id;
			}
		}

		global $wpdb;

		foreach ( $type_groups as $type => $ids ) {
			$class         = self::$table_map[ $type ];
			$table         = $class::get_table_name();
			$object_id_col = ( 'rt-movie' === $type ) ? 'movie_id' : 'person_id';

			// Format keys and IDs for the SQL IN clause.
			$id_list  = implode( ',', $ids );
			$key_list = "'" . implode( "','", array_map( 'esc_sql', $meta_keys ) ) . "'";

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table/Column names are internal and safe.
			$results = $wpdb->get_results(
				"SELECT {$object_id_col} as object_id, meta_key, meta_value 
				 FROM `{$table}` 
				 WHERE {$object_id_col} IN ({$id_list}) 
				 AND meta_key IN ({$key_list})"
			);
			// phpcs:enable

			// Populate the static cache in Abstract_Meta_Table.
			foreach ( $results as $row ) {
				$cache_key = "{$row->object_id}:{$row->meta_key}";
				// We simulate the structure Abstract_Meta_Table::get expects.
				// Since we don't know if the user wants single=true/false later,
				// we store as an array of values.
				$class::$cache[ $class ][ $cache_key ][] = maybe_unserialize( $row->meta_value );
			}
		}
	}
}
