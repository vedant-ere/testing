<?php
/**
 * Abstract Meta Table.
 *
 * Provides a base CRUD and lifecycle implementation for custom post meta tables.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Class Abstract_Meta_Table
 *
 * Base class for all custom meta tables. Extenders supply only three things:
 *   – get_table_name()      : the fully-prefixed SQL table name
 *   – get_object_id_column(): the domain-specific ID column ('movie_id' / 'person_id')
 *   – get_columns()         : column → wpdb format map for whitelisting
 */
abstract class Abstract_Meta_Table {

	/**
	 * Request-level static cache: [ ClassName => [ "post_id:meta_key" => [ values ] ] ]
	 *
	 * Uses a multi-dimensional array keyed by the calling class name to ensure
	 * that Movie caches and Person caches do not collide.
	 *
	 * @var array<string, array<string, array<int, mixed>>>
	 */
	protected static array $cache = array();

	/**
	 * Returns the full table name (respects $wpdb->prefix).
	 *
	 * @return string
	 */
	abstract public static function get_table_name(): string;

	/**
	 * Returns the name of the column that stores the object ID (e.g., 'movie_id').
	 *
	 * @return string
	 */
	abstract protected static function get_object_id_column(): string;

	/**
	 * Returns column names mapped to their wpdb format specifiers.
	 * Used for whitelisting and format arrays in insert/update calls.
	 *
	 * @return array<string, string>
	 */
	abstract protected static function get_columns(): array;

	/**
	 * Creates or upgrades the table using dbDelta().
	 *
	 * @return void
	 */
	public static function create_table(): void {
		global $wpdb;

		$table           = static::get_table_name();
		$object_id_col   = static::get_object_id_column();
		$charset_collate = $wpdb->get_charset_collate(); 

		// dbDelta formatting rules (violations cause silent failures):
		// – TWO spaces before PRIMARY KEY (and before every KEY line).
		// – Use KEY, never INDEX.
		// – Every KEY must be named.
		// – One column/key per line.
		// – No trailing comma after the last column before PRIMARY KEY.
		//
		// UNIQUE KEY `obj_key` covers (object_id, meta_key) together, making the
		// dominant read query "WHERE {col} = %d AND meta_key = %s" a fast unique
		// index lookup. It also prevents duplicate (object, key) rows.
		$object_id_key = 'UNIQUE KEY obj_key (' . $object_id_col . ', meta_key(191))';
		$sql           = "CREATE TABLE {$table} (
meta_id    BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
{$object_id_col}   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
meta_key   VARCHAR(255) DEFAULT NULL,
meta_value LONGTEXT,
PRIMARY KEY  (meta_id),
KEY {$object_id_col} ({$object_id_col}),
{$object_id_key}
) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drops the table. Only called from uninstall.php.
	 *
	 * @return void
	 */
	public static function drop_table(): void {
		global $wpdb;
		$table = static::get_table_name();
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Controlled maintenance logic.
		$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
		// phpcs:enable
	}

	/**
	 * Inserts a new meta row.
	 *
	 * @param int    $object_id  Principal post ID (movie_id, person_id, etc).
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value (arrays are serialized automatically).
	 * @return int|false New meta_id on success, false on failure.
	 */
	public static function add( int $object_id, string $meta_key, $meta_value ) {
		global $wpdb;

		$object_id_col = static::get_object_id_column();

		$data = array(
			$object_id_col => absint( $object_id ),
			'meta_key'     => $meta_key,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Custom table logic.
			'meta_value'   => maybe_serialize( $meta_value ),
		);

		$column_formats = static::get_columns();
		$data           = array_intersect_key( $data, $column_formats );
		$data_keys      = array_keys( $data );
		$formats        = array_merge( array_flip( $data_keys ), $column_formats );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table insertion.
		$result = $wpdb->insert( static::get_table_name(), $data, $formats );

		if ( false === $result ) {
			return false;
		}

		self::bust_cache( $object_id, $meta_key );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Retrieves a single meta value from the custom table.
	 *
	 * @param int    $object_id Object ID (Movie or Person).
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Optional. Whether to return a single value or array. Default true.
	 * @return mixed
	 */
	public static function get( int $object_id, string $meta_key, bool $single = true ) {
		$class     = static::class;
		$cache_key = "{$object_id}:{$meta_key}";

		if ( ! isset( self::$cache[ $class ][ $cache_key ] ) ) {
			global $wpdb;

			$object_id_col = static::get_object_id_column();
			$table         = static::get_table_name();

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table lookup.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_value FROM `{$table}` WHERE {$object_id_col} = %d AND meta_key = %s",
					absint( $object_id ),
					$meta_key
				)
			);
			// phpcs:enable

			self::$cache[ $class ][ $cache_key ] = ! empty( $rows )
				? array_map( fn( $row ) => maybe_unserialize( $row->meta_value ), $rows )
				: array();
		}

