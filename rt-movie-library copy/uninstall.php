<?php
/**
 * Plugin Uninstall.
 *
 * WordPress calls this file automatically when an admin permanently deletes
 * the plugin (not just deactivates it). It must live in the plugin root.
 *
 * @package RT_Movie_Library
 */

// Security: WordPress sets WP_UNINSTALL_PLUGIN when calling this file.
// If it is not defined, someone is trying to run this file directly — stop them.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Only clean up when the admin explicitly opted in.
if ( '1' !== get_option( 'rt_movie_library_delete_data' ) ) {
	return;
}

global $wpdb;

// ── Step 1: Drop custom meta tables ──────────────────────────────────────────
// We reference the prefix directly here because $wpdb->rt_movie_meta is
// registered by an init hook which has not fired during uninstall.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Controlled maintenance logic.
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}rt_movie_meta`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}rt_person_meta`" );
// phpcs:enable

// ── Step 2: Batch-delete all rt-movie and rt-person posts ────────────────────
$batch_size = 50;

foreach ( array( 'rt-movie', 'rt-person' ) as $rt_post_type ) {

	// Keep looping until no posts of this type remain.
	do {
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts -- Maintenance routine, needs fresh data for deletion.
		$ids = get_posts(
			array(
				'post_type'      => $rt_post_type,
				'post_status'    => 'any',
				'posts_per_page' => $batch_size,
				'fields'         => 'ids',
				'no_found_rows'  => true, // Skips COUNT(*) — we don't need pagination.
			)
		);

		$ids_count = count( $ids );
		foreach ( $ids as $rt_id ) {
			wp_delete_post( (int) $rt_id, true ); // true = skip trash, delete permanently.
		}
	} while ( $ids_count === $batch_size );
}

// ── Step 3: Remove all plugin-specific options ───────────────────────────────
// The autoloader is not available in this context, so we use raw option name
// strings instead of Settings::OPTION_API_KEY / Settings::OPTION_MOVIE_LIMIT.
$raw_options = array(
	'rt_movie_library_delete_data',
	'rt_movie_library_db_version',
	'rt_search_cache_version',
	'rt_tmdb_api_key',
	'rt_cron_movie_limit',
);

foreach ( $raw_options as $option ) {
	delete_option( $option );
}
