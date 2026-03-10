# RT Movie Library

## Issue #25 - WP Cron TMDB Metadata Sync

### What This Branch Adds
1. A custom WP Cron event that runs every 30 minutes.
2. TMDB API settings in plugin settings:
   - API key
   - movies-per-sync limit
3. Metadata sync for Movie posts using TMDB search-by-title.
4. Shared TMDB HTTP client used by both dashboard and cron.

### Cron Behavior
1. Event hook: `rt_tmdb_sync`
2. Interval: `rt_every_30_min` (1800 seconds)
3. Syncs published `rt-movie` posts in least-recently-synced order.
4. Updates:
   - `rt-movie-meta-basic-rating`
   - `rt-movie-meta-basic-release-date`
   - `_mw_tmdb_synced_at`
5. Writes `_mw_tmdb_synced_at` even on no-match to avoid infinite retries.

### TMDB Client Behavior
1. Uses TMDB v3 endpoints:
   - `/movie/upcoming`
   - `/search/movie`
2. Sends API key in `Authorization: Bearer ...` header.
3. Never appends API key to URL query params.
4. Sanitizes response values before use.

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
4. Verify post meta updates on movie posts.
5. Deactivate plugin and confirm cron is unscheduled.

### Notes
1. Shared TMDB client removes duplicated TMDB HTTP logic.
2. Upcoming movies are cached using transients.