		$values = self::$cache[ $class ][ $cache_key ];

		if ( empty( $values ) ) {
			return $single ? '' : array();
		}

		return $single ? $values[0] : $values;
	}

	/**
	 * Returns ALL meta data for a specific object as a flat key => values array.
	 *
	 * @param int $object_id Principal post ID.
	 * @return array<string, array<mixed>>
	 */
	public static function get_all( int $object_id ): array {
		global $wpdb;

		$object_id_col = static::get_object_id_column();
		$table         = static::get_table_name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table bulk lookup.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM `{$table}` WHERE {$object_id_col} = %d",
				absint( $object_id )
			)
		);
		// phpcs:enable

		$data = array();

		foreach ( $rows as $row ) {
			$data[ $row->meta_key ][] = maybe_unserialize( $row->meta_value );
		}

		return $data;
	}

	/**
	 * Updates an existing meta row, or inserts if it doesn't exist (upsert).
	 *
	 * Uses "INSERT ... ON DUPLICATE KEY UPDATE" for atomic performance.
	 * This leverages the UNIQUE composite index (object_id, meta_key).
	 *
	 * @param int    $object_id  Principal post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value New meta value.
	 * @return int|bool Meta ID (insert) or true/false (update), false on failure.
	 */
	public static function update( int $object_id, string $meta_key, $meta_value ) {
		global $wpdb;

		$object_id_col = static::get_object_id_column();
		$table         = static::get_table_name();
		$value_str     = maybe_serialize( $meta_value );

		self::bust_cache( $object_id, $meta_key );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Custom table atomic UPSERT.
		$sql = "INSERT INTO `{$table}` ( `{$object_id_col}`, `meta_key`, `meta_value` )
				VALUES ( %d, %s, %s )
				ON DUPLICATE KEY UPDATE `meta_value` = VALUES( `meta_value` )";

		return $wpdb->query( $wpdb->prepare( $sql, absint( $object_id ), $meta_key, $value_str ) );
		// phpcs:enable
	}

	/**
	 * Deletes meta row(s) for an object.
	 *
	 * @param int    $object_id  Principal post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Optional. If provided, only delete rows with this value.
	 * @return bool True on success, false on failure.
	 */
	public static function delete( int $object_id, string $meta_key, $meta_value = '' ): bool {
		global $wpdb;

		$object_id_col = static::get_object_id_column();
		$table         = static::get_table_name();

		$where  = array(
			$object_id_col => absint( $object_id ),
			'meta_key'     => $meta_key,
		);
		$format = array( '%d', '%s' );

		if ( '' !== $meta_value ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Custom specialized table schema.
			$where['meta_value'] = maybe_serialize( $meta_value );
			$format[]            = '%s';
		}

		self::bust_cache( $object_id, $meta_key );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct deletion of custom internal data.
		return (bool) $wpdb->delete( $table, $where, $format );
	}

	/**
	 * Deletes ALL meta rows for a given object ID.
	 *
	 * @param int $object_id Principal post ID.
	 * @return bool
	 */
	public static function delete_all( int $object_id ): bool {
		global $wpdb;

		$class         = static::class;
		$object_id_col = static::get_object_id_column();
		$table         = static::get_table_name();

		// Bust entire object cache.
		if ( isset( self::$cache[ $class ] ) ) {
			foreach ( array_keys( self::$cache[ $class ] ) as $key ) {
				if ( str_starts_with( $key, "{$object_id}:" ) ) {
					unset( self::$cache[ $class ][ $key ] );
				}
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Batch clean-up from internal custom schema.
		return (bool) $wpdb->delete(
			$table,
			array( $object_id_col => absint( $object_id ) ),
			array( '%d' )
		);
	}

	/**
	 * Truncates the table (removes all rows, resets AUTO_INCREMENT).
	 *
	 * @return bool
	 */
	public static function clear_table(): bool {
		global $wpdb;
		$class = static::class;
		unset( self::$cache[ $class ] );

		$table = static::get_table_name();
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Maintenance logic.
		return (bool) $wpdb->query( "TRUNCATE TABLE `{$table}`" );
		// phpcs:enable
	}

	/**
	 * Clears memory cache for a specific key.
	 *
	 * @param int    $object_id Principal post ID.
	 * @param string $meta_key  Meta key.
	 * @return void
	 */
	protected static function bust_cache( int $object_id, string $meta_key ): void {
		unset( self::$cache[ static::class ][ "{$object_id}:{$meta_key}" ] );
	}
}
