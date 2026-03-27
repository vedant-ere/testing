# TMDB Integration & WordPress Cron - Comprehensive Learning Guide

## Table of Contents
1. [Overview & Architecture](#overview--architecture)
2. [TMDB Folder Structure & Files](#tmdb-folder-structure--files)
3. [WordPress Cron System - Deep Dive](#wordpress-cron-system---deep-dive)
4. [Class-TMDB-Client.php - Complete Explanation](#class-tmdb-clientphp---complete-explanation)
5. [Class-TMDB-Sync.php - Complete Explanation](#class-tmdb-syncphp---complete-explanation)
6. [How TMDB & WP-Cron Work Together](#how-tmdb--wp-cron-work-together)
7. [Data Flow & Execution Journey](#data-flow--execution-journey)
8. [Making Changes to the Code](#making-changes-to-the-code)
9. [Common Modifications & Patterns](#common-modifications--patterns)
10. [Troubleshooting & Debugging](#troubleshooting--debugging)

---

## 1. Overview & Architecture

### What is TMDB?

TMDB (The Movie Database) is a community-built movie database with a free REST API. Our plugin uses TMDB to:
- Fetch upcoming movies information
- Search for movies by title to get metadata
- Retrieve ratings, release dates, and poster images
- Update our local WordPress database with current information

### What is WordPress Cron?

WordPress Cron (WP-Cron) is a pseudo-cron system built into WordPress that:
- Runs scheduled tasks at specified intervals
- Works WITHOUT requiring system-level cron setup
- Triggers on every WordPress page load (checks if tasks are due)
- Allows plugins to schedule recurring events
- ONLY runs when your site gets traffic

### Why Both Together?

Our plugin uses them together to:
1. **Schedule automatic syncing**: Every 30 minutes
2. **Fetch fresh data from TMDB**: Without manual intervention
3. **Keep movie metadata updated**: Ratings, release dates, posters
4. **Improve user experience**: Always have current information

---

## 2. TMDB Folder Structure & Files

### Directory Layout

```
rt-movie-library/
├── includes/
│   └── classes/
│       └── tmdb/                    ← TMDB Integration Folder
│           ├── class-tmdb-client.php    ← API Client
│           └── class-tmdb-sync.php      ← WP-Cron Handler
```

### What Each File Does

| File | Purpose | Responsibilities |
|------|---------|---|
| **class-tmdb-client.php** | HTTP API Client | Making requests to TMDB API, parsing responses, caching |
| **class-tmdb-sync.php** | WP-Cron Handler | Scheduling tasks, syncing movies, managing metadata |

---

## 3. WordPress Cron System - Deep Dive

### How WordPress Cron Works (Step-by-Step)

#### Step 1: Understand the Problem WP-Cron Solves

Traditional server cron:
```bash
# Server cron (requires server access - not available on shared hosting)
*/30 * * * * php /var/www/html/wp-cli.phar rt_movie:sync
```

WordPress Cron:
- No server access needed
- Triggered by page loads
- Works on any hosting (even shared hosting)
- Easy to schedule through code

#### Step 2: The WP-Cron Execution Flow

```
User visits WordPress site
    ↓
WordPress loads wp-cron.php
    ↓
Checks: Is there a scheduled task due to run?
    ├─ YES → Run the scheduled callback
    └─ NO  → Do nothing
    ↓
Continue loading the page
```

#### Step 3: Scheduling vs Execution

**Scheduling** (Happens ONCE - usually on plugin activation):
```php
wp_schedule_event( time(), 'rt_every_30_min', 'rt_tmdb_sync' );
```
- Creates a row in `wp_options` table
- Stores: next run time, interval, hook name
- No actual execution yet

**Execution** (Happens REPEATEDLY - every 30 minutes when site gets traffic):
```php
add_action( 'rt_tmdb_sync', [ $this, 'run_sync' ] );
```
- WordPress triggers the action hook
- Your callback function runs
- Updates next scheduled time automatically

#### Step 4: The wp_options Table Entry

When you schedule an event, WordPress stores it like this:

```
option_id: 123
option_name: _transient_wp_scheduled_events
option_value: {
    "rt_tmdb_sync": {
        "1615459200": {
            "schedule": "rt_every_30_min",
            "args": []
        }
    }
}
```

**Breaking this down:**
- `rt_tmdb_sync`: The hook name
- `1615459200`: Unix timestamp of next run
- `rt_every_30_min`: The interval
- `args`: Arguments to pass to the callback (empty for us)

### Custom Intervals

WordPress comes with standard intervals:
- `hourly` (1 hour)
- `twicedaily` (12 hours)
- `daily` (24 hours)

We need 30 minutes, so we create a custom interval:

```php
public function register_cron_interval( array $schedules ): array {
    if ( ! isset( $schedules[ self::CRON_INTERVAL ] ) ) {
        $schedules[ self::CRON_INTERVAL ] = array(
            'interval' => 30 * MINUTE_IN_SECONDS,    // 1800 seconds
            'display'  => __( 'Every 30 Minutes', 'rt-movie-library' ),
        );
    }
    return $schedules;
}
```

**How this works:**
- Hook into `cron_schedules` filter
- Add our custom interval with interval size in seconds
- `display` is for admin viewing purposes
- Now `rt_every_30_min` is available for scheduling

### Complete Cron Lifecycle in Our Plugin

```
PLUGIN ACTIVATION
    ↓
class-plugin.php calls $tmdb_sync_instance->schedule()
    ↓
Tmdb_Sync::schedule() calls wp_schedule_event()
    ↓
WordPress stores in wp_options:
    _transient_wp_scheduled_events = {
        "rt_tmdb_sync": {
            "1615459200": {
                "schedule": "rt_every_30_min",
                "args": []
            }
        }
    }
    ↓
EVERY 30 MINUTES (when site gets traffic)
    ↓
WordPress checks: Is rt_tmdb_sync due?
    ├─ YES → Fire the 'rt_tmdb_sync' action hook
    │        ↓
    │        run_sync() callback executes
    │        ↓
    │        Syncs movies from TMDB
    │        ↓
    │        Updates next run time
    └─ NO  → Continue loading page
    ↓
PLUGIN DEACTIVATION
    ↓
class-plugin.php calls $tmdb_sync_instance->unschedule()
    ↓
Tmdb_Sync::unschedule() calls wp_clear_scheduled_hook()
    ↓
Removes event from wp_options
    ↓
No more syncing happens
```

### Important WP-Cron Gotchas

1. **It's NOT Real Cron**
   - Relies on page views to trigger
   - Low-traffic sites might not sync regularly
   - Use `define( 'DISABLE_WP_CRON', false );` to ensure it runs

2. **Loopback Requests**
   - WP-Cron makes a request to itself
   - If `localhost` access is blocked, cron won't work
   - Check with: `wp_remote_get( site_url() )`

3. **Timezone Issues**
   - WP-Cron uses UTC internally
   - WordPress automatically handles local timezone
   - Use `current_time( 'timestamp' )` for correct time

4. **Multiple Server Issues**
   - Each server might run the same cron
   - Use transients/options to prevent duplicate runs
   - Or use managed cron services (Kinsta, WP Engine)

---

## 4. Class-TMDB-Client.php - Complete Explanation

### File Overview

**Purpose**: Handle all communication with The Movie Database (TMDB) API

**Responsibilities**:
- Make HTTP requests to TMDB endpoints
- Parse and sanitize API responses
- Cache results to reduce API calls
- Validate API credentials
- Handle errors gracefully

**Size**: ~358 lines

**Extends**: Nothing (standalone utility class)

**Uses Traits**: None

### Class Constants

#### TMDB Base Configuration

```php
private const BASE_URL = 'https://api.themoviedb.org/3';
```
- TMDB API v3 base URL
- All requests go to this base
- Example: `https://api.themoviedb.org/3/movie/upcoming`

#### Caching Configuration

```php
private const TRANSIENT_UPCOMING = 'rt_tmdb_upcoming_movies';
private const TRANSIENT_TTL = 4 * HOUR_IN_SECONDS;
```

**What is a Transient?**
- WordPress temporary data storage
- Stored in wp_options table (or in-memory cache)
- Automatically deleted after TTL expires
- Perfect for API cache data

**Why cache for 4 hours?**
- TMDB data doesn't change frequently
- Reduces API rate limiting issues
- Improves page load speed
- Dashboard widget loads instantly from cache

### Method 1: `get_upcoming_movies()` - Public Method

```php
public function get_upcoming_movies(): array|\WP_Error {
    // Step 1: Try to get from cache
    $cached = get_transient( self::TRANSIENT_UPCOMING );
    
    if ( false !== $cached && is_array( $cached ) ) {
        return $cached;
    }
    
    // Step 2: Get API key
    $api_key = $this->get_api_key();
    if ( is_wp_error( $api_key ) ) {
        return $api_key;
    }
    
    // Step 3: Make TMDB API request
    $data = $this->request_tmdb(
        '/movie/upcoming',
        array(
            'language' => 'en-US',
            'page'     => 1,
        ),
        $api_key
    );
    
    if ( is_wp_error( $data ) ) {
        return $data;
    }
    
    // Step 4: Validate response format
    if ( ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
        return new \WP_Error(
            'rt_tmdb_invalid_response',
            esc_html__( 'TMDB returned an unrecognised response format.', 'rt-movie-library' )
        );
    }
    
    // Step 5: Parse and sanitize results
    $movies = $this->parse_upcoming_results( $data['results'] );
    
    // Step 6: Cache the results
    set_transient( self::TRANSIENT_UPCOMING, $movies, self::TRANSIENT_TTL );
    
    return $movies;
}
```

**Step-by-Step Breakdown:**

1. **Check Cache First**
   ```php
   $cached = get_transient( self::TRANSIENT_UPCOMING );
   if ( false !== $cached && is_array( $cached ) ) {
       return $cached;
   }
   ```
   - If data exists in cache, return immediately
   - Saves API call, improves performance
   - If cache expired, `get_transient` returns `false`

2. **Validate API Key**
   ```php
   $api_key = $this->get_api_key();
   ```
   - Calls private method to fetch and validate
   - Returns error if key missing/invalid
   - Prevents unnecessary API requests

3. **Make HTTP Request**
   ```php
   $data = $this->request_tmdb(
       '/movie/upcoming',
       array( 'language' => 'en-US', 'page' => 1 ),
       $api_key
   );
   ```
   - Calls private helper method
   - Builds full URL: `BASE_URL + /movie/upcoming?language=en-US&page=1&api_key=XXX`
   - Returns decoded JSON as array

4. **Check for Errors**
   ```php
   if ( is_wp_error( $data ) ) {
       return $data;
   }
   ```
   - WordPress error object returned if request failed
   - Propagate error to caller
   - Dashboard widget handles error gracefully

5. **Validate Response Structure**
   ```php
   if ( ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
       return new \WP_Error(...);
   }
   ```
   - TMDB always returns `results` array
   - If missing or malformed, return error
   - Defensive programming prevents crashes

6. **Parse Results**
   ```php
   $movies = $this->parse_upcoming_results( $data['results'] );
   ```
   - Sanitizes each movie entry
   - Extracts: title, release_date
   - Skips invalid entries

7. **Cache for Reuse**
   ```php
   set_transient( 
       self::TRANSIENT_UPCOMING, 
       $movies, 
       self::TRANSIENT_TTL  // 4 hours
   );
   ```
   - Next call within 4 hours uses cache
   - Reduces API calls significantly

**Return Value:**
- Success: Array of movies `[['title' => 'Movie Name', 'release_date' => '2024-03-15'], ...]`
- Error: `WP_Error` object with error code and message

**Used by:**
- Dashboard widget (Upcoming Movies widget)
- Display upcoming releases to users

---

### Method 2: `search_movie()` - Public Method

```php
public function search_movie( string $title ): array|\WP_Error {
    // Get API key
    $api_key = $this->get_api_key();
    if ( is_wp_error( $api_key ) ) {
        return $api_key;
    }
    
    // Sanitize title
    $search_title = sanitize_text_field( $title );
    if ( '' === $search_title ) {
        return new \WP_Error( 'rt_tmdb_invalid_title', ... );
    }
    
    // Make request
    $data = $this->request_tmdb(
        '/search/movie',
        array(
            'query'    => $search_title,
            'language' => 'en-US',
            'page'     => 1,
        ),
        $api_key
    );
    
    if ( is_wp_error( $data ) ) {
        return $data;
    }
    
    // Validate response
    if ( ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
        return new \WP_Error( 'rt_tmdb_invalid_response', ... );
    }
    
    // Find exact title match
    $normalized_query = strtolower( trim( $search_title ) );
    
    foreach ( $data['results'] as $movie ) {
        if ( ! is_array( $movie ) ) {
            continue;
        }
        
        $raw_title = '';
        if ( isset( $movie['title'] ) ) {
            $raw_title = (string) $movie['title'];
        }
        
        $candidate = strtolower( trim( $raw_title ) );
        
        // Return on exact match
        if ( $candidate === $normalized_query ) {
            return $this->sanitize_movie_payload( $movie );
        }
    }
    
    // No match found
    return new \WP_Error( 'rt_tmdb_not_found', ... );
}
```

**What This Does:**

- Searches TMDB for a movie by title
- Returns ONLY exact title matches
- Ignores partial/fuzzy matches

**Why Exact Matching?**
```
Query: "The Dark Knight"

TMDB Results:
1. "The Dark Knight"           ← EXACT MATCH ✓
2. "The Dark Knight Rises"     ← Partial, skip
3. "Batman: The Dark Knight"   ← Partial, skip
```

**Step Explanation:**

1. **Normalize Query**: Convert to lowercase, trim whitespace
2. **Iterate Results**: Loop through TMDB results
3. **Normalize Candidate**: Same normalization as query
4. **Compare**: If they match exactly, return sanitized data
5. **Return Error**: If no exact match found

**Why Normalize?**
```php
$normalized_query = strtolower( trim( $search_title ) );
$candidate = strtolower( trim( $raw_title ) );
```
- Case-insensitive comparison
- Ignore extra spaces
- "The dark knight" === "THE DARK KNIGHT" ✓

**Return Value:**
- Success: Array with keys: `title`, `release_date`, `vote_average`, `poster_path`
- Error: `WP_Error` with specific error code

**Used by:**
- WP-Cron sync (syncs individual movies)
- Gets ratings, release dates, posters for a specific movie

**Important:**
```php
sanitize_movie_payload( $movie )
```
- Cleans the full movie object
- Ensures all data is safe and properly formatted
- Removes unexpected fields

---

### Method 3: `request_tmdb()` - Private Helper

```php
private function request_tmdb(
    string $endpoint,
    array $query_args,
    string $api_key
): array|\WP_Error {
    // Build full URL
    $base_url = self::BASE_URL . $endpoint;
    $request_url = add_query_arg(
        array_merge(
            $query_args,
            array( 'api_key' => $api_key )
        ),
        $base_url
    );
    
    // Configure request
    $request_args = array(
        'timeout'   => 3,  // 3 second timeout
        'sslverify' => true,  // Verify SSL certificate
        'headers'   => array(
            'Accept' => 'application/json',
        ),
    );
    
    // Make request using WordPress HTTP API
    $response = wp_remote_get( $request_url, $request_args );
    
    // Check for connection error
    if ( is_wp_error( $response ) ) {
        return new \WP_Error(
            'rt_tmdb_request_failed',
            esc_html__( 'Could not connect to TMDB...', 'rt-movie-library' )
        );
    }
    
    // Check status code
    $status_code = (int) wp_remote_retrieve_response_code( $response );
    
    if ( 200 !== $status_code ) {
        return new \WP_Error(
            'rt_tmdb_bad_response',
            sprintf(
                esc_html__( 'TMDB returned an unexpected status: %d.', 'rt-movie-library' ),
                $status_code
            )
        );
    }
    
    // Decode JSON response
    $data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
    
    // Validate JSON
    if ( ! is_array( $data ) ) {
        return new \WP_Error(
            'rt_tmdb_invalid_response',
            esc_html__( 'TMDB returned an unrecognised response format.', 'rt-movie-library' )
        );
    }
    
    return $data;
}
```

**This is the Core HTTP Request Handler**

**Step 1: Build URL**
```php
$base_url = self::BASE_URL . $endpoint;  // https://api.themoviedb.org/3 + /movie/upcoming

$request_url = add_query_arg(
    array_merge( $query_args, array( 'api_key' => $api_key ) ),
    $base_url
);
```

**Example URL Construction:**
```
Input:
  - endpoint: '/movie/upcoming'
  - query_args: ['language' => 'en-US', 'page' => 1]
  - api_key: 'abc123def456...'

Output:
  - https://api.themoviedb.org/3/movie/upcoming?language=en-US&page=1&api_key=abc123def456...
```

**`add_query_arg()` breakdown:**
- WordPress helper function
- Takes array of arguments
- Appends to URL as query string
- Properly encodes special characters

**Step 2: Configure Request**
```php
$request_args = array(
    'timeout'   => 3,  // Give TMDB 3 seconds to respond
    'sslverify' => true,  // Verify HTTPS certificate
    'headers'   => array(
        'Accept' => 'application/json',  // Tell TMDB we want JSON
    ),
);
```

**Why 3 second timeout?**
- Fast enough for user experience
- Slow TMDB response → return error
- Prevents hanging on network issues
- User sees error message instead of blank page

**Step 3: Make HTTP Request**
```php
$response = wp_remote_get( $request_url, $request_args );
```

**`wp_remote_get()` explanation:**
- WordPress HTTP API wrapper
- Works with multiple handlers (cURL, fopen, fsockopen)
- Returns array or WP_Error
- No difference to you (abstracted)

**Step 4: Check for Connection Error**
```php
if ( is_wp_error( $response ) ) {
    return new \WP_Error( 'rt_tmdb_request_failed', ... );
}
```

**Possible errors:**
- Network timeout
- DNS resolution failed
- SSL certificate verification failed
- Connection refused

**Step 5: Verify HTTP Status Code**
```php
$status_code = (int) wp_remote_retrieve_response_code( $response );

if ( 200 !== $status_code ) {
    return new \WP_Error( 'rt_tmdb_bad_response', ... );
}
```

**HTTP Status Codes:**
- `200 OK`: Success, data is in response body
- `401 Unauthorized`: Invalid API key
- `429 Too Many Requests`: Rate limit exceeded
- `404 Not Found`: Endpoint doesn't exist

**Step 6: Decode JSON Response**
```php
$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
```

**Breaking this down:**
- `wp_remote_retrieve_body( $response )`: Get response body as string
- `(string)`: Type cast to string (ensures it's string)
- `json_decode( ..., true )`: Convert JSON string to PHP array
  - Second parameter `true` = decode as associative array (not object)
  - Returns `null` if invalid JSON

**Step 7: Validate Decoded Data**
```php
if ( ! is_array( $data ) ) {
    return new \WP_Error( 'rt_tmdb_invalid_response', ... );
}

return $data;
```

**Why validate?**
- `json_decode()` can fail silently
- Empty response body = `null`
- Malformed JSON = `null`
- Check ensures safe data before return

**Complete Example Journey:**
```
get_upcoming_movies()
    ↓
request_tmdb( '/movie/upcoming', ['language'=>'en-US', 'page'=>1], 'api_key_here' )
    ↓
Build: https://api.themoviedb.org/3/movie/upcoming?language=en-US&page=1&api_key=api_key_here
    ↓
wp_remote_get( url, ['timeout'=>3, ...] )
    ↓
TMDB server responds with 200 OK + JSON body
    ↓
json_decode to PHP array
    ↓
Return array:
{
    'results': [
        { 'title': 'Movie 1', 'release_date': '2024-03-15', ... },
        { 'title': 'Movie 2', 'release_date': '2024-03-20', ... },
        ...
    ],
    'page': 1,
    'total_pages': 4,
    ...
}
```

---

### Method 4: `get_api_key()` - Private Helper

```php
private function get_api_key(): string|\WP_Error {
    // Get from WordPress options
    $api_key = (string) get_option( Settings::OPTION_API_KEY, '' );
    
    // Sanitize it
    $api_key = sanitize_text_field( $api_key );
    
    // Check if empty
    if ( '' === $api_key ) {
        return new \WP_Error(
            'rt_tmdb_no_api_key',
            esc_html__( 'TMDB API key is not configured. Please add it in Settings.', 'rt-movie-library' )
        );
    }
    
    // Validate format (TMDB v3 keys are 32-char hex strings)
    if ( 1 !== preg_match( '/^[a-f0-9]{32}$/i', $api_key ) ) {
        return new \WP_Error(
            'rt_tmdb_invalid_api_key',
            esc_html__( 'TMDB API key format looks invalid...', 'rt-movie-library' )
        );
    }
    
    return $api_key;
}
```

**What This Does:**
- Fetches API key from WordPress settings
- Validates it's not empty
- Validates format (32-character hex string)
- Returns error if invalid

**Where API Key is Stored:**
```php
Settings::OPTION_API_KEY
```
- This is a constant that equals something like: `'rt_movie_library_tmdb_api_key'`
- Stored in `wp_options` table
- Set in plugin settings page (admin)

**API Key Format Validation:**
```php
if ( 1 !== preg_match( '/^[a-f0-9]{32}$/i', $api_key ) ) {
```

**Breaking down the regex:**
```
/^[a-f0-9]{32}$/i

^ = Start of string
[a-f0-9] = Lowercase a-f OR 0-9 (hexadecimal character)
{32} = Exactly 32 of these characters
$ = End of string
i = Case insensitive (A-F also allowed)
```

**Valid examples:**
```
a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6  ✓
A1B2C3D4E5F6A7B8C9D0E1F2A3B4C5D6  ✓ (case insensitive)
abc123xyz999...                    ✗ (wrong format)
a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e ✗ (too long)
```

---

### Method 5: `parse_upcoming_results()` - Private Helper

```php
private function parse_upcoming_results( array $results ): array {
    $movies = array();
    
    foreach ( $results as $item ) {
        // Skip if not array
        if ( ! is_array( $item ) ) {
            continue;
        }
        
        // Extract title
        $title = '';
        if ( isset( $item['title'] ) ) {
            $title = sanitize_text_field( (string) $item['title'] );
        }
        
        // Skip if no title
        if ( '' === $title ) {
            continue;
        }
        
        // Extract release date
        $release_date = '';
        if ( isset( $item['release_date'] ) ) {
            $release_date = $this->sanitize_release_date( (string) $item['release_date'] );
        }
        
        // Add to array
        $movies[] = array(
            'title'        => $title,
            'release_date' => $release_date,
        );
    }
    
    return $movies;
}
```

**Purpose:** Extract relevant fields from TMDB results

**Why Skip Invalid Entries?**
```
TMDB Response:
results: [
    { title: 'Movie 1', release_date: '2024-03-15' },  ← Valid ✓
    null,                                               ← Invalid, skip
    { title: '', release_date: '2024-03-20' },         ← No title, skip
    { release_date: '2024-03-25' },                    ← No title, skip
    { title: 'Movie 2', release_date: '2024-04-01' },  ← Valid ✓
]
```

**Defensive Programming:**
- Check `is_array()` before accessing
- Check `isset()` before using
- Normalize with sanitizers
- Skip anything malformed

**Output Example:**
```php
[
    [
        'title' => 'The Shawshank Redemption',
        'release_date' => '2024-03-15'
    ],
    [
        'title' => 'Inception',
        'release_date' => '2024-03-20'
    ],
    ...
]
```

---

### Method 6: `sanitize_movie_payload()` - Private Helper

```php
private function sanitize_movie_payload( array $movie ): array {
    // Initialize structure with defaults
    $sanitized = array(
        'title'        => '',
        'release_date' => '',
        'vote_average' => 0.0,
        'poster_path'  => '',
    );
    
    // Extract and sanitize each field
    if ( isset( $movie['title'] ) ) {
        $sanitized['title'] = sanitize_text_field( (string) $movie['title'] );
    }
    
    if ( isset( $movie['release_date'] ) ) {
        $sanitized['release_date'] = $this->sanitize_release_date( (string) $movie['release_date'] );
    }
    
    if ( isset( $movie['vote_average'] ) ) {
        $sanitized['vote_average'] = (float) $movie['vote_average'];
    }
    
    if ( isset( $movie['poster_path'] ) ) {
        $sanitized['poster_path'] = $this->sanitize_poster_path( (string) $movie['poster_path'] );
    }
    
    return $sanitized;
}
```

**Purpose:** Clean and structure movie data from TMDB API response

**Why Use Defaults?**
```php
$sanitized = array(
    'title'        => '',
    'release_date' => '',
    'vote_average' => 0.0,
    'poster_path'  => '',
);
```

- Guaranteed structure: Always same keys
- Type safety: Always same value types
- Prevents "undefined key" errors
- Makes code predictable

**Example:**

Input from TMDB:
```php
[
    'id' => 550,
    'title' => 'Fight Club',
    'vote_average' => 8.8,
    'poster_path' => '/a28jWPkAY0XG9R3XQwXczs95Jxl.jpg',
    'original_language' => 'en',
    'adult' => false,
    'backdrop_path' => '/...',
    // ... 20 more fields we don't need
]
```

After `sanitize_movie_payload()`:
```php
[
    'title' => 'Fight Club',  // Sanitized
    'release_date' => '',  // Not in input, default ''
    'vote_average' => 8.8,  // Type-cast to float
    'poster_path' => '/a28jWPkAY0XG9R3XQwXczs95Jxl.jpg',  // Sanitized
    // Extra fields removed
]
```

**Why Remove Extra Fields?**
- Security: Less data to worry about
- Performance: Smaller arrays, faster operations
- Clarity: Only what we need is present

---

### Method 7: `sanitize_poster_path()` - Private Helper

```php
private function sanitize_poster_path( string $raw_path ): string {
    // Sanitize with WordPress text sanitizer
    $poster_path = sanitize_text_field( $raw_path );
    
    // Check: must not be empty AND must start with /
    if ( '' === $poster_path || '/' !== $poster_path[0] ) {
        return '';  // Invalid, return empty string
    }
    
    return $poster_path;
}
```

**What TMDB Returns:**
```
Valid: "/a28jWPkAY0XG9R3XQwXczs95Jxl.jpg"
Valid: "/nMMHBHwpK0I1pY9HWe1vJmDq0t.jpg"
Invalid: null  (no poster)
Invalid: ""  (empty)
Invalid: "a28jWPkAY0XG9R3XQwXczs95Jxl.jpg"  (missing leading /)
```

**Validation Logic:**
```php
if ( '' === $poster_path || '/' !== $poster_path[0] ) {
    return '';
}
```

- If empty string: Invalid
- If first character is NOT '/': Invalid
- Otherwise: Valid

**Why This Format?**
TMDB API returns partial paths. Full poster URL is:
```
https://image.tmdb.org/t/p/w500 + /a28jWPkAY0XG9R3XQwXczs95Jxl.jpg
                                  ↑ Must start with /
```

---

### Method 8: `sanitize_release_date()` - Private Helper

```php
private function sanitize_release_date( string $raw_date ): string {
    // Sanitize text
    $release_date = sanitize_text_field( $raw_date );
    
    // Return empty if empty
    if ( '' === $release_date ) {
        return '';
    }
    
    // Validate date format: YYYY-MM-DD
    $datetime = \DateTime::createFromFormat( 'Y-m-d', $release_date );
    
    if ( false === $datetime || $datetime->format( 'Y-m-d' ) !== $release_date ) {
        return '';  // Invalid format, return empty
    }
    
    return $release_date;
}
```

**What This Validates:**

```
Valid inputs:
- "2024-03-15"  ✓
- "2023-12-25"  ✓
- "2025-01-01"  ✓

Invalid inputs:
- "03-15-2024"  ✗ (wrong format)
- "2024/03/15"  ✗ (wrong format)
- "2024-13-01"  ✗ (invalid month)
- "2024-02-30"  ✗ (invalid day for February)
- ""            ✗ (empty)
- "not-a-date"  ✗ (garbage)
```

**How `DateTime::createFromFormat()` Works:**

```php
$datetime = \DateTime::createFromFormat( 'Y-m-d', '2024-03-15' );
```

- First param: Format string (what format input is)
  - `Y` = 4-digit year
  - `m` = 2-digit month
  - `d` = 2-digit day
- Second param: Date string to parse
- Returns: DateTime object on success, `false` on failure

**Double-Check Validation:**
```php
if ( false === $datetime || $datetime->format( 'Y-m-d' ) !== $release_date ) {
    return '';
}
```

Why the second check `$datetime->format( 'Y-m-d' ) !== $release_date`?

Because this can happen:
```php
$datetime = \DateTime::createFromFormat( 'Y-m-d', '2024-02-30' );
// createFromFormat is lenient!
// Returns DateTime for 2024-03-01 (automatically rolls over)
// But 2024-03-01 formatted as 'Y-m-d' !== '2024-02-30'
// So we catch it and return ''
```

---

## 5. Class-TMDB-Sync.php - Complete Explanation

### File Overview

**Purpose**: Handle WP-Cron scheduling and movie synchronization

**Responsibilities**:
- Register custom 30-minute cron interval
- Schedule/unschedule cron events on plugin activation/deactivation
- Run sync callback every 30 minutes
- Find unsynced movies
- Fetch metadata from TMDB
- Update movie ratings and release dates
- Download and attach posters

**Size**: ~296 lines

**Uses Traits**: `Singleton` (enforces single instance)

**Extends**: Nothing

### Class Constants

```php
public const CRON_HOOK = 'rt_tmdb_sync';
```
- The WordPress action hook that gets fired
- Other plugins can hook into this
- Example: `add_action( 'rt_tmdb_sync', 'my_custom_function' );`

```php
private const CRON_INTERVAL = 'rt_every_30_min';
```
- Internal name for our custom 30-minute interval
- Used when scheduling: `wp_schedule_event( time(), 'rt_every_30_min', ... )`

```php
private const META_SYNCED_AT = '_mw_tmdb_synced_at';
private const META_POSTER_PATH = '_mw_tmdb_poster_path';
private const META_POSTER_ATTACHMENT_ID = '_mw_tmdb_poster_attachment_id';
```

Post meta keys for storing sync information:
- `_mw_tmdb_synced_at`: Unix timestamp of last sync (when)
- `_mw_tmdb_poster_path`: TMDB poster path (what was downloaded)
- `_mw_tmdb_poster_attachment_id`: Local WordPress attachment ID (where stored)

**Why post meta?**
- Attached to specific movie post
- Persists across syncs
- Easy to query and update
- WordPress standard pattern

```php
private const TMDB_POSTER_BASE_URL = 'https://image.tmdb.org/t/p/w500';
```
- TMDB hosts poster images
- `w500` = 500px wide version (good balance of quality/size)
- Full URL: `https://image.tmdb.org/t/p/w500/a28jWPkAY0XG9R3XQwXczs95Jxl.jpg`

### Constructor & Hook Bootstrap

```php
protected function __construct() {
    add_filter( 'cron_schedules', array( $this, 'register_cron_interval' ) );
    add_action( self::CRON_HOOK, array( $this, 'run_sync' ) );
}
```

**Why protected?**
- Singleton trait enforces this
- Cannot instantiate directly: `new Tmdb_Sync()` = Error
- Must use: `Tmdb_Sync::get_instance()`

**What Happens on `get_instance()`:**
```
First call to Tmdb_Sync::get_instance()
    ↓
Checks if instance already exists? NO
    ↓
Calls __construct()
    ↓
add_filter( 'cron_schedules', 'register_cron_interval' )
    ↓
add_action( 'rt_tmdb_sync', 'run_sync' )
    ↓
Returns singleton instance
    ↓
Future calls to get_instance() just return same instance
```

### Method 1: `register_cron_interval()` - Public

```php
public function register_cron_interval( array $schedules ): array {
    if ( ! isset( $schedules[ self::CRON_INTERVAL ] ) ) {
        $schedules[ self::CRON_INTERVAL ] = array(
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 30 Minutes', 'rt-movie-library' ),
        );
    }
    
    return $schedules;
}
```

**Complete Flow:**

```
WordPress boots
    ↓
Applies 'cron_schedules' filter
    ↓
register_cron_interval() called with existing schedules array
    ↓
Check: Is 'rt_every_30_min' already in schedules?
    ├─ YES → Don't add again (prevent duplicates)
    └─ NO  → Add it
    ↓
schedules[ 'rt_every_30_min' ] = array(
    'interval' => 1800,  // seconds
    'display'  => 'Every 30 Minutes'
)
    ↓
Return updated schedules array
```

**What Gets Registered:**
```php
$schedules[ self::CRON_INTERVAL ] = array(
    'interval' => 30 * MINUTE_IN_SECONDS,  // = 1800
    'display'  => __( 'Every 30 Minutes', 'rt-movie-library' ),
);
```

- `interval`: Seconds between runs (1800 = 30 minutes)
- `display`: Admin-facing label

**Why Check Before Adding?**
```php
if ( ! isset( $schedules[ self::CRON_INTERVAL ] ) ) {
```

The filter might run multiple times. Without this check:
- First run: Add interval ✓
- Second run: Add interval again (now there are duplicates) ✗

WordPress doesn't prevent this, so we do.

### Method 2: `schedule()` - Static Public

```php
public static function schedule(): void {
    self::get_instance();  // Ensure singleton exists (runs constructor)
    
    if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
        wp_schedule_event( time(), self::CRON_INTERVAL, self::CRON_HOOK );
    }
}
```

**When This Runs:**
- Plugin activation (called from `class-activator.php` or `class-plugin.php`)
- Runs ONCE ever (per installation)

**Step-by-Step:**

1. **Ensure Instance Exists**
   ```php
   self::get_instance();
   ```
   - Calls `__construct()` (if first time)
   - Registers the cron interval via filter

2. **Check If Already Scheduled**
   ```php
   if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
   ```
   - `wp_next_scheduled( 'rt_tmdb_sync' )` returns:
     - Unix timestamp of next run (if scheduled)
     - `false` if NOT scheduled
   - The `!` means "if NOT scheduled"

3. **Schedule the Event**
   ```php
   wp_schedule_event( time(), self::CRON_INTERVAL, self::CRON_HOOK );
   ```
   - `time()`: Start immediately (WordPress rounds to next occurrence)
   - `'rt_every_30_min'`: Run every 30 minutes
   - `'rt_tmdb_sync'`: Fire this action hook

**What Gets Stored in Database:**

WordPress creates/updates this option:
```php
option_name = '_transient_wp_scheduled_events'
option_value = {
    "rt_tmdb_sync": {
        "1234567890": {
            "schedule": "rt_every_30_min",
            "args": []
        }
    }
}
```

**Why `wp_next_scheduled()` Check?**

Prevent duplicate schedules:
```
First activation:
    wp_next_scheduled( 'rt_tmdb_sync' ) = false
    → Schedule it ✓

Second activation (if plugin re-enabled):
    wp_next_scheduled( 'rt_tmdb_sync' ) = 1234567890 (timestamp)
    → Already scheduled, don't schedule again ✓
```

---

### Method 3: `unschedule()` - Static Public

```php
public static function unschedule(): void {
    wp_clear_scheduled_hook( self::CRON_HOOK );
}
```

**When This Runs:**
- Plugin deactivation
- Called from deactivator or main plugin file

**What It Does:**
- Removes ALL scheduled occurrences of `rt_tmdb_sync`
- Deletes from `_transient_wp_scheduled_events` option
- No more syncing happens

**Important:**
- Synced metadata stays in database
- Only removes the scheduled task
- If plugin re-activated, must run `schedule()` again

---

### Method 4: `run_sync()` - Public (Cron Callback)

```php
public function run_sync(): void {
    try {
        // Get limit from settings
        $limit = absint( get_option( Settings::OPTION_MOVIE_LIMIT, 5 ) );
        if ( $limit < 1 ) {
            $limit = 5;  // Default to 5
        }
        
        // Create fresh client instance
        $client = new Tmdb_Client();
        
        // Get unsynced movies
        $movies = $this->get_movies_to_sync( $limit );
        
        if ( empty( $movies ) ) {
            return;  // No movies to sync
        }
        
        // Sync each movie
        foreach ( $movies as $post ) {
            $this->sync_single_movie( $post, $client );
        }
    } catch ( \Throwable $e ) {
        // Log error to options
        update_option(
            'rt_tmdb_last_sync_error',
            array(
                'message' => sanitize_text_field( $e->getMessage() ),
                'time'    => time(),
            ),
            false
        );
        
        // Fire error hook for other plugins
        do_action( 'rt_movie_library_tmdb_sync_error', $e );
        
        return;
    }
}
```

**This is the Main Cron Callback**

**Complete Flow (Every 30 Minutes):**

1. **Get Limit Setting**
   ```php
   $limit = absint( get_option( Settings::OPTION_MOVIE_LIMIT, 5 ) );
   ```
   - Gets from WordPress options: "How many movies to sync per run?"
   - Default: 5 (if not set)
   - `absint()`: Convert to absolute integer (no negatives)
   - Fallback to 5 if invalid

2. **Create TMDB Client**
   ```php
   $client = new Tmdb_Client();
   ```
   - Fresh instance each run
   - Ready to make API requests
   - Handles caching internally

3. **Get Movies to Sync**
   ```php
   $movies = $this->get_movies_to_sync( $limit );
   ```
   - Query for unsynced movies
   - Sorted by least recently synced first
   - Limited to $limit (e.g., 5)

4. **Check for Empty Results**
   ```php
   if ( empty( $movies ) ) {
       return;
   }
   ```
   - All movies synced? Done.
   - No movies published? Done.

5. **Sync Each Movie**
   ```php
   foreach ( $movies as $post ) {
       $this->sync_single_movie( $post, $client );
   }
   ```
   - Loop through unsynced movies
   - Fetch from TMDB
   - Update rating, date, poster

6. **Error Handling**
   ```php
   catch ( \Throwable $e ) {
       // Store error
       update_option( 'rt_tmdb_last_sync_error', [...] );
       do_action( 'rt_movie_library_tmdb_sync_error', $e );
   }
   ```
   - If ANY exception occurs, catch it
   - Log to options (admin can see it)
   - Fire hook (other plugins can react)
   - Don't crash (graceful degradation)

**Why Try-Catch?**
- Prevent fatal errors from breaking WP-Cron
- Allows other cron tasks to run
- Provides error information for debugging

**Error Storage:**
```php
update_option( 'rt_tmdb_last_sync_error', [
    'message' => 'Connection timeout',
    'time' => 1616000000
], false );
```
- `false` = Don't autoload this option
- Can be checked from admin area
- Shows when sync last failed

---

### Method 5: `get_movies_to_sync()` - Private Helper

```php
private function get_movies_to_sync( int $limit ): array {
    $query = new \WP_Query(
        array(
            'post_type'              => 'rt-movie',
            'post_status'            => 'publish',
            'posts_per_page'         => $limit,
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'orderby'                => array(
                'meta_value_num' => 'ASC',
                'ID'             => 'ASC',
            ),
            'meta_query'             => array(
                'relation' => 'OR',
                array(
                    'key'     => self::META_SYNCED_AT,
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => self::META_SYNCED_AT,
                    'compare' => 'EXISTS',
                ),
            ),
        )
    );
    
    return $query->posts;
}
```

**Purpose:** Get published movies that need syncing, prioritizing unsynced ones

**Query Parameters Explained:**

```php
'post_type' => 'rt-movie',
```
- Only get custom post type 'rt-movie'
- Don't get pages, posts, etc.

```php
'post_status' => 'publish',
```
- Only published movies
- Skip drafts, scheduled, trash

```php
'posts_per_page' => $limit,
```
- Get max $limit movies (e.g., 5)
- Each cron run syncs max 5 movies
- If 100 unsynced: Takes 20 runs to sync all

```php
'no_found_rows' => true,
```
- **Performance optimization!**
- Don't COUNT total rows
- We don't need to know if there are 100 more
- Saves expensive COUNT query
- Just tell us the first 5

```php
'update_post_term_cache' => false,
```
- **Performance optimization!**
- Don't load taxonomy terms
- We're not displaying on frontend
- Just syncing metadata
- Saves database queries

```php
'orderby' => array(
    'meta_value_num' => 'ASC',
    'ID'             => 'ASC',
),
```
- Primary: Sort by meta_value_num ascending
  - Which meta value? `_mw_tmdb_synced_at` (set by meta_query)
  - Unsynced (meta doesn't exist): Treated as 0 (comes first)
  - Synced with timestamp: 1615459200, 1615459300, etc.
  - ASC = Ascending = Oldest synced first
- Secondary: Sort by ID ascending (tiebreaker)

**Why This Sorting?**
```
Desired sync priority:

1. Never synced (no meta) → timestamp = 0 → comes first
   Movie A: NO sync timestamp
   Movie B: NO sync timestamp

2. Old sync (old timestamp) → comes next
   Movie C: synced at 1615459200 (long ago)
   Movie D: synced at 1615459300 (long ago)

3. Recent sync (new timestamp) → comes last
   Movie E: synced at 1730000000 (just now)
   Movie F: synced at 1730000100 (just now)
```

```php
'meta_query' => array(
    'relation' => 'OR',
    array(
        'key'     => self::META_SYNCED_AT,
        'compare' => 'NOT EXISTS',
    ),
    array(
        'key'     => self::META_SYNCED_AT,
        'compare' => 'EXISTS',
    ),
),
```

**Why this meta_query with OR?**
- Would only get posts with the meta key
- Meta query filters by meta key existence

**What This Does:**
```
Get posts WHERE:
    - Meta key '_mw_tmdb_synced_at' does NOT exist
    OR
    - Meta key '_mw_tmdb_synced_at' DOES exist

Result: Gets ALL published movies (synced + unsynced)
```

**Without this meta_query:**
- `get_movies_to_sync()` would need to check after query
- Or we'd only get synced/unsynced separately
- With OR, we get all in one query

**The Returned Array:**
```php
return $query->posts;
```

Array of WP_Post objects:
```php
[
    WP_Post {
        ID: 123,
        post_title: 'The Shawshank Redemption',
        post_type: 'rt-movie',
        ...
    },
    WP_Post {
        ID: 124,
        post_title: 'Inception',
        post_type: 'rt-movie',
        ...
    },
    ...
]
```

---

### Method 6: `sync_single_movie()` - Private Helper

```php
private function sync_single_movie( \WP_Post $post, Tmdb_Client $client ): void {
    // Search TMDB for this movie
    $tmdb_data = $client->search_movie( $post->post_title );
    
    if ( is_wp_error( $tmdb_data ) ) {
        // Couldn't find on TMDB, mark as synced anyway
        update_post_meta( $post->ID, self::META_SYNCED_AT, time() );
        return;
    }
    
    // Update rating if changed
    $new_rating      = (float) ( $tmdb_data['vote_average'] ?? 0 );
    $existing_rating = (float) get_post_meta( $post->ID, 'rt-movie-meta-basic-rating', true );
    
    if ( $existing_rating !== $new_rating ) {
        update_post_meta( $post->ID, 'rt-movie-meta-basic-rating', $new_rating );
    }
    
    // Update release date if changed
    $new_date      = trim( (string) ( $tmdb_data['release_date'] ?? '' ) );
    $existing_date = trim( (string) get_post_meta( $post->ID, 'rt-movie-meta-basic-release-date', true ) );
    
    if ( '' !== $new_date && $existing_date !== $new_date ) {
        update_post_meta( $post->ID, 'rt-movie-meta-basic-release-date', $new_date );
    }
    
    // Download and attach poster
    $new_poster_path = trim( (string) ( $tmdb_data['poster_path'] ?? '' ) );
    $this->sync_movie_poster( $post->ID, $new_poster_path );
    
    // Mark as synced (timestamp)
    update_post_meta( $post->ID, self::META_SYNCED_AT, time() );
}
```

**Purpose:** Sync one movie's metadata with TMDB

**Complete Flow:**

1. **Search TMDB for Movie**
   ```php
   $tmdb_data = $client->search_movie( $post->post_title );
   ```
   - Uses movie title stored in WordPress
   - Returns array: `['title', 'vote_average', 'release_date', 'poster_path']`
   - Or WP_Error if not found

2. **Handle Not Found**
   ```php
   if ( is_wp_error( $tmdb_data ) ) {
       update_post_meta( $post->ID, self::META_SYNCED_AT, time() );
       return;
   }
   ```
   - Movie not on TMDB? That's okay.
   - Still mark as synced (to avoid re-checking constantly)
   - Move on to next movie

3. **Update Rating (if Changed)**
   ```php
   $new_rating      = (float) ( $tmdb_data['vote_average'] ?? 0 );
   $existing_rating = (float) get_post_meta( $post->ID, 'rt-movie-meta-basic-rating', true );
   
   if ( $existing_rating !== $new_rating ) {
       update_post_meta( $post->ID, 'rt-movie-meta-basic-rating', $new_rating );
   }
   ```

   **Why Check Before Updating?**
   ```php
   if ( $existing_rating !== $new_rating ) {
   ```
   - Only update if value changed
   - Avoids unnecessary database writes
   - Saves performance
   - Reduces database transactions

   **Null Coalescing Operator:**
   ```php
   $tmdb_data['vote_average'] ?? 0
   ```
   - If `vote_average` key exists: use it
   - If missing: use 0
   - Prevents undefined key notices

4. **Update Release Date (if Changed)**
   ```php
   $new_date      = trim( (string) ( $tmdb_data['release_date'] ?? '' ) );
   $existing_date = trim( (string) get_post_meta( ... ) );
   
   if ( '' !== $new_date && $existing_date !== $new_date ) {
       update_post_meta( $post->ID, 'rt-movie-meta-basic-release-date', $new_date );
   }
   ```

   **Why Double Check?**
   ```php
   if ( '' !== $new_date && $existing_date !== $new_date ) {
   ```
   - `'' !== $new_date`: Only update if we have a real date
   - `$existing_date !== $new_date`: Only update if changed

5. **Download and Attach Poster**
   ```php
   $new_poster_path = trim( (string) ( $tmdb_data['poster_path'] ?? '' ) );
   $this->sync_movie_poster( $post->ID, $new_poster_path );
   ```
   - Complex enough for separate method

6. **Mark as Synced**
   ```php
   update_post_meta( $post->ID, self::META_SYNCED_AT, time() );
   ```
   - `time()`: Current Unix timestamp
   - Next sync, this movie moves to end of queue
   - Unsynced movies get priority

---

### Method 7: `sync_movie_poster()` - Private Helper

```php
private function sync_movie_poster( int $post_id, string $poster_path ): void {
    if ( '' === $poster_path ) {
        return;  // No poster available
    }
    
    $existing_path          = trim( (string) get_post_meta( $post_id, self::META_POSTER_PATH, true ) );
    $existing_attachment_id = absint( get_post_meta( $post_id, self::META_POSTER_ATTACHMENT_ID, true ) );
    
    // Skip download if same poster path
    if ( $poster_path === $existing_path ) {
        // Already have this poster
        if ( has_post_thumbnail( $post_id ) ) {
            return;  // Poster is set, all good
        }
        
        // Poster was deleted, re-attach from storage
        if ( $existing_attachment_id > 0 && get_post( $existing_attachment_id ) instanceof \WP_Post ) {
            set_post_thumbnail( $post_id, $existing_attachment_id );
            return;
        }
    }
    
    // New poster or old one missing, download and attach
    $this->load_media_dependencies();
    
    $poster_url = esc_url_raw( self::TMDB_POSTER_BASE_URL . $poster_path );
    
    if ( '' === $poster_url ) {
        return;
    }
    
    $attachment_id = media_sideload_image( $poster_url, $post_id, null, 'id' );
    
    if ( is_wp_error( $attachment_id ) ) {
        return;
    }
    
    $attachment_id = absint( $attachment_id );
    
    if ( $attachment_id < 1 ) {
        return;
    }
    
    set_post_thumbnail( $post_id, $attachment_id );
    update_post_meta( $post_id, self::META_POSTER_PATH, $poster_path );
    update_post_meta( $post_id, self::META_POSTER_ATTACHMENT_ID, $attachment_id );
}
```

**Purpose:** Download poster from TMDB and attach to movie post

**Complete Flow:**

1. **Check if Poster Path Empty**
   ```php
   if ( '' === $poster_path ) {
       return;
   }
   ```
   - No poster on TMDB for this movie
   - Nothing to download

2. **Check if Poster Already Downloaded**
   ```php
   $existing_path = trim( (string) get_post_meta( $post_id, self::META_POSTER_PATH, true ) );
   ```
   - What poster did we download before?
   - Stored in post meta

3. **Optimization: Skip Re-Download**
   ```php
   if ( $poster_path === $existing_path ) {
   ```
   - Same poster as before?
   - Don't download again (saves bandwidth)

4. **Check if Poster is Set**
   ```php
   if ( has_post_thumbnail( $post_id ) ) {
       return;  // It's set, we're done
   }
   ```
   - Is featured image already attached?
   - Yes → Skip

5. **Re-Attach if Deleted**
   ```php
   if ( $existing_attachment_id > 0 && get_post( $existing_attachment_id ) instanceof \WP_Post ) {
       set_post_thumbnail( $post_id, $existing_attachment_id );
       return;
   }
   ```
   - Featured image was deleted but attachment exists?
   - Re-set it
   - No download needed

6. **Download New Poster**
   ```php
   $this->load_media_dependencies();
   ```
   - Load WordPress media functions
   - Needed for `media_sideload_image()`

7. **Build Full URL**
   ```php
   $poster_url = esc_url_raw( self::TMDB_POSTER_BASE_URL . $poster_path );
   ```
   - Base: `https://image.tmdb.org/t/p/w500`
   - Poster: `/a28jWPkAY0XG9R3XQwXczs95Jxl.jpg`
   - Full: `https://image.tmdb.org/t/p/w500/a28jWPkAY0XG9R3XQwXczs95Jxl.jpg`
   - `esc_url_raw()`: Sanitize for HTTP requests

8. **Download Image**
   ```php
   $attachment_id = media_sideload_image( $poster_url, $post_id, null, 'id' );
   ```
   - `media_sideload_image()`: WordPress function
   - Downloads image from URL
   - Attachs to post
   - Fourth parameter `'id'`: Return attachment ID (not HTML)
   - Returns: Attachment ID (int) or WP_Error

9. **Handle Download Errors**
   ```php
   if ( is_wp_error( $attachment_id ) ) {
       return;  // Download failed, skip
   }
   ```
   - Server unreachable? Skip.
   - Image corrupted? Skip.
   - File too large? Skip.
   - Just return, don't crash

10. **Validate Attachment ID**
    ```php
    $attachment_id = absint( $attachment_id );
    
    if ( $attachment_id < 1 ) {
        return;
    }
    ```
    - Ensure it's a positive integer
    - If 0 or negative: Skip

11. **Set as Featured Image**
    ```php
    set_post_thumbnail( $post_id, $attachment_id );
    ```
    - This attachment is now the post's featured image
    - Shows in movies listing, single page, etc.

12. **Store Metadata**
    ```php
    update_post_meta( $post_id, self::META_POSTER_PATH, $poster_path );
    update_post_meta( $post_id, self::META_POSTER_ATTACHMENT_ID, $attachment_id );
    ```
    - `META_POSTER_PATH`: What poster path we downloaded
    - `META_POSTER_ATTACHMENT_ID`: Where we stored it locally
    - Next sync: Check these before re-downloading

---

### Method 8: `load_media_dependencies()` - Private Helper

```php
private function load_media_dependencies(): void {
    if ( ! function_exists( 'media_sideload_image' ) ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }
    
    if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }
    
    if ( ! function_exists( 'download_url' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
}
```

**Purpose:** Load WordPress media functions (only loaded in admin by default)

**Why Needed?**
- WP-Cron runs on frontend (not admin)
- Media functions in `/wp-admin/includes/` not loaded
- We need them to download images
- So we manually include them

**What Each File Provides:**

| File | Functions |
|------|-----------|
| `media.php` | `media_sideload_image()` - Download and attach image |
| `image.php` | `wp_generate_attachment_metadata()` - Generate thumbnails |
| `file.php` | `download_url()` - Download file from URL |

**Why Check Before Including?**
```php
if ( ! function_exists( 'media_sideload_image' ) ) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
}
```
- Maybe already loaded by another plugin?
- Don't include twice (causes errors)
- Check first, include only if needed

**ABSPATH Constant:**
- WordPress constant pointing to WordPress installation root
- Example: `/var/www/html/wp/`
- `ABSPATH . 'wp-admin/includes/media.php'` = `/var/www/html/wp/wp-admin/includes/media.php`

---

## 6. How TMDB & WP-Cron Work Together

### The Complete Ecosystem

```
Plugin Activation
    ↓
Tmdb_Sync::schedule() called
    ↓
register_cron_interval() fires
    ↓
WordPress stores in wp_options:
    - Custom interval: rt_every_30_min (1800 seconds)
    - Scheduled event: rt_tmdb_sync at [next_run_time]
    ↓
Every 30 minutes (when site gets traffic)
    ↓
WordPress checks: rt_tmdb_sync due?
    ├─ YES → Fire action hook
    │    ↓
    │    run_sync() callback executes
    │    ↓
    │    Create Tmdb_Client instance
    │    ↓
    │    get_movies_to_sync(5)
    │    ↓
    │    Query for 5 unsynced/least-recently-synced published movies
    │    ↓
    │    For each movie:
    │        ├─ search_movie() via Tmdb_Client
    │        ├─ request_tmdb() → wp_remote_get() → TMDB API
    │        ├─ Sanitize response
    │        ├─ Update rating (if changed)
    │        ├─ Update release date (if changed)
    │        ├─ Download poster (if new)
    │        └─ Mark synced (timestamp)
    │    ↓
    │    All done, next run in 30 minutes
    └─ NO  → Continue loading page
```

### Information Flow

```
TMDB Database
    ↓
TMDB API (/search/movie, /movie/upcoming)
    ↓
wp_remote_get() [WordPress HTTP API]
    ↓
json_decode() [Convert JSON to PHP]
    ↓
Sanitization [Clean data]
    ↓
WordPress Database (wp_postmeta)
    ├─ rt-movie-meta-basic-rating
    ├─ rt-movie-meta-basic-release-date
    ├─ _mw_tmdb_poster_path
    └─ _mw_tmdb_poster_attachment_id
    ↓
Featured Images (wp_posts attachment)
    ↓
WordPress Frontend [Display to Users]
```

### Data Storage

When a movie is synced, stored metadata:

```php
// Post meta stored for movie with ID=123
[
    'rt-movie-meta-basic-rating' => 8.9,
    'rt-movie-meta-basic-release-date' => '2024-03-15',
    '_mw_tmdb_synced_at' => 1730000000,  // When synced
    '_mw_tmdb_poster_path' => '/a28jWPkAY0XG9R3XQwXczs95Jxl.jpg',
    '_mw_tmdb_poster_attachment_id' => 456,  // Local attachment ID
]

// Featured image (in wp_posts table)
[
    'ID' => 123,  // Movie post
    'meta' => [
        '_thumbnail_id' => 456  // Points to attachment
    ]
]
```

---

## 7. Data Flow & Execution Journey

### Scenario: Movie Syncing from Start to Finish

Let's trace what happens when cron fires:

#### User visits WordPress site at 10:00 AM
```
WordPress boots
    ↓
Checks: Any cron tasks due?
    ├─ IS rt_tmdb_sync due?
    │  └─ Last run: 9:30 AM
    │  └─ Interval: 30 minutes
    │  └─ Current: 10:00 AM
    │  └─ YES, RUN IT!
    │  ↓
    │  run_sync() called
    │  ↓
    │  [SEE STEPS BELOW]
    └─ Continue loading page
```

#### STEP 1: Get Settings
```php
$limit = absint( get_option( Settings::OPTION_MOVIE_LIMIT, 5 ) );
```
- Looks in wp_options for 'rt_movie_library_movie_limit'
- If not set or invalid: use 5
- Result: $limit = 5

#### STEP 2: Create TMDB Client
```php
$client = new Tmdb_Client();
```
- New instance ready to make API calls
- Will cache upcoming movies for 4 hours

#### STEP 3: Query Unsynced Movies
```php
$movies = $this->get_movies_to_sync( 5 );
```

**Database Query Executed:**
```sql
SELECT ID, post_title, post_type FROM wp_posts
WHERE post_type = 'rt-movie'
  AND post_status = 'publish'
  AND (
    -- Meta '_mw_tmdb_synced_at' doesn't exist (never synced)
    OR Meta '_mw_tmdb_synced_at' exists (already synced)
  )
ORDER BY 
  meta_value_num ASC,  -- Unsynced first (NULL = 0), then oldest
  ID ASC
LIMIT 5
```

**Results Example:**
```php
Array (
    [0] => WP_Post {
        'ID' => 50,
        'post_title' => 'The Shawshank Redemption',
        'post_type' => 'rt-movie'
    },
    [1] => WP_Post {
        'ID' => 51,
        'post_title' => 'Inception',
        'post_type' => 'rt-movie'
    },
    // ... 3 more
)
```

#### STEP 4: Sync First Movie (The Shawshank Redemption)

**Call 1: Search TMDB**
```php
$tmdb_data = $client->search_movie( 'The Shawshank Redemption' );
```

**What Happens Inside:**

1. Get API key from options
   ```php
   $api_key = (string) get_option( 'rt_movie_library_tmdb_api_key', '' );
   ```
   - Value: `'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6'`

2. Sanitize and validate
   ```php
   if ( 1 !== preg_match( '/^[a-f0-9]{32}$/i', $api_key ) ) {
       return new \WP_Error( 'rt_tmdb_invalid_api_key', ... );
   }
   ```
   - Format valid ✓

3. Build request URL
   ```php
   $request_url = 'https://api.themoviedb.org/3/search/movie'
       . '?query=The+Shawshank+Redemption'
       . '&language=en-US'
       . '&page=1'
       . '&api_key=a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6';
   ```

4. Make HTTP request
   ```php
   $response = wp_remote_get( $request_url, [
       'timeout' => 3,
       'sslverify' => true,
       'headers' => [ 'Accept' => 'application/json' ]
   ]);
   ```
   - WordPress HTTP API makes request
   - TMDB server responds

5. TMDB Response (200 OK)
   ```json
   {
       "results": [
           {
               "id": 278,
               "title": "The Shawshank Redemption",
               "vote_average": 8.7,
               "release_date": "1994-10-14",
               "poster_path": "/xj0hKJL1I5nSzT4Gb6L7NnfIrX.jpg",
               "original_language": "en",
               "adult": false,
               // ... more fields
           },
           // ... other results
       ],
       "page": 1,
       "total_pages": 1,
       // ... more fields
   }
   ```

6. Decode JSON
   ```php
   $data = json_decode( json_response_body, true );
   // Result: PHP array
   ```

7. Validate structure
   ```php
   if ( ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
       return new \WP_Error( ... );
   }
   // Passes ✓
   ```

8. Find exact title match
   ```php
   foreach ( $data['results'] as $movie ) {
       $candidate = strtolower( trim( $movie['title'] ?? '' ) );
       $query = strtolower( trim( 'The Shawshank Redemption' ) );
       
       if ( $candidate === $query ) {
           // MATCH! Return sanitized data
           return [
               'title' => 'The Shawshank Redemption',
               'release_date' => '1994-10-14',
               'vote_average' => 8.7,
               'poster_path' => '/xj0hKJL1I5nSzT4Gb6L7NnfIrX.jpg'
           ];
       }
   }
   ```

**Back in sync_single_movie():**

```php
$tmdb_data = [
    'title' => 'The Shawshank Redemption',
    'release_date' => '1994-10-14',
    'vote_average' => 8.7,
    'poster_path' => '/xj0hKJL1I5nSzT4Gb6L7NnfIrX.jpg'
];
```

**Update Rating:**
```php
$new_rating = 8.7;
$existing_rating = (float) get_post_meta( 50, 'rt-movie-meta-basic-rating', true );
// Result: 0 (no previous rating)

if ( 0 !== 8.7 ) {
    update_post_meta( 50, 'rt-movie-meta-basic-rating', 8.7 );
    // Database updated ✓
}
```

**Update Release Date:**
```php
$new_date = '1994-10-14';
$existing_date = (float) get_post_meta( 50, 'rt-movie-meta-basic-release-date', true );
// Result: '' (not set)

if ( '' !== '1994-10-14' && '' !== '1994-10-14' ) {
    update_post_meta( 50, 'rt-movie-meta-basic-release-date', '1994-10-14' );
    // Database updated ✓
}
```

**Download Poster:**
```php
$new_poster_path = '/xj0hKJL1I5nSzT4Gb6L7NnfIrX.jpg';
$this->sync_movie_poster( 50, $new_poster_path );

// Inside sync_movie_poster():

// Load media functions
$this->load_media_dependencies();
require_once '/wp-admin/includes/media.php';
require_once '/wp-admin/includes/image.php';
require_once '/wp-admin/includes/file.php';

// Build full URL
$poster_url = 'https://image.tmdb.org/t/p/w500/xj0hKJL1I5nSzT4Gb6L7NnfIrX.jpg';

// Download and attach
$attachment_id = media_sideload_image(
    'https://image.tmdb.org/t/p/w500/xj0hKJL1I5nSzT4Gb6L7NnfIrX.jpg',
    50,  // movie post ID
    null,  // no description
    'id'  // return attachment ID
);
// Returns: 456 (attachment ID)

// Set as featured image
set_post_thumbnail( 50, 456 );
// Database updated ✓

// Store metadata
update_post_meta( 50, '_mw_tmdb_poster_path', '/xj0hKJL1I5nSzT4Gb6L7NnfIrX.jpg' );
update_post_meta( 50, '_mw_tmdb_poster_attachment_id', 456 );
// Database updated ✓
```

**Mark as Synced:**
```php
update_post_meta( 50, '_mw_tmdb_synced_at', 1730000000 );  // time()
// Database updated ✓
```

#### STEP 5: Sync Next 4 Movies (Same Process)
- Inception
- Dark Knight
- etc.

#### STEP 6: Cron Complete
- All 5 movies synced
- Next run scheduled 30 minutes later
- WordPress continues loading page normally

---

## 8. Making Changes to the Code

### Common Modifications & Patterns

#### Modification 1: Change Sync Interval

**Current:** Every 30 minutes

**Goal:** Change to every 15 minutes

**Files to Change:**
1. [class-tmdb-sync.php](class-tmdb-sync.php#L27-L35)

**Step 1: Change Interval Definition**
```php
// BEFORE
private const CRON_INTERVAL = 'rt_every_30_min';

// AFTER
private const CRON_INTERVAL = 'rt_every_15_min';
```

**Step 2: Update Interval Registration**
```php
// BEFORE
public function register_cron_interval( array $schedules ): array {
    if ( ! isset( $schedules[ self::CRON_INTERVAL ] ) ) {
        $schedules[ self::CRON_INTERVAL ] = array(
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 30 Minutes', 'rt-movie-library' ),
        );
    }
    return $schedules;
}

// AFTER
public function register_cron_interval( array $schedules ): array {
    if ( ! isset( $schedules[ self::CRON_INTERVAL ] ) ) {
        $schedules[ self::CRON_INTERVAL ] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 15 Minutes', 'rt-movie-library' ),
        );
    }
    return $schedules;
}
```

**Step 3: Reschedule (for existing installations)**
```php
// In plugin update code
Tmdb_Sync::unschedule();
Tmdb_Sync::schedule();
```

**Why Both Steps?**
- Step 1: Changes the internal name (prevents old cron from running)
- Step 2: Changes the actual interval value
- Step 3: Clears old schedule and creates new one

---

#### Modification 2: Change Movies Per Sync

**Current:** 5 movies per 30-minute sync

**Goal:** 10 movies per sync

**Files to Change:**
1. Settings storage (wherever API key is stored)
2. Optionally: [class-tmdb-sync.php](class-tmdb-sync.php#L108)

**Step 1: Change Default in Code**
```php
// BEFORE
$limit = absint( get_option( Settings::OPTION_MOVIE_LIMIT, 5 ) );

// AFTER
$limit = absint( get_option( Settings::OPTION_MOVIE_LIMIT, 10 ) );
```

**Step 2: Update Admin Settings**
- In settings page, change default
- Users can override per installation

**Why Default to 10?**
- More movies synced per run
- Fewer runs needed to sync everything
- Higher server load per sync (but less often)

---

#### Modification 3: Add Metadata Field (Genre)

**Current:** Syncs: rating, release date, poster

**Goal:** Also sync TMDB movie genre

**Files to Change:**
1. [class-tmdb-client.php](class-tmdb-client.php#L276-L295) - Add genre to payload
2. [class-tmdb-sync.php](class-tmdb-sync.php#L193-L217) - Update syncing logic

**Step 1: Update Tmdb_Client to Include Genre**
```php
// In sanitize_movie_payload()

// BEFORE
$sanitized = array(
    'title'        => '',
    'release_date' => '',
    'vote_average' => 0.0,
    'poster_path'  => '',
);

// AFTER
$sanitized = array(
    'title'        => '',
    'release_date' => '',
    'vote_average' => 0.0,
    'poster_path'  => '',
    'genres'       => array(),  // NEW
);

// Add extraction
if ( isset( $movie['genres'] ) && is_array( $movie['genres'] ) ) {
    $sanitized['genres'] = $this->sanitize_genres( $movie['genres'] );
}

// New helper method
private function sanitize_genres( array $raw_genres ): array {
    $genres = array();
    foreach ( $raw_genres as $genre ) {
        if ( is_array( $genre ) && isset( $genre['name'] ) ) {
            $genres[] = sanitize_text_field( (string) $genre['name'] );
        }
    }
    return $genres;
}
```

**Step 2: Update Syncing**
```php
// In sync_single_movie()

// After poster sync, add:
if ( ! empty( $tmdb_data['genres'] ) ) {
    $genres = $tmdb_data['genres'];
    
    // If using custom taxonomy
    wp_set_object_terms( 
        $post->ID, 
        $genres, 
        'rt-movie-genre',  // taxonomy slug
        false  // replace existing
    );
}
```

**Complete Picture:**
```
TMDB API returns genres:
[
    { "id": 18, "name": "Drama" },
    { "id": 80, "name": "Crime" }
]
    ↓
sanitize_genres() converts to:
[ "Drama", "Crime" ]
    ↓
sync_single_movie() sets as movie taxonomy terms
    ↓
WordPress associates movie with these genre categories
```

---

#### Modification 4: Skip Syncing for Specific Movies

**Current:** Syncs all published movies

**Goal:** Skip certain movies (manual override)

**Files to Change:**
1. [class-tmdb-sync.php](class-tmdb-sync.php#L157-L180) - Query modification

**Step 1: Add Meta Key Check**
```php
// In get_movies_to_sync()

// BEFORE
'meta_query' => array(
    'relation' => 'OR',
    array(
        'key'     => self::META_SYNCED_AT,
        'compare' => 'NOT EXISTS',
    ),
    array(
        'key'     => self::META_SYNCED_AT,
        'compare' => 'EXISTS',
    ),
),

// AFTER
'meta_query' => array(
    'relation' => 'AND',  // CHANGED from OR
    array(
        'relation' => 'OR',
        array(
            'key'     => self::META_SYNCED_AT,
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key'     => self::META_SYNCED_AT,
            'compare' => 'EXISTS',
        ),
    ),
    array(
        'key'     => '_rt_skip_tmdb_sync',  // NEW
        'compare' => 'NOT EXISTS',  // Skip if this meta exists
    ),
),
```

**Step 2: Use in Admin**
- Add meta box to movie editor
- Users can check "Skip TMDB Sync"
- Stores `_rt_skip_tmdb_sync = 1`
- `get_movies_to_sync()` excludes it

---

#### Modification 5: Add Error Logging to Database

**Current:** Errors stored in options only

**Goal:** Create error log table and store errors there

**Files to Change:**
1. [class-tmdb-sync.php](class-tmdb-sync.php#L100-L120) - Error handling

**Step 1: Create Custom Table**
```php
// In activation hook
global $wpdb;
$charset = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tmdb_sync_errors (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    post_id BIGINT UNSIGNED,
    error_code VARCHAR(100),
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY (post_id),
    KEY (created_at)
) $charset;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta( $sql );
```

**Step 2: Log Errors**
```php
// In catch block of run_sync()

global $wpdb;

$wpdb->insert(
    $wpdb->prefix . 'tmdb_sync_errors',
    array(
        'post_id' => 0,  // or specific post if available
        'error_code' => $e->getCode(),
        'error_message' => $e->getMessage(),
    ),
    array( '%d', '%s', '%s' )
);

// Also store in options for backward compatibility
update_option( 'rt_tmdb_last_sync_error', [
    'message' => $e->getMessage(),
    'time' => time()
], false );
```

---

#### Modification 6: Add Retry Logic

**Current:** If TMDB returns error, mark synced and move on

**Goal:** Retry failed movies multiple times

**Files to Change:**
1. [class-tmdb-sync.php](class-tmdb-sync.php#L193-L217) - Sync logic

**Step 1: Add Retry Meta Key**
```php
// Constants
private const META_SYNC_ATTEMPTS = '_mw_tmdb_sync_attempts';
private const MAX_SYNC_ATTEMPTS = 3;
```

**Step 2: Update Sync Logic**
```php
// In sync_single_movie()

$tmdb_data = $client->search_movie( $post->post_title );

if ( is_wp_error( $tmdb_data ) ) {
    // Don't just mark synced - check attempts
    $attempts = absint( get_post_meta( $post->ID, self::META_SYNC_ATTEMPTS, true ) );
    
    if ( $attempts >= self::MAX_SYNC_ATTEMPTS ) {
        // Max attempts reached, give up
        update_post_meta( $post->ID, self::META_SYNCED_AT, time() );
    } else {
        // Increment attempts, try again next time
        update_post_meta( $post->ID, self::META_SYNC_ATTEMPTS, $attempts + 1 );
    }
    return;
}

// Success - reset attempts
if ( $tmdb_data ) {
    delete_post_meta( $post->ID, self::META_SYNC_ATTEMPTS );
    update_post_meta( $post->ID, self::META_SYNCED_AT, time() );
}
```

**Step 3: Update Query**
```php
// Modify orderby in get_movies_to_sync()
// to prioritize movies with fewer attempts
'orderby' => array(
    'meta_value_num' => 'ASC',  // _mw_tmdb_sync_attempts (fewer first)
    'ID'             => 'ASC',
),
'meta_query' => array(
    // ... existing query, but change orderby meta key
),
```

---

## 9. Common Modifications & Patterns

### Pattern 1: Caching Strategy

**Existing Pattern:**
```php
$cached = get_transient( self::TRANSIENT_UPCOMING );
if ( false !== $cached && is_array( $cached ) ) {
    return $cached;
}

// ... fetch from API ...

set_transient( self::TRANSIENT_UPCOMING, $movies, self::TRANSIENT_TTL );
return $movies;
```

**This Pattern Is Used For:**
- API response caching (expensive operations)
- Dashboard widget data
- Anything fetched from external sources

**Why Transients?**
- Auto-expiring (no manual cleanup)
- Can use persistent cache (Redis, Memcached) if configured
- Persists across page loads
- Reduces API calls and improves performance

### Pattern 2: Null-Coalescing with Defaults

**Existing Pattern:**
```php
$new_rating = (float) ( $tmdb_data['vote_average'] ?? 0 );
$new_date   = trim( (string) ( $tmdb_data['release_date'] ?? '' ) );
```

**Used For:**
- Safely accessing array keys that might not exist
- Providing sensible defaults
- Preventing "undefined key" errors

**Why Important?**
- Makes code defensive (robust against API changes)
- Clear intent (what's the default?)
- No PHP notices/warnings

### Pattern 3: Compare Before Update

**Existing Pattern:**
```php
$new_rating      = (float) ( $tmdb_data['vote_average'] ?? 0 );
$existing_rating = (float) get_post_meta( $post->ID, 'key', true );

if ( $existing_rating !== $new_rating ) {
    update_post_meta( $post->ID, 'key', $new_rating );
}
```

**Used For:**
- Reducing database writes
- Only updating when data actually changed
- Improving performance

**Performance Impact:**
```
100 movies to sync:

Without check:
    100 update_post_meta() calls
    100 database writes
    100 meta table rows modified

With check:
    ~10 movies changed (on average)
    10 update_post_meta() calls
    10 database writes
    10 meta table rows modified

Result: 10x fewer database transactions!
```

### Pattern 4: Post Meta Queries with NOT EXISTS

**Existing Pattern:**
```php
'meta_query' => array(
    array(
        'key'     => self::META_SYNCED_AT,
        'compare' => 'NOT EXISTS',
    ),
),
```

**Used For:**
- Finding posts that don't have a meta key
- Identifying "unprocessed" or "unsync" items
- Priority sorting (unprocessed first)

**How It Works:**
```
WordPress generates SQL:
    SELECT * FROM wp_posts
    WHERE ID NOT IN (
        SELECT post_id FROM wp_postmeta
        WHERE meta_key = '_mw_tmdb_synced_at'
    )
```

### Pattern 5: Error Handling with WP_Error

**Existing Pattern:**
```php
if ( is_wp_error( $response ) ) {
    return $response;  // Propagate error
}

if ( is_wp_error( $data ) ) {
    return $data;
}

// Later...
if ( is_wp_error( $result ) ) {
    // Handle error
    do_action( 'rt_movie_library_sync_error', $result );
    return;
}
```

**Used For:**
- Standardized error handling across plugin
- Can propagate errors up call stack
- Includes error code and message
- WordPress standard pattern

**Benefits:**
- Consistent error format
- Can distinguish between errors and empty results
- Errors are objects: `is_wp_error()` check is reliable

### Pattern 6: Try-Catch for Synchronization

**Existing Pattern:**
```php
try {
    // Run sync
} catch ( \Throwable $e ) {
    // Log error
    update_option( 'rt_tmdb_last_sync_error', [...] );
    do_action( 'rt_movie_library_tmdb_sync_error', $e );
    return;
}
```

**Used For:**
- Preventing fatal errors during cron
- Graceful error handling
- Allowing other crons to run

**Why Catch Throwable?**
- `\Exception`: Regular exceptions
- `\Error`: PHP fatal errors (parse errors, type errors)
- `\Throwable`: Parent interface (catches both)

---

## 10. Troubleshooting & Debugging

### Issue 1: Cron Doesn't Run

**Symptoms:**
- Movies never get synced
- `_mw_tmdb_synced_at` meta never updated
- Posters never downloaded

**Diagnoses:**

1. **Check if scheduled**
   ```php
   // In wp-cli or custom admin page
   $next_run = wp_next_scheduled( 'rt_tmdb_sync' );
   echo $next_run ? "Next run: " . date( 'Y-m-d H:i:s', $next_run ) : "Not scheduled";
   ```

2. **Check if interval registered**
   ```php
   $schedules = apply_filters( 'cron_schedules', array() );
   if ( isset( $schedules['rt_every_30_min'] ) ) {
       echo "Interval registered ✓";
   } else {
       echo "Interval NOT registered ✗";
   }
   ```

3. **Manually trigger cron**
   ```php
   // In custom admin action
   do_action( 'rt_tmdb_sync' );
   echo "Cron triggered manually";
   ```

4. **Check error log**
   ```php
   $error = get_option( 'rt_tmdb_last_sync_error' );
   if ( $error ) {
       echo "Last error: " . $error['message'];
       echo "Time: " . date( 'Y-m-d H:i:s', $error['time'] );
   } else {
       echo "No errors recorded";
   }
   ```

**Solutions:**

- **If not scheduled**: Call `Tmdb_Sync::schedule()` manually
  ```php
  // In admin page or custom function
  Tmdb_Sync::schedule();
  echo "Cron scheduled ✓";
  ```

- **If low traffic**: Enable real cron
  ```php
  // In wp-config.php
  define( 'DISABLE_WP_CRON', false );
  
  // Then set system cron:
  // */5 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
  ```

- **If loopback blocked**: Check server network access
  ```php
  // In admin or custom function
  $response = wp_remote_get( site_url() );
  if ( is_wp_error( $response ) ) {
      echo "Loopback blocked: " . $response->get_error_message();
  } else {
      echo "Loopback works ✓";
  }
  ```

---

### Issue 2: TMDB API Errors

**Symptoms:**
- Errors in `rt_tmdb_last_sync_error` option
- Status 401 Unauthorized
- Status 429 Rate Limited

**Diagnoses:**

1. **Check API Key**
   ```php
   $api_key = get_option( Settings::OPTION_API_KEY, '' );
   echo strlen( $api_key ) === 32 ? "API key looks valid" : "API key invalid";
   ```

2. **Test API Request**
   ```php
   $client = new Tmdb_Client();
   $result = $client->get_upcoming_movies();
   
   if ( is_wp_error( $result ) ) {
       echo "Error: " . $result->get_error_code();
       echo "Message: " . $result->get_error_message();
   } else {
       echo "Success! Movies: " . count( $result );
   }
   ```

3. **Check Rate Limit**
   ```php
   // TMDB API rate limits: 40 requests per 10 seconds
   // If you get 429, you're making too many requests
   // Check: Are you running sync in a loop?
   ```

**Solutions:**

- **401 Unauthorized**
  ```php
  // Wrong API key - regenerate from TMDB account
  // Get new key from: https://www.themoviedb.org/settings/api
  update_option( Settings::OPTION_API_KEY, 'new_key_here' );
  ```

- **429 Rate Limited**
  ```php
  // Reduce sync frequency or limit
  update_option( Settings::OPTION_MOVIE_LIMIT, 3 );  // Fewer per run
  // OR increase interval (edit code)
  ```

- **Connection Timeout**
  ```php
  // Increase timeout in request_tmdb()
  'timeout' => 5,  // was 3
  ```

---

### Issue 3: Posters Not Downloading

**Symptoms:**
- Rating and date sync fine
- Posters stay empty
- No featured images set

**Diagnoses:**

1. **Check Media Dependencies**
   ```php
   echo function_exists( 'media_sideload_image' ) ? "✓ media.php loaded" : "✗ Not loaded";
   echo function_exists( 'download_url' ) ? "✓ file.php loaded" : "✗ Not loaded";
   ```

2. **Check Poster Path**
   ```php
   $post_id = 123;  // a movie
   $poster_path = get_post_meta( $post_id, '_mw_tmdb_poster_path', true );
   echo $poster_path ?: "No poster path stored";
   ```

3. **Test Download**
   ```php
   $poster_url = 'https://image.tmdb.org/t/p/w500/xj0hKJL1I5nSzT4Gb6L7NnfIrX.jpg';
   
   require_once ABSPATH . 'wp-admin/includes/media.php';
   require_once ABSPATH . 'wp-admin/includes/image.php';
   require_once ABSPATH . 'wp-admin/includes/file.php';
   
   $result = media_sideload_image( $poster_url, $post_id, null, 'id' );
   
   if ( is_wp_error( $result ) ) {
       echo "Error: " . $result->get_error_message();
   } else {
       echo "Success! Attachment ID: " . $result;
   }
   ```

**Solutions:**

- **File Download Blocked**
  ```php
  // Check wp-config.php
  // Ensure these are not defined as empty:
  // define( 'FS_METHOD', '' );
  // Should be 'direct' or auto-detected
  
  // Or check file permissions on /uploads/
  ```

- **SSL Certificate Error**
  ```php
  // TMDB uses HTTPS
  // If 'sslverify' => true fails
  // Solution 1: Update WordPress
  // Solution 2: Update certificates: wp plugin install wp-cli && wp cli update
  // Solution 3: 'sslverify' => false (not recommended)
  ```

- **Uploads Directory Full**
  ```php
  // Check available disk space
  // If full: Sync will fail silently
  // Solution: Delete old attachments or upgrade server
  ```

---

### Issue 4: Movies Not Queuing for Sync

**Symptoms:**
- Some movies sync fine
- Others never get synced
- Sync count doesn't progress

**Diagnoses:**

1. **Check Query Results**
   ```php
   $sync = Tmdb_Sync::get_instance();
   $movies = $sync->get_movies_to_sync( 10 );
   echo "Movies to sync: " . count( $movies );
   ```

2. **Check Movie Status**
   ```php
   $post = get_post( 50 );  // movie ID
   echo "Status: " . $post->post_status;
   echo $post->post_status === 'publish' ? "✓ Published" : "✗ Not published";
   ```

3. **Check Sync Meta**
   ```php
   $synced_at = get_post_meta( 50, '_mw_tmdb_synced_at', true );
   echo $synced_at ? "Last synced: " . date( 'Y-m-d H:i:s', $synced_at ) : "Never synced";
   ```

**Solutions:**

- **Movie not published**
  ```php
  // Publish from admin or via code
  wp_update_post( array(
      'ID' => 50,
      'post_status' => 'publish'
  ) );
  ```

- **Movie wrongly marked as synced**
  ```php
  // Reset sync time
  delete_post_meta( 50, '_mw_tmdb_synced_at' );
  // Next cron will sync again
  ```

- **Query not finding movies**
  ```php
  // Check if rt-movie post type exists
  $movie_post_type = get_post_type_object( 'rt-movie' );
  echo $movie_post_type ? "✓ Post type registered" : "✗ Not registered";
  ```

---

### Debug Logging

Add this to enable detailed logging:

```php
// In class-tmdb-sync.php, in run_sync()

error_log( "=== TMDB Sync Started ===" );
error_log( "Limit: " . $limit );

$movies = $this->get_movies_to_sync( $limit );
error_log( "Movies to sync: " . count( $movies ) );

foreach ( $movies as $post ) {
    error_log( "Syncing: {$post->ID} - {$post->post_title}" );
    
    try {
        $this->sync_single_movie( $post, $client );
        error_log( "✓ Synced: {$post->ID}" );
    } catch ( \Throwable $e ) {
        error_log( "✗ Error syncing {$post->ID}: " . $e->getMessage() );
    }
}

error_log( "=== TMDB Sync Completed ===" );
```

View logs in `/wp-content/debug.log` (if `WP_DEBUG_LOG` enabled in wp-config.php).

---

## Summary

**TMDB Integration:**
- HTTP client wrapper around TMDB API v3
- Makes requests, parses JSON, sanitizes data
- Caches results for performance
- Used by dashboard widgets and cron

**WordPress Cron:**
- Pseudo-cron system (triggered by page loads)
- Stores schedules in wp_options
- Fires action hooks on schedule
- Perfect for background tasks without system access

**How They Work Together:**
1. Activation: Schedule `rt_tmdb_sync` event every 30 minutes
2. Every 30 mins (on traffic): WordPress fires `rt_tmdb_sync` action
3. Tmdb_Sync::run_sync() runs
4. Gets 5 unsynced movies
5. Uses Tmdb_Client to search TMDB for each
6. Updates movie metadata (rating, date, poster)
7. Marks as synced
8. Next run: 30 minutes later

**Key Files:**
- `class-tmdb-client.php`: API communication (~358 lines)
- `class-tmdb-sync.php`: Cron scheduling and syncing (~296 lines)

**Key Methods:**
- `Tmdb_Client::search_movie()`: Find movie on TMDB
- `Tmdb_Sync::run_sync()`: Main cron callback
- `Tmdb_Sync::sync_single_movie()`: Sync one movie
- `Tmdb_Sync::sync_movie_poster()`: Download poster

You're now ready to understand, modify, and extend this code!

