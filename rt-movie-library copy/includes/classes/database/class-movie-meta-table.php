<?php
/**
 * Movie Meta Table.
 *
 * Manages creation, maintenance and CRUD for the wp_rt_movie_meta
 * custom database table.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Meta_Table
 *
 * Static utility class for all operations on wp_rt_movie_meta.
 */
class Movie_Meta_Table extends Abstract_Meta_Table {

	/**
	 * Returns the full table name (respects $wpdb->prefix).
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->rt_movie_meta;
	}

	/**
	 * Returns the name of the column that stores the object ID.
	 *
	 * @return string
	 */
	protected static function get_object_id_column(): string {
		return 'movie_id';
	}

	/**
	 * Returns column names mapped to their wpdb format specifiers.
	 *
	 * @return array<string, string>
	 */
	protected static function get_columns(): array {
		return array(
			'meta_id'    => '%d',
			'movie_id'   => '%d',
			'meta_key'   => '%s',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Custom specialized meta table.
			'meta_value' => '%s',
		);
	}
}
