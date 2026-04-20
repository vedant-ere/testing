# RT Movie Library

## Testing

See `TESTING.md` for PHPUnit setup, supported commands, and troubleshooting.

## Issue #25 - WP Cron TMDB Metadata Sync

### What This Branch Adds
1. A custom WP Cron event that runs every 30 minutes.
2. TMDB API settings in plugin settings:
   - TMDB v3 API key (`rt_tmdb_api_key`)
   - movies-per-sync limit (`rt_cron_movie_limit`, UI capped at 5)
3. Metadata sync for Movie posts using TMDB search-by-title with exact title match.
4. Shared TMDB HTTP client used by both dashboard widgets and cron sync.
5. Implemented: TMDB poster download and attachment sync.

### Cron Behavior
1. Event hook: `rt_tmdb_sync`
2. Interval: `rt_every_30_min` (1800 seconds)
3. Syncs published `rt-movie` posts in least-recently-synced order.
4. Sync run limit comes from settings and is safety-clamped to at least 1.
5. Updates:
   - `rt-movie-meta-basic-rating`
   - `rt-movie-meta-basic-release-date`
   - `_mw_tmdb_synced_at`
   - `_mw_tmdb_poster_path`
   - `_mw_tmdb_poster_attachment_id`
6. Writes `_mw_tmdb_synced_at` even on no-match to avoid infinite retries.
7. Stores unexpected sync failures in option `rt_tmdb_last_sync_error` and fires `rt_movie_library_tmdb_sync_error`.

### TMDB Client Behavior
1. Uses TMDB v3 endpoints:
   - `/discover/movie`
   - `/search/movie`
2. Uses TMDB v3 API key via `api_key` query parameter.
3. Validates API key format (32-char hex v3 key) before making requests.
4. Uses `timeout => 3` and `sslverify => true` on requests.
5. Sanitizes response values before use (title, release date, vote average, poster path).
6. Caches upcoming-movies response in transient `rt_tmdb_upcoming_movies` for 4 hours.

### Files in Scope
1. `includes/classes/tmdb/class-tmdb-sync.php`
2. `includes/classes/tmdb/class-tmdb-client.php`
3. `includes/classes/class-settings.php`
4. `includes/classes/dashboard/class-dashboard-widgets.php`
5. `includes/classes/class-activator.php`
6. `includes/classes/class-deactivator.php`
7. `rt-movie-library.php`

### Test Steps
1. Add TMDB API key in plugin settings.
2. Confirm cron schedule exists:
   - `wp cron schedule list | grep rt_every_30_min`
   - `wp cron event list | grep rt_tmdb_sync`
3. Trigger sync manually:
   - `wp cron event run rt_tmdb_sync`
4. Verify post meta updates on movie posts:
   - `rt-movie-meta-basic-rating`
   - `rt-movie-meta-basic-release-date`
   - `_mw_tmdb_synced_at`
5. Verify posters are sideloaded and set as featured image when TMDB poster path changes.
6. Deactivate plugin and confirm cron is unscheduled.

### Notes
1. Shared TMDB client removes duplicated TMDB HTTP logic.
2. Upcoming movies are cached using transients.
3. Poster download is skipped when the synced poster path has not changed.
