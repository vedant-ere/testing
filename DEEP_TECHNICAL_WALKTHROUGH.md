# Deep Technical Walkthrough: RT Movie Library WordPress Plugin

## A Comprehensive Code Review Walkthrough for Senior Engineers

---

## Table of Contents

1. [System Architecture Overview](#system-architecture-overview)
2. [Plugin Bootstrap & Initialization](#plugin-bootstrap--initialization)
3. [Rewrite Rules Deep Dive](#rewrite-rules-deep-dive)
4. [Custom Roles & Capabilities System](#custom-roles--capabilities-system)
5. [Dashboard Widgets Architecture](#dashboard-widgets-architecture)
6. [TMDB API Integration](#tmdb-api-integration)
7. [WordPress Cron Job System](#wordpress-cron-job-system)
8. [Custom Post Types & Taxonomies](#custom-post-types--taxonomies)
9. [REST API Endpoints](#rest-api-endpoints)
10. [Full Plugin Execution Lifecycle](#full-plugin-execution-lifecycle)
11. [Key Design Patterns & Architecture Decisions](#key-design-patterns--architecture-decisions)
12. [Common Interview Questions](#common-interview-questions)
13. [Performance & Security Considerations](#performance--security-considerations)
14. [Possible Improvements & Reviewer Feedback](#possible-improvements--reviewer-feedback)

---

## System Architecture Overview

### What This Plugin Does

The **RT Movie Library** is an enterprise-grade WordPress plugin that transforms a standard WordPress installation into a sophisticated **movie database management system**. It does four key things:

1. **Manages Movie & Person Data** — Custom post types with rich metadata
2. **Organizes Content** — Taxonomies for genres, careers, production companies, languages, ratings, and tags
3. **Maintains SEO-Friendly URLs** — Custom rewrite rules that generate movie/person URLs based on genre/career
4. **Syncs External Data** — Integrates with TMDB API to automatically update movie metadata (ratings, posters, release dates)
5. **Provides Admin Intelligence** — Dashboard widgets showing recent movies, top-rated films, and upcoming releases

### High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│  WORDPRESS CORE LIFECYCLE                                    │
│  plugins_loaded → init → wp_dashboard_setup → rest_api_init │
└────────────┬────────────────────────────────────────────────┘
             │
    ┌────────┴─────────┐
    │                  │
    ▼                  ▼
┌──────────────┐  ┌─────────────────────┐
│ PLUGIN CLASS │  │ REWRITE_RULES CLASS │
│              │  │                     │
│ Registers:   │  │ Registers:          │
│ - CPTs       │  │ - URL patterns      │
│ - Taxonomies │  │ - Query vars        │
│ - Meta boxes │  │ - Permalink filters │
│ - REST APIs  │  │                     │
│ - Dashboard  │  │ Pattern:            │
│   Widgets    │  │ /movie/{genre}/{   │
│              │  │  slug}-{id}/        │
└──────────────┘  └─────────────────────┘
    │                  │
    └────────┬─────────┘
             │
    ┌────────┴──────────────────────────┐
    │                                   │
    ▼                                   ▼
┌──────────────────────┐    ┌─────────────────────┐
│ ROLES & CAPABILITIES │    │ TMDB API INTEGRATION │
│                      │    │                     │
│ - movie-manager role │    │ - Tmdb_Client      │
│ - 40+ capabilities   │    │ - Tmdb_Sync (cron) │
│ - Capability checks  │    │ - HTTP requests    │
│   on key actions     │    │ - Poster downloads │
└──────────────────────┘    └────────┬────────────┘
                                     │
                                     ▼
                            ┌──────────────────┐
                            │ WP CRON SYSTEM   │
                            │                  │
                            │ Every 30 min:    │
                            │ - Query unsynced │
                            │   movies         │
                            │ - Call TMDB API  │
                            │ - Update meta    │
                            │ - Download poster│
                            └──────────────────┘
```

### Namespace & File Structure

```
rt-movie-library/
├── rt-movie-library.php                 [Main plugin file]
│
├── includes/
│   ├── classes/
│   │   ├── class-activator.php          [Plugin activation]
│   │   ├── class-deactivator.php        [Plugin deactivation]
│   │   ├── class-plugin.php             [Main orchestrator]
│   │   ├── class-settings.php           [Plugin options/settings]
│   │   │
│   │   ├── post-types/
│   │   │   ├── class-movie.php          [rt-movie CPT]
│   │   │   └── class-person.php         [rt-person CPT]
│   │   │
│   │   ├── taxonomies/
│   │   │   ├── class-genre.php
│   │   │   ├── class-career.php
│   │   │   └── ... [6 total taxonomies]
│   │   │
│   │   ├── rewrite/
│   │   │   └── class-rewrite-rules.php  [Custom URL rewriting]
│   │   │
│   │   ├── roles/
│   │   │   └── class-movie-manager-role.php
│   │   │
│   │   ├── dashboard/
│   │   │   └── class-dashboard-widgets.php
│   │   │
│   │   ├── tmdb/
│   │   │   ├── class-tmdb-client.php    [API client]
│   │   │   └── class-tmdb-sync.php      [WP Cron handler]
│   │   │
│   │   ├── rest/
│   │   │   ├── class-base-cpt-controller.php
│   │   │   ├── class-cpt-endpoints.php
│   │   │   ├── class-movie-controller.php
│   │   │   ├── class-person-controller.php
│   │   │   └── ... [handlers for crew, merges, etc]
│   │   │
│   │   ├── meta-boxes/
│   │   │   └── class-movie-meta-box.php
│   │   │
│   │   ├── shortcodes/
│   │   │   ├── class-movie-shortcode.php
│   │   │   └── class-person-shortcode.php
│   │   │
│   │   └── cli/
│   │       └── class-movie-cli-command.php
│   │
│   ├── helpers/
│   │   ├── autoloader.php               [PSR-4 autoloader]
│   │   └── class-admin-filters.php
│   │
│   ├── traits/
│   │   └── class-singleton.php
│   │
│   └── languages/
│       └── rt-movie-library.po
│
└── assets/
    ├── css/
    │   ├── frontend/
    │   │   ├── movie-shortcode.css
    │   │   └── person-shortcode.css
    │   └── admin/
    └── js/
        ├── frontend/
        └── admin/
            └── required-taxonomy-notice.js
```

### Design Pattern: Singleton + Autoloader

The plugin uses **Singleton pattern** extensively to ensure classes are instantiated only once:

```php
// From Traits/Singleton.php
trait Singleton {
    private static $instance;
    
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    protected function __construct() {}  // Prevents direct instantiation
}
```

This pattern ensures that:
- Hooks are registered only once
- Shared state is preserved
- Resources are initialized lazily (on first use)
- Memory footprint is minimized

The **SPL Autoloader** automatically loads classes based on namespace:
- Converts `RT_Movie_Library\Classes\Post_Types\Movie` → `includes/classes/post-types/class-movie.php`
- Supports PSR-4 naming conventions
- Eliminates need for manual `require` statements

---

## Plugin Bootstrap & Initialization

### Entry Point: rt-movie-library.php

```php
<?php
/**
 * Main plugin file
 */
define( 'RT_MOVIE_LIBRARY_PATH', plugin_dir_path( __FILE__ ) );
define( 'RT_MOVIE_LIBRARY_URL', plugin_dir_url( __FILE__ ) );
define( 'RT_MOVIE_LIBRARY_VERSION', '1.0.0' );

// Load PSR-4 autoloader
require_once RT_MOVIE_LIBRARY_PATH . 'includes/helpers/autoloader.php';

// Hook into WordPress initialization
add_action( 'plugins_loaded', static function (): void {
    load_plugin_textdomain( 'rt-movie-library', false, ... );
    
    // Instantiate core services
    RT_Movie_Library\Classes\Tmdb\Tmdb_Sync::get_instance();
    RT_Movie_Library\Classes\Rewrite\Rewrite_Rules::get_instance();
    RT_Movie_Library\Classes\Plugin::get_instance();
});

// Register activation/deactivation hooks
register_activation_hook( __FILE__, [Activator::class, 'activate'] );
register_deactivation_hook( __FILE__, [Deactivator::class, 'deactivate'] );
```

### Why This Architecture?

1. **plugins_loaded Hook** — Fires after all plugins are loaded, guaranteeing other plugins won't conflict
2. **Lazy Initialization** — Services only instantiate when `get_instance()` is called
3. **Activation/Deactivation Hooks** — Ensure roles, rewrites, and crons are properly set up/torn down
4. **Text Domain** — Loads translations, supporting multilingual sites

### Activation Sequence

```
User clicks "Activate Plugin"
           ↓
register_activation_hook() fires
           ↓
Activator::activate() called
           ↓
┌─────────────────────────────────────────┐
│ 1. Plugin::get_instance()->register()   │
│    - Registers CPTs (Movie, Person)     │
│    - Registers Taxonomies (7 total)     │
│    - Loads meta boxes                   │
│    - Loads REST endpoints                │
│    - Loads dashboard widgets             │
│                                         │
│ 2. Movie_Manager_Role::activate()       │
│    - Adds 'movie-manager' role          │
│    - Grants 40+ capabilities            │
│    - Syncs caps to admin role           │
│                                         │
│ 3. Rewrite_Rules::flush_on_activate()   │
│    - Registers rewrite tags             │
│    - Registers rewrite rules            │
│    - Flushes .htaccess (if needed)      │
│                                         │
│ 4. Tmdb_Sync::schedule()                │
│    - Schedules cron event               │
│    - First run: 30 minutes from now      │
└─────────────────────────────────────────┘
           ↓
Plugin is active and ready
```

### Deactivation Sequence

```
User clicks "Deactivate Plugin"
           ↓
register_deactivation_hook() fires
           ↓
Deactivator::deactivate() called
           ↓
┌──────────────────────────────────────────┐
│ 1. Movie_Manager_Role::deactivate()      │
│    - Removes 'movie-manager' role        │
│    - Revokes custom capabilities         │
│                                          │
│ 2. Rewrite_Rules::flush_on_deactivate()  │
│    - Flushes .htaccess                   │
│    - Removes custom rewrite rules        │
│                                          │
│ 3. Tmdb_Sync::unschedule()               │
│    - Clears all cron hooks               │
│    - Prevents background tasks           │
└──────────────────────────────────────────┘
           ↓
Plugin is inactive
```

---

## Rewrite Rules Deep Dive

### The Problem: SEO-Friendly Movie URLs

By default, WordPress generates post URLs like:
```
https://example.com/?post_type=rt-movie&p=42
```

This is terrible for SEO. We want:
```
https://example.com/movie/action/the-matrix-42/
https://example.com/person/actor/tom-cruise-99/
```

**The URL structure solves two problems:**

1. **Human-readable** — Users and Google bots can understand the URL
2. **Contextual** — The genre/career in the URL reinforces the content theme
3. **Unique** — Post ID at the end ensures uniqueness (allows movies with same name)

### WordPress Rewrite Architecture (Internal Behavior)

WordPress uses a sophisticated regex-based URL rewriting system:

```
┌────────────────────────────────────┐
│ User requests: /movie/action/...   │
│                                    │
│ WordPress Core (wp-rewrite.php):   │
│ 1. Load .htaccess rewrite rules    │
│ 2. Match URL against patterns      │
│ 3. Extract capture groups          │
│ 4. Build query variables           │
│ 5. Pass to WP_Query                │
└────────────────────────────────────┘
           ↓
    ┌──────────────────┐
    │ WP_Rewrite class │
    │ (wp-includes/)   │
    │                  │
    │ - Stores all     │
    │   rewrite rules  │
    │ - Generates      │
    │   .htaccess      │
    │ - Handles query  │
    │   variable        │
    │   mapping        │
    └──────────────────┘
           ↓
        Matched
           ↓
    query_vars = [
        'post_type' => 'rt-movie',
        'mw_genre' => 'action',
        'name' => 'the-matrix',
        'p' => 42
    ]
           ↓
    WP_Query loads post #42
           ↓
    Template loads (single-rt-movie.php)
```

### Class: Rewrite_Rules

**Location:** [includes/classes/rewrite/class-rewrite-rules.php](includes/classes/rewrite/class-rewrite-rules.php)

#### Key Methods

##### 1. `__construct()` — Hook Registration

```php
protected function __construct() {
    add_action( 'init', array( $this, 'register_rewrite_tags' ) );
    add_action( 'init', array( $this, 'register_rewrite_rules' ) );
    add_filter( 'post_type_link', array( $this, 'filter_post_type_link' ), 10, 4 );
}
```

**Why these hooks?**

- **init hook** — WordPress has loaded all post types and taxonomies by this point, so we can safely register rewrite tags
- **post_type_link filter** — Fires when WordPress generates a permalink for a post, allowing us to override the default format

**Hook Priority (10)** — Standard priority, fires after WordPress core hooks but before custom code

##### 2. `register_rewrite_tags()` — Define Custom URL Placeholders

```php
public function register_rewrite_tags(): void {
    add_rewrite_tag( '%mw_genre%', '([^/]+)' );
    add_rewrite_tag( '%mw_career%', '([^/]+)' );
}
```

**What's happening here?**

- `add_rewrite_tag()` tells WordPress: "I will use `%mw_genre%` in my rewrite rules"
- `'([^/]+)'` — This is the regex capture group pattern
  - `[^/]+` means "one or more characters that are NOT forward slashes"
  - This captures the slug (e.g., `action`, `thriller`)
- WordPress automatically stores the captured value in `$_GET['mw_genre']`

**Without this step:**

The rewrite rule wouldn't work because WordPress wouldn't know how to extract and store the genre slug from the URL.

##### 3. `register_rewrite_rules()` — Create Rewrite Rules

```php
public function register_rewrite_rules(): void {
    // Movie URL pattern: /movie/{genre}/{slug}-{id}/
    add_rewrite_rule(
        '^movie/([^/]+)/([^/]+)-(\d+)/?$',
        'index.php?post_type=rt-movie&mw_genre=$matches[1]&name=$matches[2]&p=$matches[3]',
        'top'
    );

    // Person URL pattern: /person/{career}/{slug}-{id}/
    add_rewrite_rule(
        '^person/([^/]+)/([^/]+)-(\d+)/?$',
        'index.php?post_type=rt-person&mw_career=$matches[1]&name=$matches[2]&p=$matches[3]',
        'top'
    );
}
```

**Breaking down the movie rewrite rule:**

```
Pattern:  '^movie/([^/]+)/([^/]+)-(\d+)/?$'
          
          ^          = Start of URL
          movie/     = Literal "movie/"
          ([^/]+)    = Capture group 1: genre slug (action, thriller, etc)
          /          = Literal forward slash
          ([^/]+)    = Capture group 2: post name/slug (the-matrix, etc)
          -          = Literal hyphen
          (\d+)      = Capture group 3: post ID (digits only)
          /?         = Optional trailing slash
          $          = End of URL
          
Query:    'index.php?post_type=rt-movie&mw_genre=$matches[1]&name=$matches[2]&p=$matches[3]'

          Tells WordPress:
          1. This is for post_type = rt-movie
          2. Custom query var: mw_genre = first capture group
          3. Standard param: name = post slug
          4. Standard param: p = post ID
```

**The third parameter: 'top'**

```php
add_rewrite_rule( $regex, $query, $position )
```

- `'top'` = Place this rule at the TOP of the .htaccess file (highest priority)
- This ensures `/movie/action/...` is matched before more general patterns
- Without 'top', WordPress would check other rules first (slower, potential mismatches)

**Internal WordPress behavior:**

1. Rule is stored in WordPress options table: `wp_options.rewrite_rules`
2. When `flush_rewrite_rules()` is called, all rules are written to `.htaccess`
3. On each request, Apache/Nginx reads `.htaccess` and matches the URL
4. Once matched, the query string is passed to `index.php` (WordPress loads)

##### 4. `filter_post_type_link()` — Generate Permalinks

```php
public function filter_post_type_link( string $link, \WP_Post $post ): string {
    if ( 'rt-movie' === $post->post_type ) {
        return $this->build_movie_link( $post );
    }

    if ( 'rt-person' === $post->post_type ) {
        return $this->build_person_link( $post );
    }

    return $link;
}
```

**Why this filter?**

- When you call `get_permalink( $movie_id )`, WordPress fires `post_type_link` filter
- This lets us override the generated URL format
- Ensures URLs are generated in the correct format

**Without this filter:**

`get_permalink()` would generate a wrong URL (or no URL at all).

##### 5. `build_movie_link()` — Construct Movie URL

```php
private function build_movie_link( \WP_Post $post ): string {
    $genre_slug = $this->get_first_term_slug( $post->ID, self::TAXONOMY_GENRE );

    return trailingslashit(
        sprintf(
            '%s/movie/%s/%s-%d',
            untrailingslashit( home_url() ),
            $genre_slug,
            $post->post_name,
            $post->ID
        )
    );
}
```

**Step-by-step:**

1. **Get the first genre term:**
   ```php
   $genre_slug = $this->get_first_term_slug( $post->ID, 'rt-movie-genre' );
   ```
   - Queries the database: Which genre is assigned to this movie?
   - Returns slug (e.g., "action") or fallback ("uncategorized")

2. **Build the URL using sprintf:**
   ```
   Format: '{home_url}/movie/{genre}/{post_name}-{post_id}'
   
   Example:
   https://example.com + /movie + /action + /the-matrix + -42
   = https://example.com/movie/action/the-matrix-42
   ```

3. **Add trailing slash:**
   ```php
   trailingslashit() ensures: .../the-matrix-42/ (with slash)
   ```

##### 6. `get_first_term_slug()` — Fetch Genre for Movie

```php
private function get_first_term_slug( int $post_id, string $taxonomy ): string {
    $terms = wp_get_object_terms(
        $post_id,
        $taxonomy,
        array(
            'orderby' => 'term_id',
            'order'   => 'ASC',
            'number'  => 1,
        )
    );

    if ( is_wp_error( $terms ) || empty( $terms ) || ! isset( $terms[0]->slug ) ) {
        return self::FALLBACK_SLUG;  // 'uncategorized'
    }

    $slug = sanitize_title( (string) $terms[0]->slug );

    return '' !== $slug ? $slug : self::FALLBACK_SLUG;
}
```

**Why `wp_get_object_terms()`?**

- Queries `wp_term_relationships` table
- Finds all terms assigned to this post
- Filters by taxonomy
- Returns only the first term (ordered by term_id)

**Edge cases handled:**

1. **No genre assigned** → Returns 'uncategorized'
2. **WP_Error returned** → Returns 'uncategorized'
3. **Empty slug** → Returns 'uncategorized'
4. **Invalid characters in slug** → `sanitize_title()` cleans it

### URL Resolution Flow (Example)

**User requests:** `https://example.com/movie/action/the-matrix-42/`

```
┌────────────────────────────────────────────────────────┐
│ 1. Apache/Nginx reads request                          │
│    Path: /movie/action/the-matrix-42/                  │
│                                                        │
│ 2. Checks .htaccess (RewriteEngine)                    │
│    Matches: ^movie/([^/]+)/([^/]+)-(\d+)/?$           │
│    Extracts:                                          │
│    - $matches[1] = 'action'                           │
│    - $matches[2] = 'the-matrix'                       │
│    - $matches[3] = '42'                               │
│                                                        │
│ 3. Rewrites to:                                        │
│    /index.php?post_type=rt-movie&mw_genre=action&    │
│    name=the-matrix&p=42                               │
│                                                        │
│ 4. WordPress receives query string                     │
└────────────────────────────────────────────────────────┘
           ↓
┌────────────────────────────────────────────────────────┐
│ WordPress Core (wp-blog-header.php)                    │
│                                                        │
│ 5. WP_Query parses query variables:                   │
│    - post_type = 'rt-movie'                           │
│    - name = 'the-matrix'                              │
│    - p = 42                                           │
│                                                        │
│ 6. Builds SQL query:                                   │
│    SELECT * FROM wp_posts                             │
│    WHERE ID = 42 AND post_type = 'rt-movie'          │
│    AND post_name = 'the-matrix'                       │
│                                                        │
│ 7. Loads post object                                   │
│    $post->ID = 42                                     │
│    $post->post_title = 'The Matrix'                   │
│    $post->post_name = 'the-matrix'                    │
│    $post->post_type = 'rt-movie'                      │
└────────────────────────────────────────────────────────┘
           ↓
┌────────────────────────────────────────────────────────┐
│ 8. WordPress template system                           │
│    is_single() = true                                  │
│    get_post_type() = 'rt-movie'                        │
│                                                        │
│ 9. Loads template (priority order):                    │
│    - single-rt-movie.php (if exists)                  │
│    - single.php (if exists)                           │
│    - index.php (fallback)                             │
│                                                        │
│ 10. Render post content                                │
└────────────────────────────────────────────────────────┘
           ↓
        HTML sent to browser
```

### Rewrite Rules Flushing

**What is flushing?**

Flushing rebuilds the rewrite rules cache and the `.htaccess` file. It's necessary when:
- Plugin is activated (new rules need to be registered)
- Permalink settings change (WordPress flushes automatically)
- Custom post types are registered

**When does it happen?**

```php
// In Activator::activate()
Rewrite_Rules::flush_on_activate();

// Which calls:
public static function flush_on_activate(): void {
    $instance = self::get_instance();
    $instance->register_rewrite_tags();
    $instance->register_rewrite_rules();
    
    flush_rewrite_rules( false );  // false = don't force hard flush
}
```

**Why `flush_rewrite_rules( false )`?**

- `false` — Uses transient cache (faster, rebuilds on next request)
- `true` — Forces immediate flush (slower, but ensures immediate effect)

**What gets flushed?**

```
WordPress Options Table (wp_options)
├── rewrite_rules (option_name)
│   └── Serialized array of all rewrite rules
│       [0] => '^movie/([^/]+)/([^/]+)-(\d+)/?$ => ...'
│       [1] => '^person/([^/]+)/([^/]+)-(\d+)/?$ => ...'
│       ... [hundreds of WordPress core rules]

.htaccess file (in site root)
├── RewriteRule ^movie/([^/]+)/([^/]+)-(\d+)/?$ /index.php?... [L]
├── RewriteRule ^person/([^/]+)/([^/]+)-(\d+)/?$ /index.php?... [L]
└── ... [all other rules]
```

### Performance Considerations

**Query Optimization:**

In `get_first_term_slug()`, we use:

```php
$terms = wp_get_object_terms(
    $post_id,
    $taxonomy,
    array(
        'number'  => 1,  // ← Limit to 1 result
        'orderby' => 'term_id',
    )
);
```

**Why 'number' => 1?**

- Without it: Database returns ALL genres for the movie
- With it: Database stops after finding 1 genre
- This is called **query limiting** and prevents unnecessary data

**Potential Improvement:**

Cache the genre slug in post meta:

```php
// Store genre slug when movie is saved
update_post_meta( $post_id, '_movie_genre_slug', $genre_slug );

// Later, retrieve from cache first
$cached = get_post_meta( $post_id, '_movie_genre_slug', true );
if ( ! empty( $cached ) ) {
    return $cached;
}
```

### Security Considerations

**1. Regex Injection:**

The regex pattern `^movie/([^/]+)/([^/]+)-(\d+)/?$` is **not vulnerable** because:
- It's hardcoded in the plugin code
- User input can't modify the pattern
- The pattern only matches valid formats

**2. SQL Injection (in WP_Query):**

Protected by WordPress sanitization:
```php
$terms = wp_get_object_terms( $post_id, $taxonomy );
```
- `wp_get_object_terms()` sanitizes internally
- Uses prepared statements

**3. Data Validation:**

The rewrite rule forces:
- Genre must be a slug (no special chars)
- Post name must be a slug
- Post ID must be numeric

Invalid URLs are rejected by the regex before they reach WordPress.

### Common Issues & Edge Cases

**Issue 1: ".htaccess file not writable"**

```
Symptom: URLs don't work, getting 404 errors
Cause: Web server can't write to .htaccess
Fix: chmod 666 /var/www/html/.htaccess
```

**Issue 2: "Movie returns 404 even though it exists"**

```
Cause: Movie doesn't have a genre assigned
Effect: URL is /movie/uncategorized/...
Fix: Assign a genre to the movie
```

**Issue 3: "Same post name with different IDs fails"**

```
Example: /movie/action/the-matrix-42 and /movie/action/the-matrix-99
Problem: WordPress matches by post_name first, ID is secondary
Fix: Unique post names per post type (WordPress enforces this)
```

---

## Custom Roles & Capabilities System

### The Problem: Granular Permission Control

By default, WordPress has these roles:
- **Administrator** — Full access
- **Editor** — Can manage posts/pages
- **Author** — Can write own posts
- **Contributor** — Can write draft posts
- **Subscriber** — Can view published content

For a movie library, we need an intermediate role:
- **Movie Manager** — Can manage movies but NOT delete users, change settings, etc.

### WordPress Capability System (Internal Behavior)

WordPress uses a **two-tier capability system**:

```
┌─────────────────────────────────────────┐
│ Capability Hierarchies                  │
│                                         │
│ Primitive Capabilities:                 │
│ - read, edit_posts, delete_posts        │
│ - (Directly check user meta)            │
│                                         │
│ Meta Capabilities:                      │
│ - edit_post, delete_post, read_post     │
│ - (Require mapping function)            │
└─────────────────────────────────────────┘
```

**When WordPress checks `current_user_can( 'edit_post', $post_id )`:**

```
1. Check if it's a meta capability
   └─ Yes, it's a meta capability

2. Call map_meta_cap filter
   ┌──────────────────────────────────┐
   │ Is this a primitive capability? │
   │ - check user meta               │
   │                                │
   │ Is this a custom post type?    │
   │ - check custom capability      │
   │ - e.g., edit_rt-movie          │
   │                                │
   │ Return primitive capability    │
   └──────────────────────────────────┘

3. Check if user has primitive capability
   └─ Found in wp_usermeta table

4. Return true/false
```

### Class: Movie_Manager_Role

**Location:** [includes/classes/roles/class-movie-manager-role.php](includes/classes/roles/class-movie-manager-role.php)

#### Key Methods

##### 1. `activate()` — Create Role & Grant Capabilities

```php
public static function activate(): void {
    add_role(
        self::ROLE_SLUG,         // 'movie-manager'
        self::ROLE_NAME,         // 'Movie Manager'
        self::get_capabilities() // Array of capabilities
    );

    self::grant_caps_to_administrator();
}
```

**What `add_role()` does internally:**

```php
// WordPress Core (wp-includes/user.php)
function add_role( $role, $display_name, $capabilities = array() ) {
    // 1. Check if role already exists
    if ( get_role( $role ) ) {
        return;
    }
    
    // 2. Store in wp_options (role definitions)
    // wp_options.option_name = 'wp_user_roles'
    // Stores: ['movie-manager' => [...capabilities array...]]
    
    // 3. Return WP_Role object
    return $GLOBALS['wp_roles']->add_role( $role, $display_name, $capabilities );
}
```

**Database impact:**

```
wp_options table:
├── option_id: 123
├── option_name: 'wp_user_roles'
├── option_value: serialized array
│   {
│       'movie-manager' => {
│           'name' => 'Movie Manager',
│           'capabilities' => {
│               'read' => true,
│               'upload_files' => true,
│               'edit_rt-movie' => true,
│               'delete_rt-movie' => true,
│               ... [40+ more capabilities]
│           }
│       }
│   }
```

##### 2. `get_capabilities()` — Define Permissions

The plugin defines 40+ capabilities across multiple categories:

```php
private static function get_capabilities(): array {
    return array(
        // Base capabilities
        'read'                               => true,
        'upload_files'                       => true,

        // Movie Post Type (rt-movie / rt-movies)
        'edit_rt-movie'                      => true,
        'read_rt-movie'                      => true,
        'delete_rt-movie'                    => true,
        'edit_rt-movies'                     => true,
        'edit_others_rt-movies'              => true,
        'publish_rt-movies'                  => true,
        'read_private_rt-movies'             => true,
        'delete_rt-movies'                   => true,
        'delete_private_rt-movies'           => true,
        'delete_published_rt-movies'         => true,
        'delete_others_rt-movies'            => true,
        'edit_private_rt-movies'             => true,
        'edit_published_rt-movies'           => true,

        // Person Post Type (rt-person / rt-persons)
        'edit_rt-person'                     => true,
        'read_rt-person'                     => true,
        // ... [13 more person capabilities]

        // Taxonomy Capabilities
        'manage_rt-movie-genre'              => true,
        'assign_rt-movie-genre'              => true,
        'manage_rt-person-career'            => true,
        'assign_rt-person-career'            => true,
        // ... [more taxonomy capabilities]
    ];
}
```

**Why so many capabilities?**

WordPress automatically generates capability combinations for CPTs:

```
For CPT: rt-movie
├── edit_rt-movie          (edit own posts)
├── edit_rt-movies         (edit all posts)
├── edit_others_rt-movies  (edit others' posts)
├── edit_private_rt-movies (edit private posts)
├── edit_published_rt-movies (edit published posts)
├── delete_rt-movie        (delete own posts)
├── delete_rt-movies       (delete all posts)
├── delete_private_rt-movies
├── delete_published_rt-movies
├── delete_others_rt-movies
├── publish_rt-movies      (publish posts)
└── read_rt-movie          (read private posts)
```

This granularity allows:
- Prevent movie-managers from editing others' movies: `if ( ! current_user_can( 'edit_others_rt-movies' ) )`
- Prevent publishing without permission: `if ( ! current_user_can( 'publish_rt-movies' ) )`

##### 3. `grant_caps_to_administrator()` — Sync to Admin

```php
private static function grant_caps_to_administrator(): void {
    $admin = get_role( 'administrator' );

    if ( ! $admin instanceof \WP_Role ) {
        return;
    }

    foreach ( self::get_administrator_caps() as $cap => $grant ) {
        $admin->add_cap( $cap, $grant );
    }
}
```

**Why grant to admin?**

- Admins should have ALL capabilities including custom ones
- Without this, admin couldn't manage movies they don't have explicit caps for
- This ensures backward compatibility: admins always have full access

**How `add_cap()` works internally:**

```
When $admin->add_cap( 'edit_rt-movie', true ):
┌──────────────────────────────────────────┐
│ wp_usermeta table                        │
├─ user_id: 1 (admin)                      │
├─ meta_key: 'wp_capabilities'             │
├─ meta_value: serialized array            │
│   {                                      │
│       'administrator' => true,           │
│       'edit_rt-movie' => true,    ←──────│ Added here
│       'delete_rt-movie' => true,         │
│       ... [thousands of caps]            │
│   }                                      │
└──────────────────────────────────────────┘
```

##### 4. `deactivate()` — Clean Up

```php
public static function deactivate(): void {
    remove_role( self::ROLE_SLUG );
    self::revoke_caps_from_administrator();
}
```

**Important: Data Cleanup**

When a role is removed:
- Users with that role still exist
- WordPress reassigns them to `subscriber` role
- Their custom capabilities are ignored
- If plugin is reactivated, users are recreated as movie-managers

**Best Practice:** Before removing a role, reassign users:

```php
$users = get_users( array( 'role' => 'movie-manager' ) );
foreach ( $users as $user ) {
    $user->set_role( 'editor' );  // Move to editor role
}
remove_role( 'movie-manager' );
```

### Capability Checks in Practice

**Example 1: Restrict Movie Editing**

```php
if ( ! current_user_can( 'edit_rt-movie' ) ) {
    wp_die( 'You do not have permission to edit movies.' );
}
```

WordPress internally:
1. Check if user has `edit_rt-movie` capability
2. Look in `wp_usermeta` for user's capabilities
3. Check if `edit_rt-movie` is set to `true`
4. Return `true` if found, `false` otherwise

**Example 2: Restrict Genre Management**

```php
if ( ! current_user_can( 'manage_rt-movie-genre' ) ) {
    wp_die( 'You cannot manage genres.' );
}
```

**Example 3: Dashboard Widget Visibility**

```php
public function register_widgets(): void {
    if ( ! current_user_can( 'edit_rt-movies' ) ) {
        return;  // Widget not registered for non-managers
    }

    wp_add_dashboard_widget( ... );
}
```

### Capability Mapping with `map_meta_cap()`

WordPress allows custom capability mapping. For example:

```php
add_filter( 'map_meta_cap', function( $caps, $cap, $user_id, $args ) {
    if ( 'edit_post' === $cap && 'rt-movie' === get_post_type( $args[0] ) ) {
        // For editing movie posts, also require 'edit_rt-movie' capability
        return array( 'edit_rt-movie' );
    }
    return $caps;
}, 10, 4 );
```

**When is this used?**

When you check: `current_user_can( 'edit_post', $movie_id )`

WordPress:
1. Recognizes 'edit_post' as a meta capability
2. Calls `map_meta_cap()` to convert to primitive capability
3. Returns `['edit_rt-movie']` (custom capability)
4. Checks if user has `edit_rt-movie`

### Scenario: Movie Manager Workflow

**Workflow:**

1. Admin user with ID=1 creates a movie:
   ```php
   wp_insert_post( array(
       'post_type' => 'rt-movie',
       'post_title' => 'The Matrix',
       'post_author' => 2,  // Movie manager user ID
   ));
   ```

2. Movie manager (user ID=2) tries to edit:
   ```php
   if ( current_user_can( 'edit_rt-movies' ) ) {
       // Load edit form
   }
   ```

3. WordPress checks:
   - Get user 2's role: 'movie-manager'
   - Look in `wp_usermeta` for user 2's capabilities
   - Find `'edit_rt-movies' => true`
   - Return `true`, allow editing

4. Movie manager publishes:
   - Check: `current_user_can( 'publish_rt-movies' )`
   - User has this capability
   - Allow publication

### Security Considerations

**1. Privilege Escalation Prevention**

Movie managers can't:
- Delete user accounts (no `delete_users` cap)
- Change plugin settings (no `manage_options` cap)
- Install plugins (no `activate_plugins` cap)
- Edit others' movies (no `edit_others_rt-movies` cap)

**2. Cross-User Post Access**

A movie-manager can only edit their own movies by default:

```php
// WordPress default: edit_rt-movies = can edit own
// But: edit_others_rt-movies = NOT granted to movie-manager
// So: User can't edit other people's movies
```

**3. Capability Verification**

Always verify in sensitive operations:

```php
public function rest_delete_movie( $request ) {
    $post_id = $request['id'];
    
    if ( ! current_user_can( 'delete_rt-movies', $post_id ) ) {
        return new \WP_Error( 'forbidden', 'Cannot delete', array( 'status' => 403 ) );
    }
    
    wp_delete_post( $post_id );
}
```

### Potential Improvements

**1. Role-Specific Dashboard:**

```php
add_action( 'admin_menu', function() {
    if ( ! current_user_can( 'manage_options' ) && current_user_can( 'edit_rt-movies' ) ) {
        // Remove settings menu for movie-managers
        remove_menu_page( 'options-general.php' );
    }
});
```

**2. Content Ownership Restriction:**

```php
add_filter( 'posts_where', function( $where ) {
    if ( is_admin() && ! current_user_can( 'edit_others_rt-movies' ) ) {
        global $wpdb;
        $user_id = get_current_user_id();
        $where .= " AND {$wpdb->posts}.post_author = {$user_id}";
    }
    return $where;
});
```

---

## Dashboard Widgets Architecture

### The Problem: Admin Intelligence

WordPress administrators need at-a-glance information:
- "What are the most recent movies?"
- "Which movies have the highest ratings?"
- "What's coming to theaters soon?"

Dashboard widgets provide a centralized place for this intel.

### WordPress Dashboard System (Internal Behavior)

```
User loads wp-admin dashboard
        ↓
Fires: wp_dashboard_setup
        ↓
Hooks call: wp_add_dashboard_widget()
        ↓
Widget registered in $GLOBALS['wp_dashboard_control_panel']
        ↓
WordPress renders dashboard
        ↓
For each widget:
  1. Call widget callback (render function)
  2. Output buffered HTML
  3. Display in dashboard columns
```

### Class: Dashboard_Widgets

**Location:** [includes/classes/dashboard/class-dashboard-widgets.php](includes/classes/dashboard/class-dashboard-widgets.php)

#### Architecture

The dashboard contains **three widgets:**

```
┌──────────────────────────────────────────────┐
│ WordPress Admin Dashboard                    │
├──────────────────────────────────────────────┤
│  Recent Movies  │  Top Rated Movies          │
│  - Query Local  │  - Query Local DB          │
│  - Show titles  │  - Order by rating         │
│  - Show dates   │  - Show rating scores      │
├──────────────────────────────────────────────┤
│  Upcoming Movies (TMDB)                      │
│  - Call TMDB API                             │
│  - Cache results (4 hours)                   │
│  - Show release dates                        │
└──────────────────────────────────────────────┘
```

#### Key Methods

##### 1. `__construct()` — Bootstrap

```php
protected function __construct() {
    add_action( 'wp_dashboard_setup', array( $this, 'register_widgets' ) );
}
```

**Why `wp_dashboard_setup` hook?**

- Fires after WordPress loads dashboard-specific hooks
- Ensures all dashboard infrastructure is ready
- Perfect place to register widgets

##### 2. `register_widgets()` — Register Three Widgets

```php
public function register_widgets(): void {
    if ( ! current_user_can( 'edit_rt-movies' ) ) {
        return;
    }

    wp_add_dashboard_widget(
        'rt_widget_recent_movies',
        esc_html__( 'Recent Movies', 'rt-movie-library' ),
        array( $this, 'render_recent_movies' )
    );

    wp_add_dashboard_widget(
        'rt_widget_top_rated_movies',
        esc_html__( 'Top Rated Movies', 'rt-movie-library' ),
        array( $this, 'render_top_rated_movies' )
    );

    wp_add_dashboard_widget(
        'rt_widget_upcoming_movies',
        esc_html__( 'Upcoming Movies (TMDB)', 'rt-movie-library' ),
        array( $this, 'render_upcoming_movies' )
    );
}
```

**Breaking it down:**

```php
wp_add_dashboard_widget(
    'rt_widget_recent_movies',              // ← Unique widget ID
    esc_html__( 'Recent Movies', ... ),     // ← Display title
    array( $this, 'render_recent_movies' )  // ← Callback to render
);
```

**Internal WordPress behavior:**

```
wp_add_dashboard_widget() does:
┌─────────────────────────────────────┐
│ 1. Validate widget ID is unique     │
│ 2. Store callback function          │
│ 3. Validate user capability         │
│ 4. Register with dashboard filter   │
│ 5. Load user widget preferences     │
│    (which widgets to show/hide)     │
└─────────────────────────────────────┘
```

**Capability Check:**

```php
if ( ! current_user_can( 'edit_rt-movies' ) ) {
    return;  // Don't register widgets for non-managers
}
```

This ensures only movie-managers see the widgets.

##### 3. `render_recent_movies()` — Widget 1

```php
public function render_recent_movies(): void {
    $query = new \WP_Query(
        array(
            'post_type'              => self::POST_TYPE,
            'post_status'            => 'publish',
            'posts_per_page'         => self::WIDGET_POST_LIMIT,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
        )
    );

    if ( ! $query->have_posts() ) {
        echo '<p>' . esc_html__( 'No movies found.', 'rt-movie-library' ) . '</p>';
        wp_reset_postdata();
        return;
    }

    echo '<ul class="rt-dashboard-widget-list">';

    foreach ( $query->posts as $post ) {
        $edit_link = get_edit_post_link( $post->ID );

        if ( empty( $edit_link ) ) {
            continue;
        }

        $release_date = trim( (string) get_post_meta( $post->ID, self::META_RELEASE_DATE, true ) );
        $date_label   = '' !== $release_date ? __( 'Release Date', 'rt-movie-library' ) : __( 'Post Created On', 'rt-movie-library' );
        $date_value   = '' !== $release_date ? $release_date : get_the_date( 'Y-m-d', $post );

        printf(
            '<li><a href="%s">%s</a> <span class="rt-widget-date">(%s: %s)</span></li>',
            esc_url( $edit_link ),
            esc_html( get_the_title( $post ) ),
            esc_html( $date_label ),
            esc_html( $date_value )
        );
    }

    echo '</ul>';
    wp_reset_postdata();
}
```

**Query Parameters Explained:**

```php
'post_type'              => 'rt-movie'        // Only movies
'post_status'            => 'publish'         // Only published
'posts_per_page'         => 5                 // Limit to 5
'orderby'                => 'date'            // Sort by post date
'order'                  => 'DESC'            // Newest first

// PERFORMANCE OPTIMIZATIONS:
'no_found_rows'          => true              // Don't count total rows
                                              // (saves: SELECT COUNT(*) query)
'update_post_term_cache' => false             // Don't fetch taxonomies
                                              // (saves: SELECT FROM wp_term_relationships)
```

**Why these optimizations?**

Dashboard widgets are loaded on every page view. If you don't optimize:

```
Without optimizations (5 queries):
- SELECT * FROM wp_posts (get 5 movies)
- SELECT COUNT(*) FROM wp_posts (count total)
- SELECT * FROM wp_term_relationships (fetch genres)
- SELECT * FROM wp_term_taxonomy (fetch term info)
- SELECT * FROM wp_terms (fetch term names)

With optimizations (1 query):
- SELECT * FROM wp_posts (get 5 movies)
└─ Faster page load!
```

**Rendering Logic:**

```
For each movie:
  1. Get edit link: get_edit_post_link( $post->ID )
  2. Get release date from meta: get_post_meta( $post->ID, 'rt-movie-meta-basic-release-date', true )
  3. Fallback to post creation date if no release date
  4. Output list item with title + date
  5. Title is clickable link to edit page
```

**Example output:**

```html
<ul class="rt-dashboard-widget-list">
  <li><a href="/wp-admin/post.php?post=42&action=edit">The Matrix</a>
    <span class="rt-widget-date">(Release Date: 1999-03-31)</span>
  </li>
  <li><a href="/wp-admin/post.php?post=43&action=edit">Inception</a>
    <span class="rt-widget-date">(Release Date: 2010-07-16)</span>
  </li>
</ul>
```

##### 4. `render_top_rated_movies()` — Widget 2

```php
public function render_top_rated_movies(): void {
    $query = new \WP_Query(
        array(
            'post_type'              => self::POST_TYPE,
            'post_status'            => 'publish',
            'posts_per_page'         => self::WIDGET_POST_LIMIT,
            'meta_key'               => self::META_RATING,
            'orderby'                => array(
                'meta_value_num' => 'DESC',
                'title'          => 'ASC',
            ),
            'meta_query'             => array(
                array(
                    'key'     => self::META_RATING,
                    'value'   => 0,
                    'compare' => '>',
                    'type'    => 'NUMERIC',
                ),
            ),
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'ignore_custom_sort'     => true,
        )
    );
    // ... render similar to recent_movies
}
```

**Query Parameters:**

```php
'meta_key'      => 'rt-movie-meta-basic-rating'
// Tells WP to join wp_postmeta table on this key

'orderby'       => array(
    'meta_value_num' => 'DESC',  // Primary: sort by rating (descending)
    'title'          => 'ASC',   // Secondary: sort by title (alphabetical)
)
// This handles ties (movies with same rating sorted alphabetically)

'meta_query'    => array(
    array(
        'key'     => 'rt-movie-meta-basic-rating',
        'value'   => 0,
        'compare' => '>',           // rating > 0
        'type'    => 'NUMERIC',     // Treat as number (not string)
    ),
)
// Only include movies that HAVE a rating assigned
```

**Why `'type' => 'NUMERIC'`?**

Without this:
```
String comparison: '8.5' > '9.0'? NO (because '8' < '9')
Wrong! It's comparing strings character-by-character.

With NUMERIC:
Numeric comparison: 8.5 > 9.0? NO
Correct!
```

##### 5. `render_upcoming_movies()` — Widget 3 (TMDB API)

This widget fetches data from **external API** rather than local database:

```php
public function render_upcoming_movies(): void {
    $client = new Tmdb_Client();
    
    $movies = $client->get_upcoming_movies();

    if ( is_wp_error( $movies ) ) {
        printf(
            '<p><strong>%s:</strong> %s</p>',
            esc_html__( 'Error', 'rt-movie-library' ),
            esc_html( $movies->get_error_message() )
        );
        return;
    }

    if ( empty( $movies ) ) {
        echo '<p>' . esc_html__( 'No upcoming movies found.', 'rt-movie-library' ) . '</p>';
        return;
    }

    echo '<ul class="rt-dashboard-widget-list">';

    foreach ( $movies as $movie ) {
        printf(
            '<li>%s <span class="rt-widget-date">(%s: %s)</span></li>',
            esc_html( $movie['title'] ?? 'Unknown' ),
            esc_html__( 'Release Date', 'rt-movie-library' ),
            esc_html( $movie['release_date'] ?? 'TBA' )
        );
    }

    echo '</ul>';
}
```

**Key Differences:**

1. **External Data** — Calls Tmdb_Client API wrapper
2. **Caching** — Results cached for 4 hours (transient)
3. **Error Handling** — Gracefully displays API errors
4. **No Edit Links** — These movies don't exist locally yet

### Transient Caching System

The TMDB widget uses **WordPress transients** for caching:

```php
// In Tmdb_Client::get_upcoming_movies()

$cached = get_transient( 'rt_tmdb_upcoming_movies' );

if ( false !== $cached && is_array( $cached ) ) {
    return $cached;  // ← Return cached data if fresh
}

// Fetch from API if no cache
$data = $this->request_tmdb( '/movie/upcoming', [...] );

// Cache for 4 hours
set_transient( 'rt_tmdb_upcoming_movies', $movies, 4 * HOUR_IN_SECONDS );

return $movies;
```

**How transients work internally:**

```
wp_options table:
├── option_name: 'transient_rt_tmdb_upcoming_movies'
├── option_value: serialized array of movies
├── autoload: 'yes'  (auto-loaded on every page)

wp_options table:
├── option_name: 'transient_timeout_rt_tmdb_upcoming_movies'
├── option_value: 1710157200  (Unix timestamp: 4 hours from now)
```

**On each dashboard load:**

```
1. Try get_transient( 'rt_tmdb_upcoming_movies' )
2. WordPress checks: Is timeout value > current time?
3. If yes: Return cached data
4. If no: Delete cache, return false
5. If false, fetch fresh from API
```

**Benefits:**

- **Performance** — No API call on every dashboard load
- **Rate Limiting** — TMDB API has request limits; caching prevents hitting them
- **Reliability** — If API is down, cached data is used
- **User Experience** — Fast page loads

### Performance Considerations

**Widget Query Optimization:**

As you can see, the widget queries use:

```php
'no_found_rows'          => true,    // Saves COUNT(*) query
'update_post_term_cache' => false,   // Saves taxonomy queries
```

But the dashboard could be even faster:

**Option 1: Cache widget HTML**

```php
public function render_recent_movies(): void {
    $cache_key = 'rt_widget_recent_movies_html';
    $cached = get_transient( $cache_key );
    
    if ( false !== $cached ) {
        echo $cached;
        return;
    }
    
    // Generate HTML
    ob_start();
    // ... render
    $html = ob_get_clean();
    
    set_transient( $cache_key, $html, 15 * MINUTE_IN_SECONDS );
    echo $html;
}
```

**Option 2: Use dedicated dashboard query cache**

```php
add_filter( 'posts_clauses', function( $where ) {
    if ( defined( 'REST_REQUEST' ) ) {
        return $where;  // Don't cache REST requests
    }
    
    $cache_key = md5( $where );
    if ( ( $results = get_transient( $cache_key ) ) !== false ) {
        return $results;
    }
    
    set_transient( $cache_key, $where, HOUR_IN_SECONDS );
    return $where;
});
```

### Security Considerations

**1. Widget XSS Protection**

```php
esc_html( $post->post_title )      // ← Escapes HTML entities
esc_url( $edit_link )              // ← Escapes URLs
```

Without this:

```
If movie title is: <script>alert('hacked')</script>
WordPress would render: <script>alert('hacked')</script>
Browser executes the script!

With esc_html():
WordPress renders: &lt;script&gt;alert(&#039;hacked&#039;)&lt;/script&gt;
Browser displays as text, not code
```

**2. Capability Checks**

```php
if ( ! current_user_can( 'edit_rt-movies' ) ) {
    return;  // Only admins/movie-managers see widgets
}
```

**3. API Error Handling**

```php
if ( is_wp_error( $movies ) ) {
    printf( '<p><strong>%s:</strong> %s</p>',
        esc_html__( 'Error', 'rt-movie-library' ),
        esc_html( $movies->get_error_message() )
    );
    return;
}
```

Errors are displayed safely, not causing fatal crashes.

---

## TMDB API Integration

### The Problem: Sync Movie Metadata

When a movie is added to WordPress:
- User enters title manually
- User enters description
- But movie poster, rating, release date—these come from TMDB

**Solution:** Automatically fetch and sync data from TMDB API.

### TMDB API Overview

**The Movie Database (TMDB)** is a free API that provides:
- Movie metadata (title, plot, runtime, budget)
- Cast & crew information
- Ratings (vote_average)
- Release dates
- Movie posters
- Upcoming releases

**API Endpoint:** `https://api.themoviedb.org/3/`

**Authentication:** Requires API key (free registration)

### Class: Tmdb_Client

**Location:** [includes/classes/tmdb/class-tmdb-client.php](includes/classes/tmdb/class-tmdb-client.php)

**Purpose:** HTTP client wrapper around TMDB API

#### Key Methods

##### 1. `get_upcoming_movies()` — Fetch Upcoming Releases

```php
public function get_upcoming_movies(): array|\WP_Error {
    $cached = get_transient( self::TRANSIENT_UPCOMING );

    if ( false !== $cached && is_array( $cached ) ) {
        return $cached;
    }

    $api_key = $this->get_api_key();

    if ( is_wp_error( $api_key ) ) {
        return $api_key;
    }

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

    if ( ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
        return new \WP_Error(
            'rt_tmdb_invalid_response',
            esc_html__( 'TMDB returned an unrecognised response format.', 'rt-movie-library' )
        );
    }

    $movies = $this->parse_upcoming_results( $data['results'] );

    set_transient( self::TRANSIENT_UPCOMING, $movies, self::TRANSIENT_TTL );

    return $movies;
}
```

**Execution flow:**

```
1. Check if cached (4-hour cache)
   └─ If yes, return cached data immediately

2. Get API key from plugin settings
   └─ If missing, return WP_Error

3. Make HTTP request to TMDB
   Request: /movie/upcoming?language=en-US&page=1&api_key=XXX
   └─ If error, return WP_Error

4. Validate response structure
   └─ Check for 'results' key
   └─ Check it's an array
   └─ If not, return WP_Error

5. Parse results
   └─ Filter to only relevant fields
   └─ Format dates

6. Cache results (4 hours)
   └─ Next request returns from cache

7. Return to caller
```

##### 2. `search_movie()` — Find Movie by Title

```php
public function search_movie( string $title ): array|\WP_Error {
    $api_key = $this->get_api_key();

    if ( is_wp_error( $api_key ) ) {
        return $api_key;
    }

    $search_title = sanitize_text_field( $title );

    if ( '' === $search_title ) {
        return new \WP_Error(
            'rt_tmdb_invalid_title',
            esc_html__( 'Movie title is empty.', 'rt-movie-library' )
        );
    }

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

    if ( ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
        return new \WP_Error(
            'rt_tmdb_invalid_response',
            esc_html__( 'TMDB returned an unrecognised response format.', 'rt-movie-library' )
        );
    }

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

        if ( $candidate === $normalized_query ) {
            return $this->sanitize_movie_payload( $movie );
        }
    }

    return new \WP_Error(
        'rt_tmdb_not_found',
        esc_html__( 'Movie not found on TMDB.', 'rt-movie-library' )
    );
}
```

**Why case-insensitive matching?**

```
User enters: "THE MATRIX"
TMDB has: "The Matrix"

Without normalization:
"THE MATRIX" === "The Matrix" → false (mismatch!)

With normalization:
strtolower("THE MATRIX") === strtolower("The Matrix")
"the matrix" === "the matrix" → true (match!)
```

**TMDB Search Behavior:**

```
Request: /search/movie?query=the+matrix
Response:
{
  "results": [
    {"title": "The Matrix", "vote_average": 8.2, ... },
    {"title": "The Matrix Reloaded", "vote_average": 7.2, ... },
    {"title": "The Matrix Revolutions", "vote_average": 6.8, ... }
  ]
}
```

The API returns partial matches, so we loop until we find an exact title match.

##### 3. `request_tmdb()` — Make API Call

```php
private function request_tmdb( string $endpoint, array $params, string $api_key ): array|\WP_Error {
    $url = self::BASE_URL . $endpoint;
    
    $params['api_key'] = $api_key;
    
    $response = wp_remote_get(
        add_query_arg( $params, $url ),
        array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status_code = intval( wp_remote_retrieve_response_code( $response ) );

    if ( $status_code !== 200 ) {
        return new \WP_Error(
            'rt_tmdb_http_error',
            sprintf(
                esc_html__( 'TMDB API returned HTTP %d', 'rt-movie-library' ),
                $status_code
            )
        );
    }

    $body = wp_remote_retrieve_body( $response );
    
    $data = json_decode( $body, true );

    if ( ! is_array( $data ) ) {
        return new \WP_Error(
            'rt_tmdb_json_parse_error',
            esc_html__( 'Failed to parse TMDB response.', 'rt-movie-library' )
        );
    }

    return $data;
}
```

**WordPress HTTP API Functions:**

```php
wp_remote_get()                      // Make HTTP GET request
wp_remote_retrieve_response_code()   // Get HTTP status (200, 404, 500, etc)
wp_remote_retrieve_body()            // Get response body
```

**Why use WordPress API instead of cURL?**

- **HTTP compatibility** — Works with streams, cURL, or WordPress fallback
- **Timeout protection** — Prevents hanging requests
- **Error handling** — Returns WP_Error for consistency
- **Hooks** — Allows plugins to intercept/modify requests

**Request lifecycle:**

```
wp_remote_get( 'https://api.themoviedb.org/3/search/movie?query=...' )
        ↓
Does WordPress have cURL? YES
        ↓
Use cURL to make request
        ↓
Store response in array
[
    'response' => ['code' => 200, 'message' => 'OK'],
    'body' => '{"results": [...]}',
    'headers' => [...],
    'cookies' => [...]
]
        ↓
Return to caller
```

### Class: Tmdb_Sync

**Location:** [includes/classes/tmdb/class-tmdb-sync.php](includes/classes/tmdb/class-tmdb-sync.php)

**Purpose:** WordPress Cron handler that syncs movie metadata periodically

#### Key Methods

##### 1. `register_cron_interval()` — Custom Interval

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

**Why custom interval?**

WordPress provides these built-in:
- hourly (3600 seconds)
- twicedaily (43200 seconds)
- daily (86400 seconds)

But we need 30-minute interval, so we create custom one.

**How filters work:**

```php
add_filter( 'cron_schedules', array( $this, 'register_cron_interval' ) );

// WordPress calls this filter:
$schedules = apply_filters( 'cron_schedules', array(...) );

// Our callback adds:
$schedules['rt_every_30_min'] = array(
    'interval' => 1800,
    'display' => 'Every 30 Minutes',
)

// Returns to WordPress
```

##### 2. `schedule()` — Register Cron Job

```php
public static function schedule(): void {
    self::get_instance();

    if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
        wp_schedule_event( time(), self::CRON_INTERVAL, self::CRON_HOOK );
    }
}
```

**What happens internally:**

```php
wp_schedule_event( time(), 'rt_every_30_min', 'rt_tmdb_sync' );

// WordPress stores in options table:
wp_options:
├── option_name: 'cron'
├── option_value: serialized array
│   [
│       1234567890 => [
│           'rt_tmdb_sync' => [
│               'unique_event_id' => [
│                   'schedule' => 'rt_every_30_min',
│                   'args' => []
│               ]
│           ]
│       ]
│   ]
```

**Check if already scheduled:**

```php
if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
    // Not scheduled yet, schedule it
}
```

This prevents duplicate cron jobs (scheduling twice).

##### 3. `run_sync()` — Main Cron Callback

```php
public function run_sync(): void {
    try {
        $limit = absint( get_option( Settings::OPTION_MOVIE_LIMIT, 5 ) );
        if ( $limit < 1 ) {
            $limit = 5;
        }

        $client = new Tmdb_Client();
        $movies = $this->get_movies_to_sync( $limit );

        if ( empty( $movies ) ) {
            return;
        }

        foreach ( $movies as $post ) {
            $this->sync_single_movie( $post, $client );
        }
    } catch ( \Throwable $e ) {
        update_option(
            'rt_tmdb_last_sync_error',
            array(
                'message' => sanitize_text_field( $e->getMessage() ),
                'time'    => time(),
            ),
            false
        );

        do_action( 'rt_movie_library_tmdb_sync_error', $e );

        return;
    }
}
```

**Execution flow:**

```
1. Get sync limit from settings (default: 5 movies)
2. Create TMDB client
3. Get unsynced movies (ordered by sync time)
4. For each movie:
   a. Call TMDB API
   b. Extract rating, release date, poster
   c. Update post meta
   d. Download and attach poster
5. If error occurs:
   a. Store error message in options
   b. Fire error action hook
   c. Return (don't crash)
```

**Error Handling:**

```php
try {
    // ... sync code
} catch ( \Throwable $e ) {
    // Store error
    update_option( 'rt_tmdb_last_sync_error', [...] );
    
    // Allow other plugins to handle error
    do_action( 'rt_movie_library_tmdb_sync_error', $e );
    
    return;
}
```

Other plugins can hook into the error:

```php
add_action( 'rt_movie_library_tmdb_sync_error', function( $error ) {
    error_log( 'TMDB Sync Error: ' . $error->getMessage() );
    wp_mail( 'admin@example.com', 'TMDB Sync Failed', ... );
});
```

##### 4. `get_movies_to_sync()` — Find Unsynced Movies

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

**Query Logic:**

```
Meta key: _mw_tmdb_synced_at
Stores: Unix timestamp of last sync

Query:
┌─────────────────────────────────────────┐
│ Find movies WHERE:                      │
│                                         │
│ (_mw_tmdb_synced_at NOT EXISTS)         │
│    OR                                   │
│ (_mw_tmdb_synced_at EXISTS)             │
│                                         │
│ This matches ALL movies (both synced   │
│ and unsynced), but with ordering:      │
│                                         │
│ PRIMARY: Order by meta_value_num ASC   │
│   → Unsynced (NULL) come first        │
│   → Then synced (by timestamp)        │
│                                         │
│ SECONDARY: Order by ID ASC             │
│   → If same timestamp, sort by ID     │
└─────────────────────────────────────────┘
```

**Why this query?**

```
Desired behavior:
1. Sync all NEW movies first (no _mw_tmdb_synced_at)
2. Then sync OLDEST movies (lowest timestamp)
3. Limit to 5 movies per run

With this query:
- First 5 results are: unsynced + oldest synced
- Prevents starving old movies of updates
- Ensures new movies get priority
```

**Potential improvement:**

```php
// Separate unsynced from synced
'meta_query' => array(
    'relation' => 'OR',
    array(
        'key' => self::META_SYNCED_AT,
        'compare' => 'NOT EXISTS'
    ),
    array(
        'key' => self::META_SYNCED_AT,
        'compare' => 'EXISTS'
    )
)

// Could optimize to:
'meta_query' => array(
    array(
        'key' => self::META_SYNCED_AT,
        'compare' => 'NOT EXISTS'
    )
)

// Or:
'meta_query' => array(
    array(
        'key' => self::META_SYNCED_AT,
        'compare' => 'EXISTS'
    )
)
// Run separately
```

##### 5. `sync_single_movie()` — Sync One Movie

```php
private function sync_single_movie( \WP_Post $post, Tmdb_Client $client ): void {
    $tmdb_data = $client->search_movie( $post->post_title );

    if ( is_wp_error( $tmdb_data ) ) {
        update_post_meta( $post->ID, self::META_SYNCED_AT, time() );
        return;
    }

    $new_rating      = (float) ( $tmdb_data['vote_average'] ?? 0 );
    $existing_rating = (float) get_post_meta( $post->ID, 'rt-movie-meta-basic-rating', true );

    if ( $existing_rating !== $new_rating ) {
        update_post_meta( $post->ID, 'rt-movie-meta-basic-rating', $new_rating );
    }

    $new_date      = trim( (string) ( $tmdb_data['release_date'] ?? '' ) );
    $existing_date = trim( (string) get_post_meta( $post->ID, 'rt-movie-meta-basic-release-date', true ) );

    if ( '' !== $new_date && $existing_date !== $new_date ) {
        update_post_meta( $post->ID, 'rt-movie-meta-basic-release-date', $new_date );
    }

    $new_poster_path = trim( (string) ( $tmdb_data['poster_path'] ?? '' ) );
    $this->sync_movie_poster( $post->ID, $new_poster_path );

    update_post_meta( $post->ID, self::META_SYNCED_AT, time() );
}
```

**Step-by-step:**

```
1. Search TMDB for movie by title
   $tmdb_data = $client->search_movie( 'The Matrix' )
   
   If error:
   - Mark as synced (so we don't retry infinitely)
   - Return

2. Update rating meta
   - Get TMDB rating
   - Get existing rating from post meta
   - Only update if changed (avoid unnecessary DB writes)

3. Update release date meta
   - Get TMDB release date
   - Get existing release date
   - Only update if changed AND not empty

4. Sync poster
   - Get TMDB poster path
   - Download image
   - Attach to post as featured image

5. Mark as synced
   - Store current timestamp
   - Next cron run will prioritize other movies
```

##### 6. `sync_movie_poster()` — Download & Attach Poster

```php
private function sync_movie_poster( int $post_id, string $poster_path ): void {
    if ( '' === $poster_path ) {
        return;
    }

    $existing_path          = trim( (string) get_post_meta( $post_id, self::META_POSTER_PATH, true ) );
    $existing_attachment_id = absint( get_post_meta( $post_id, self::META_POSTER_ATTACHMENT_ID, true ) );

    // Skip remote download when poster path is already synced.
    if ( $poster_path === $existing_path ) {
        if ( has_post_thumbnail( $post_id ) ) {
            return;
        }

        if ( $existing_attachment_id > 0 && get_post( $existing_attachment_id ) instanceof \WP_Post ) {
            set_post_thumbnail( $post_id, $existing_attachment_id );
            return;
        }
    }

    // ... [rest of method: download and attach poster]
}
```

**Optimization logic:**

```
if poster_path === existing_path (same poster):
    └─ Don't download again
    └─ Check if already attached
    └─ If yes: return
    └─ If no: reattach existing attachment

if poster_path !== existing_path (new poster):
    └─ Download new poster from TMDB
    └─ Create attachment
    └─ Attach to post
    └─ Store new poster_path and attachment_id
```

**Why store poster path?**

```
Without storing poster_path:
- Every sync would download the poster again (wasted bandwidth)
- Every sync would create new attachment (wasted storage)

With storing poster_path:
- Only download when poster changes
- Reuse existing attachment
- Efficient!
```

### TMDB Integration Flow Diagram

```
WordPress Cron Fires (Every 30 min)
        ↓
rt_tmdb_sync hook triggered
        ↓
Tmdb_Sync::run_sync() called
        ↓
┌─────────────────────────────────────────┐
│ Get unsynced movies (WP_Query)          │
│ ORDER BY _mw_tmdb_synced_at ASC         │
│ LIMIT 5                                 │
└─────────────────────────────────────────┘
        ↓
For each movie:
        ↓
┌─────────────────────────────────────────┐
│ 1. Call Tmdb_Client::search_movie()    │
│    Request: /search/movie?query=...     │
│    Response: {results: [...]}           │
│                                         │
│ 2. Find exact title match               │
│    return movie data with:              │
│    - vote_average (rating)              │
│    - release_date                       │
│    - poster_path                        │
│                                         │
│ 3. Update post meta:                    │
│    - rt-movie-meta-basic-rating         │
│    - rt-movie-meta-basic-release-date   │
│                                         │
│ 4. Download poster:                     │
│    - Check if already downloaded        │
│    - If not: fetch from TMDB CDN        │
│    - Create attachment                  │
│    - Attach to post (featured image)    │
│                                         │
│ 5. Mark synced:                         │
│    - _mw_tmdb_synced_at = current time │
└─────────────────────────────────────────┘
        ↓
Next cron run (30 min later) syncs different movies
```

### Error Handling & Edge Cases

**Case 1: Movie not found on TMDB**

```php
$tmdb_data = $client->search_movie( 'Obscure Movie Title' );

if ( is_wp_error( $tmdb_data ) ) {
    // Mark as synced, so we don't keep searching
    update_post_meta( $post->ID, self::META_SYNCED_AT, time() );
    return;
}
```

**Case 2: TMDB API is down**

```php
$response = wp_remote_get( ... );

if ( is_wp_error( $response ) ) {
    // Network error, timeout, or API down
    // Return WP_Error
    return $response;
}
```

Cron catches this and logs error:

```php
try {
    // sync code
} catch ( \Throwable $e ) {
    update_option( 'rt_tmdb_last_sync_error', [...] );
    do_action( 'rt_movie_library_tmdb_sync_error', $e );
}
```

**Case 3: Movie has no poster on TMDB**

```php
$new_poster_path = trim( (string) ( $tmdb_data['poster_path'] ?? '' ) );

if ( '' === $poster_path ) {
    return;  // Don't try to download empty path
}
```

**Case 4: Poster image is corrupt**

```php
// download_file_from_tmdb() would catch this
// and return WP_Error

if ( is_wp_error( $attachment ) ) {
    // Don't crash, just skip poster
    // Movie data (rating, date) still updated
    return;
}
```

### Performance Considerations

**1. Rate Limiting**

TMDB API has rate limits (typically 40 requests per 10 seconds).

Plugin handles this by:

```php
// Only sync 5 movies per run
$limit = absint( get_option( Settings::OPTION_MOVIE_LIMIT, 5 ) );

// Run every 30 minutes
// So max 5 API calls per 30 min = within limits
```

Admin can adjust limit in settings.

**2. Caching**

```php
// Upcoming movies cached for 4 hours
set_transient( 'rt_tmdb_upcoming_movies', $movies, 4 * HOUR_IN_SECONDS );

// Dashboard widget uses cached data
// Prevents 5+ API calls per dashboard load
```

**3. Database Optimization**

Only update meta if value changed:

```php
if ( $existing_rating !== $new_rating ) {
    update_post_meta( $post->ID, 'rt-movie-meta-basic-rating', $new_rating );
}
```

This avoids unnecessary database writes.

### Security Considerations

**1. API Key Security**

```php
$api_key = $this->get_api_key();

// Should be stored as plugin option with:
// - Only admins can view
// - Not logged in debug.log
// - Not exposed in REST API
```

**2. SSRF Prevention (Server-Side Request Forgery)**

The plugin only requests from `api.themoviedb.org` and `image.tmdb.org`, so SSRF is not a concern.

However, if it accepted custom URLs:

```php
// UNSAFE:
$data = wp_remote_get( $_REQUEST['url'] );

// SAFE:
$data = wp_remote_get( 'https://api.themoviedb.org/3/...' );
```

**3. Poster Download Security**

```php
$poster_url = 'https://image.tmdb.org/t/p/w500' . $poster_path;

// Only download from trusted TMDB CDN
// Not from user-provided URLs
// Path is sanitized from TMDB API response
```

---

## WordPress Cron Job System

### What is WP-Cron?

WordPress doesn't use system cron. Instead, it uses **pseudo-cron**:

```
User visits website
        ↓
WordPress loads wp-cron.php
        ↓
Check: Are there scheduled events due?
        ↓
If yes: Execute them
        ↓
If no: Continue normally
```

**Problem:** Only runs when site is visited.

```
If no visitors for 30 minutes:
- Scheduled cron doesn't run
- Sync job is delayed
```

**Solution:** Set up real cron job to disable WP-Cron:

```bash
# In wp-config.php
define( 'DISABLE_WP_CRON', true );

# In system crontab
* * * * * curl https://example.com/wp-cron.php?doing_wp_cron=1 > /dev/null 2>&1
```

This ensures cron runs every minute, executing due events immediately.

### Storage: How Cron Is Stored

Cron events are stored in `wp_options` table:

```php
wp_options:
├── option_name: 'cron'
├── option_value: serialized array
│   [
│       [timestamp] => [
│           '[hook_name]' => [
│               '[unique_id]' => [
│                   'schedule' => '[schedule_name]',
│                   'args' => [args array]
│               ]
│           ]
│       ]
│   ]
│
│ Example for rt_tmdb_sync:
│ [
│     1234567890 => [
│         'rt_tmdb_sync' => [
│             'unique_id_xyz' => [
│                 'schedule' => 'rt_every_30_min',
│                 'args' => []
│             ]
│         ]
│     ]
│ ]
```

**Cron Schedules** are stored in `cron_schedules` filter:

```php
// In Tmdb_Sync::register_cron_interval()
add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['rt_every_30_min'] = array(
        'interval' => 1800,  // 30 * 60 seconds
        'display' => 'Every 30 Minutes'
    );
    return $schedules;
});
```

### Cron Lifecycle in RT Movie Library

**Activation:**

```php
// In Activator::activate()
Tmdb_Sync::schedule();

// Which calls:
public static function schedule(): void {
    self::get_instance();
    
    if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
        wp_schedule_event( time(), self::CRON_INTERVAL, self::CRON_HOOK );
    }
}
```

**What happens:**

1. `wp_next_scheduled()` checks if 'rt_tmdb_sync' is already scheduled
2. If not found, `wp_schedule_event()` adds it to cron
3. Next run: 30 minutes from now

**Execution:**

```php
// In plugin file
add_action( self::CRON_HOOK, array( $this, 'run_sync' ) );

// When cron fires:
// WordPress checks due events
// Finds 'rt_tmdb_sync' due
// Calls do_action( 'rt_tmdb_sync' )
// Tmdb_Sync::run_sync() executes
```

**Deactivation:**

```php
// In Deactivator::deactivate()
Tmdb_Sync::unschedule();

// Which calls:
public static function unschedule(): void {
    wp_clear_scheduled_hook( self::CRON_HOOK );
}
```

**What happens:**

1. `wp_clear_scheduled_hook()` removes all 'rt_tmdb_sync' events
2. Plugin can be reactivated without orphaned cron jobs

### Common Cron Issues & Solutions

**Issue 1: Cron Never Runs**

```
Symptom: run_sync() never called, TMDB data not syncing
Cause: Site has no traffic, WP-Cron disabled
Solution: Enable real cron (system crontab)
```

**Issue 2: Cron Runs Too Frequently**

```
Symptom: Server CPU high, API rate limited
Cause: Real cron configured to run every minute
Solution: Reduce cron frequency in wp-cron.php
```

**Issue 3: Orphaned Cron Jobs**

```
Symptom: Plugin deactivated but cron still running
Cause: wp_clear_scheduled_hook() not called
Solution: Always call in deactivation hook
```

**Debugging:**

```php
// Check if cron is scheduled
if ( wp_next_scheduled( 'rt_tmdb_sync' ) ) {
    echo 'Cron is scheduled';
} else {
    echo 'Cron is NOT scheduled';
}

// Manually trigger for testing
do_action( 'rt_tmdb_sync' );

// Check last error
$error = get_option( 'rt_tmdb_last_sync_error' );
print_r( $error );
```

---

## Custom Post Types & Taxonomies

### Custom Post Types

The plugin registers **two custom post types:**

**1. rt-movie** — Movie listings
- Features: title, content, excerpt, thumbnail, revisions, author
- Taxonomies: Genre, Label, Language, Production Company, Tag, Movie-Person
- REST support: YES
- Archive page: YES
- Permalink structure: `/movie/{genre}/{slug}-{id}/`

**2. rt-person** — Actor/crew information
- Features: title, content, excerpt, thumbnail, revisions, author
- Taxonomies: Career, Movie-Person
- REST support: YES
- Archive page: YES
- Permalink structure: `/person/{career}/{slug}-{id}/`

### Taxonomies

**Public Taxonomies (visible in UI):**

1. **rt-movie-genre** — Movie categories (Action, Drama, Comedy, etc.)
2. **rt-movie-label** — Movie rating/label (PG, PG-13, R, etc.)
3. **rt-movie-language** — Language spoken (English, Spanish, etc.)
4. **rt-movie-production-company** — Movie studios (Warner Bros, etc.)
5. **rt-movie-tag** — Keywords (IMAX, 3D, etc.)
6. **rt-person-career** — Person's profession (Actor, Director, Producer, etc.)

**Private/Shadow Taxonomy:**

7. **rt-movie-person** — Relationships between movies and people (hidden from UI, used internally)

### Meta Boxes

Custom meta boxes for editing:
- Movie basic info (rating, release date, budget, runtime)
- Movie crew (cast, director, producer assignments)
- Person basic info (birth date, biography, social links)
- Media (image gallery, video embeds)
- Movie poster

### Shortcodes

Two front-end shortcodes:

```php
[rt_movie id="42"]
// Displays: Movie title, poster, synopsis, rating, etc.

[rt_person id="99"]
// Displays: Person name, bio, career, social links, etc.
```

---

## REST API Endpoints

The plugin provides **REST endpoints** for programmatic access:

**Movie Endpoints:**

```
GET    /wp-json/rt-movie-library/v1/movies
POST   /wp-json/rt-movie-library/v1/movies
GET    /wp-json/rt-movie-library/v1/movies/{id}
PUT    /wp-json/rt-movie-library/v1/movies/{id}
DELETE /wp-json/rt-movie-library/v1/movies/{id}
```

**Person Endpoints:**

```
GET    /wp-json/rt-movie-library/v1/persons
POST   /wp-json/rt-movie-library/v1/persons
GET    /wp-json/rt-movie-library/v1/persons/{id}
PUT    /wp-json/rt-movie-library/v1/persons/{id}
DELETE /wp-json/rt-movie-library/v1/persons/{id}
```

**Special Handlers:**

- `/wp-json/rt-movie-library/v1/movies/{id}/crew` — Manage cast/crew
- `/wp-json/rt-movie-library/v1/movies/{id}/merge` — Merge two movies
- `/wp-json/rt-movie-library/v1/movies/{id}/duplicate` — Clone a movie

All endpoints require authentication and proper capabilities.

---

## Full Plugin Execution Lifecycle

### Complete Flow From Activation to Cron Sync

```
┌─────────────────────────────────────────────────────────┐
│ ACTIVATION PHASE                                        │
└─────────────────────────────────────────────────────────┘

1. User clicks "Activate Plugin"
        ↓
2. WordPress fires register_activation_hook()
        ↓
3. Activator::activate() called
        ↓
   ┌─────────────────────────────────────┐
   │ a. Plugin::get_instance()->register()│
   │    - Movie CPT registered           │
   │    - Person CPT registered          │
   │    - 7 taxonomies registered        │
   │    - Meta boxes loaded              │
   │    - REST endpoints registered      │
   │    - Dashboard widgets loaded       │
   │                                     │
   │ b. Movie_Manager_Role::activate()   │
   │    - 'movie-manager' role created   │
   │    - 40+ capabilities granted       │
   │    - Admin role synced              │
   │                                     │
   │ c. Rewrite_Rules::flush_on_activate()
   │    - Rewrite tags registered        │
   │    - Rewrite rules registered       │
   │    - .htaccess flushed              │
   │                                     │
   │ d. Tmdb_Sync::schedule()            │
   │    - Cron event scheduled           │
   │    - First run: 30 min from now     │
   └─────────────────────────────────────┘
        ↓
4. Plugin is ACTIVE
        ↓

┌─────────────────────────────────────────────────────────┐
│ POST-ACTIVATION: USER ACTIONS                           │
└─────────────────────────────────────────────────────────┘

5. Admin adds movie:
   - POST /wp-admin/post.php (create)
   - WordPress creates post in wp_posts
   - Saves post meta (rating, release date, etc.)
   - Assigns taxonomies (genre, label, etc.)
        ↓
6. Permalink generated when displaying post:
   - get_permalink( $post_id )
   - Fires post_type_link filter
   - Rewrite_Rules::filter_post_type_link() called
   - URL generated: /movie/action/the-matrix-42/
        ↓
7. User visits movie page:
   - URL: https://example.com/movie/action/the-matrix-42/
   - Apache/Nginx matches .htaccess rewrite rule
   - Query: /index.php?post_type=rt-movie&mw_genre=action&name=the-matrix&p=42
   - WordPress loads post #42
   - Template renders
        ↓

┌─────────────────────────────────────────────────────────┐
│ CRON PHASE: Background Sync                            │
└─────────────────────────────────────────────────────────┘

8. 30 minutes after activation, cron fires:
   - WordPress checks due events
   - Finds 'rt_tmdb_sync' event due
   - Calls do_action( 'rt_tmdb_sync' )
        ↓
9. Tmdb_Sync::run_sync() executes:
   - Get unsynced movies (WP_Query)
   - Create Tmdb_Client
   - For each of 5 movies:
     a. Search TMDB by title
     b. Get rating, release date, poster
     c. Update post meta
     d. Download poster
     e. Mark synced
   - If error: log and notify
        ↓
10. Movie metadata updated:
    - Rating in post meta
    - Release date in post meta
    - Poster attached as featured image
        ↓
11. Dashboard widgets display updated data:
    - Widget 1: Recent movies (from DB)
    - Widget 2: Top rated movies (from DB with ratings)
    - Widget 3: Upcoming movies (from TMDB, cached)
        ↓
12. Cron reschedules automatically:
    - Next run: 30 minutes later
    - Syncs different movies
        ↓

┌─────────────────────────────────────────────────────────┐
│ DEACTIVATION PHASE                                      │
└─────────────────────────────────────────────────────────┘

13. User clicks "Deactivate Plugin"
        ↓
14. WordPress fires register_deactivation_hook()
        ↓
15. Deactivator::deactivate() called
        ↓
    ┌──────────────────────────────────┐
    │ a. Movie_Manager_Role::deactivate()
    │    - 'movie-manager' role removed │
    │    - Capabilities revoked         │
    │    - Users reassigned to default  │
    │                                  │
    │ b. Rewrite_Rules::flush_on_deactivate()
    │    - .htaccess rewritten         │
    │    - Rewrite rules removed       │
    │                                  │
    │ c. Tmdb_Sync::unschedule()       │
    │    - Cron events cleared         │
    │    - No orphaned jobs            │
    └──────────────────────────────────┘
        ↓
16. Plugin is INACTIVE
        ↓
17. Data persists:
    - Movies still in wp_posts
    - Taxonomies still assigned
    - Meta still in wp_postmeta
    - But UI unavailable (CPT hidden)
        ↓
18. User can reactivate:
    - All data restored
    - Cron rescheduled
    - Back to normal operation
```

---

## Key Design Patterns & Architecture Decisions

### 1. Singleton Pattern

**Used for:** All core classes

```php
use Singleton;

class Plugin {
    use Singleton;
    
    protected function __construct() {
        // Initialization
    }
}

// Usage
$plugin = Plugin::get_instance();  // Always same instance
```

**Why?**

- Ensures single responsibility
- Prevents duplicate hook registration
- Memory efficient
- Lazy initialization

### 2. Trait-Based Architecture

**Singleton Trait:**

```php
trait Singleton {
    private static $instance;
    
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new static();
        }
        return self::$instance;
    }
    
    protected function __construct() {}
}
```

**Benefits:**

- DRY (Don't Repeat Yourself)
- Consistent singleton behavior
- Easy to apply to multiple classes

### 3. Hook-Driven Architecture

All functionality hooked to WordPress actions/filters:

```php
// Hooks never hardcoded to run
// All through WordPress action/filter system

add_action( 'init', [ $class, 'method' ] );
add_action( 'wp_dashboard_setup', [ $class, 'method' ] );
add_filter( 'post_type_link', [ $class, 'method' ] );
```

**Benefits:**

- Other plugins can unhook/extend
- Follows WordPress conventions
- Easy debugging (can see all hooks)

### 4. WP_Query Optimization

```php
'no_found_rows'          => true,       // Skip COUNT(*)
'update_post_term_cache' => false,      // Skip taxonomy fetch
'fields'                 => 'ids',      // Only return IDs if not needed
```

**Result:** 1 query instead of 5+

### 5. Error Handling with WP_Error

```php
$result = $client->search_movie( $title );

if ( is_wp_error( $result ) ) {
    return $result;  // Propagate error
}
```

**Benefits:**

- Consistent error format
- Error messages preserved
- Prevents silent failures

### 6. Transient Caching

```php
// Check cache first
$cached = get_transient( $key );
if ( false !== $cached ) {
    return $cached;
}

// ... fetch fresh data ...

// Cache result
set_transient( $key, $data, 4 * HOUR_IN_SECONDS );
```

**Benefits:**

- Reduces API calls
- Reduces database queries
- Configurable TTL

### 7. Metadata Organization

```php
// Meta keys follow pattern:
'rt-movie-meta-basic-rating'       // Post meta
'rt-movie-meta-basic-release-date' // Post meta
'_mw_tmdb_synced_at'               // Internal (private)
'_mw_tmdb_poster_path'             // Internal (private)

// Naming convention:
// _prefix_ = private (internal use only)
// no prefix = public/editable
```

---

## Common Interview Questions

### Q1: How do custom rewrite rules work?

**Answer:**

```
Rewrite rules are regex patterns stored in wp_options table.
When flushed, they're written to .htaccess.

User requests /movie/action/the-matrix-42/:
1. Apache matches against .htaccess patterns
2. Extracts capture groups (action, the-matrix, 42)
3. Rewrites to: /index.php?post_type=rt-movie&mw_genre=action&name=the-matrix&p=42
4. WordPress loads post #42 with those query vars
5. Template renders the movie

The plugin uses custom tags:
- add_rewrite_tag( '%mw_genre%', '([^/]+)' )
  Tells WordPress: expect mw_genre query var with slug pattern

- add_rewrite_rule( '^movie/([^/]+)/...', 'index.php?...$matches[1]...' )
  Defines the URL pattern and maps capture groups to query vars
```

### Q2: Why are there SO MANY capabilities?

**Answer:**

```
WordPress automatically generates capability combinations:

edit_rt-movie          = Edit own posts
edit_rt-movies         = Edit all posts
edit_others_rt-movies  = Edit others' posts
publish_rt-movies      = Publish posts
delete_rt-movie        = Delete own
delete_rt-movies       = Delete all
delete_others_rt-movies
delete_private_rt-movies
delete_published_rt-movies
... and 30+ more

This granularity allows:
- Movie manager can publish movies
- But CAN'T delete others' movies
- But CAN edit only their own

Each combination represents a distinct permission level.
```

### Q3: How does the cron sync work if user never visits site?

**Answer:**

```
WP-Cron is pseudo-cron (only runs on page load).

Solution: Set up real cron:

In wp-config.php:
define( 'DISABLE_WP_CRON', true );

In system crontab:
* * * * * curl https://example.com/wp-cron.php?doing_wp_cron=1

This runs every minute, executing due events immediately.

Without this, sync only runs when someone visits the site.
```

### Q4: What happens if TMDB API is down?

**Answer:**

```
Tmdb_Sync::run_sync() catches exceptions:

try {
    $data = $client->search_movie( $title );
} catch ( \Throwable $e ) {
    // 1. Store error message in options
    update_option( 'rt_tmdb_last_sync_error', [...] );
    
    // 2. Fire action hook so plugins can respond
    do_action( 'rt_movie_library_tmdb_sync_error', $e );
    
    // 3. Return (don't crash)
    return;
}

If individual API call fails:
- Mark movie as synced (don't retry infinitely)
- Continue with next movie
- Admin can check 'rt_tmdb_last_sync_error' option to see issues
```

### Q5: How does dashboard widget avoid slow queries?

**Answer:**

```
The queries use optimizations:

'no_found_rows'          => true   // Don't count total rows
'update_post_term_cache' => false  // Don't fetch taxonomies

Without these:
- Query 1: SELECT * FROM wp_posts
- Query 2: SELECT COUNT(*) FROM wp_posts
- Query 3: SELECT * FROM wp_term_relationships (for genres)
- Query 4: SELECT * FROM wp_term_taxonomy
- Query 5: SELECT * FROM wp_terms

With optimizations:
- Query 1: SELECT * FROM wp_posts

This runs on every page load (slow = noticeable impact)!

Further optimization could:
- Cache widget HTML for 1 hour
- Use object cache (Redis/Memcached)
- Show cached "as of" timestamp
```

### Q6: Why use post IDs in URLs instead of just slugs?

**Answer:**

```
URL: /movie/action/the-matrix-42/

Why include post ID (42)?

1. Uniqueness:
   - Multiple movies could have same name
   - ID guarantees uniqueness
   - /movie/action/the-matrix-42 != /movie/action/the-matrix-99

2. Performance:
   - WordPress can query by ID directly
   - Doesn't have to join with wp_postmeta

3. SEO:
   - Genre in URL = contextual relevance
   - ID ensures no conflicts
   - Clean, readable URL

4. Slug changes:
   - If movie renamed, post_name changes
   - URL would break (404)
   - But with ID: still works
```

### Q7: How does the plugin prevent movie managers from editing others' movies?

**Answer:**

```
Capability system:

When you call:
current_user_can( 'edit_others_rt-movies' )

Movie managers DON'T have this capability.
Admins DO.

So:

Movie Manager:
- current_user_can( 'edit_rt-movies' ) = YES (can edit all)
- current_user_can( 'delete_rt-movies' ) = NO (can't delete)
- current_user_can( 'edit_others_rt-movies' ) = NO (can't edit others)

Admin:
- current_user_can( 'edit_rt-movies' ) = YES
- current_user_can( 'delete_rt-movies' ) = YES
- current_user_can( 'edit_others_rt-movies' ) = YES

Plugin checks these in REST endpoints/admin to prevent unauthorized actions.
```

---

## Performance & Security Considerations

### Performance Optimizations

**1. Database Query Optimization**

```php
// ✓ Good
'no_found_rows'          => true,
'update_post_term_cache' => false,
'posts_per_page'         => 5,

// ✗ Bad (default)
'posts_per_page'         => -1,  // Fetch ALL posts
'update_post_term_cache' => true, // Fetch taxonomy data
```

**2. Caching Strategy**

```php
// TMDB upcoming movies cached 4 hours
set_transient( 'rt_tmdb_upcoming_movies', $data, 4 * HOUR_IN_SECONDS );

// Transient expires automatically
// No manual cleanup needed
```

**3. Meta Value Comparison**

```php
// ✓ Good - only update if changed
if ( $existing_rating !== $new_rating ) {
    update_post_meta( ... );
}

// ✗ Bad - always update
update_post_meta( ... );
```

**4. Cron Batching**

```php
// Only sync 5 movies per run (prevents server overload)
$limit = absint( get_option( Settings::OPTION_MOVIE_LIMIT, 5 ) );

// Sync next 5 unsynced movies
// Prevents spiking CPU when 10,000 movies need syncing
```

### Security Hardening

**1. Capability Checks**

```php
// ✓ Always check before allowing action
if ( ! current_user_can( 'edit_rt-movies' ) ) {
    wp_die( 'Unauthorized' );
}

// ✗ Never skip
// if ( is_admin() ) { ... }  // Too broad
```

**2. Nonce Verification**

REST endpoints should verify nonces:

```php
if ( ! wp_verify_nonce( $request['_wpnonce'], 'edit_movie' ) ) {
    return new \WP_Error( 'invalid_nonce', 'Nonce check failed' );
}
```

**3. Output Escaping**

```php
// ✓ Escape all output
echo esc_html( $post_title );        // For HTML
echo esc_url( $link );               // For URLs
echo esc_attr( $data );              // For HTML attributes

// ✗ Never output raw
echo $post_title;  // XSS vulnerability!
```

**4. Input Sanitization**

```php
// ✓ Sanitize user input
$title = sanitize_text_field( $_POST['title'] );

// TMDB sanitizes responses
$slug = sanitize_title( $tmdb_data['title'] );
```

**5. CSRF Protection**

REST endpoints are protected by WordPress nonce system.
Admin forms should use:

```php
wp_nonce_field( 'edit_movie_action', 'edit_movie_nonce' );
```

---

## Possible Improvements & Reviewer Feedback

### 1. Pagination for Large Datasets

**Current Issue:**

```php
'meta_query' => array(
    // Matches ALL movies, then limits by 5
    'posts_per_page' => 5,
)
```

**Improvement:**

```php
// Already does pagination by limiting
// But could optimize with "NOT EXISTS" check:

'meta_query' => array(
    array(
        'key' => '_mw_tmdb_synced_at',
        'compare' => 'NOT EXISTS',
    )
)

// Only fetch unsynced movies (much smaller dataset)
```

### 2. Error Notifications for Admin

**Current Issue:**

Errors stored in options table, but not surfaced to admin UI.

**Improvement:**

```php
add_action( 'admin_notices', function() {
    $error = get_option( 'rt_tmdb_last_sync_error' );
    
    if ( empty( $error ) ) {
        return;
    }
    
    $time = isset( $error['time'] ) ? $error['time'] : time();
    $since = human_time_diff( $time );
    
    printf(
        '<div class="notice notice-error"><p><strong>TMDB Sync Error</strong> (%s ago): %s</p></div>',
        esc_html( $since ),
        esc_html( $error['message'] ?? 'Unknown error' )
    );
});
```

### 3. Bulk Action to Force Resync

**Current Issue:**

No way to force resync (must wait 30 min or manually clear meta).

**Improvement:**

```php
add_filter( 'bulk_actions-edit-rt-movie', function( $bulk_actions ) {
    $bulk_actions['rt_force_tmdb_sync'] = __( 'Force TMDB Sync', 'rt-movie-library' );
    return $bulk_actions;
});

add_action( 'handle_bulk_actions-edit-rt-movie', function( $redirect, $action, $post_ids ) {
    if ( 'rt_force_tmdb_sync' === $action ) {
        foreach ( $post_ids as $post_id ) {
            delete_post_meta( $post_id, '_mw_tmdb_synced_at' );
        }
        
        // Manually trigger sync for these movies
        do_action( 'rt_tmdb_sync' );
    }
    return $redirect;
}, 10, 3 );
```

### 4. Rate Limiting on Poster Downloads

**Current Issue:**

No limit on concurrent poster downloads (could spike bandwidth).

**Improvement:**

```php
private function sync_movie_poster( $post_id, $poster_path ) {
    // Check if we're downloading too many posters right now
    $concurrent = wp_cache_get( 'rt_poster_downloads_in_progress' );
    
    if ( $concurrent > 3 ) {
        // Too many downloads, skip this one
        // Will retry in next cron run
        return;
    }
    
    // Increment counter
    wp_cache_set( 'rt_poster_downloads_in_progress', $concurrent + 1, '', 5 * MINUTE_IN_SECONDS );
    
    // Download and attach...
}
```

### 5. Logging System

**Current Issue:**

Limited visibility into what sync does.

**Improvement:**

```php
private function sync_single_movie( $post, $client ) {
    error_log( sprintf(
        '[TMDB Sync] Syncing movie ID %d: %s',
        $post->ID,
        $post->post_title
    ));
    
    $tmdb_data = $client->search_movie( $post->post_title );
    
    if ( is_wp_error( $tmdb_data ) ) {
        error_log( sprintf(
            '[TMDB Sync] Movie ID %d not found on TMDB',
            $post->ID
        ));
        return;
    }
    
    error_log( sprintf(
        '[TMDB Sync] Movie ID %d synced: rating=%.1f, date=%s',
        $post->ID,
        $tmdb_data['vote_average'],
        $tmdb_data['release_date']
    ));
}
```

### 6. Admin Settings Page

**Current Issue:**

Settings stored as raw options, no UI to change them.

**Improvement:**

```php
add_menu_page(
    'Movie Library Settings',
    'Movie Settings',
    'manage_options',
    'rt_movie_settings',
    'render_settings_page'
);

// Render settings form with:
// - TMDB API key input
// - Sync batch size slider
// - Last sync time display
// - Force resync button
```

### 7. Multisite Support

**Current Issue:**

Plugin doesn't account for WordPress multisite.

**Improvement:**

```php
// Store role per site
public static function activate() {
    // Only affect current site
    add_role( ... );
    
    // Use get_blog_option() / update_blog_option() for multisite
    update_blog_option( get_current_blog_id(), 'rt_movie_settings', [...] );
}
```

### 8. Backward Compatibility

**Current Issue:**

If update changes meta key names, old data breaks.

**Improvement:**

```php
// Migration function on activation
public function migrate_metadata() {
    // Check if old meta keys exist
    $old_meta = get_posts( array(
        'post_type' => 'rt-movie',
        'meta_key' => 'old_rating_key',
    ));
    
    // Copy to new meta key
    foreach ( $old_meta as $post ) {
        $old_value = get_post_meta( $post->ID, 'old_rating_key', true );
        update_post_meta( $post->ID, 'rt-movie-meta-basic-rating', $old_value );
    }
}
```

### 9. API Key Validation

**Current Issue:**

Invalid API key silently fails.

**Improvement:**

```php
public function validate_api_key( $api_key ) {
    $response = wp_remote_get(
        'https://api.themoviedb.org/3/configuration?api_key=' . $api_key
    );
    
    if ( is_wp_error( $response ) ) {
        return false;
    }
    
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    
    return isset( $data['images'] );  // Valid response has 'images' key
}
```

---

## Capstone Presentation Talking Points

### Opening

> "Today I want to walk you through the architecture of a sophisticated WordPress plugin that manages a movie database. The challenge was to create a system where users can manage movies with rich metadata, automatically sync data from external APIs, maintain SEO-friendly URLs, and provide granular permission controls. Let me show you how we solved each of these problems."

### Rewrite Rules

> "First, let's talk about URLs. A standard WordPress URL looks like `/?p=42`. But we wanted `https://example.com/movie/action/the-matrix-42/`. Here's how that works: We registered rewrite rules—regex patterns that Apache matches against the URL. When someone visits `/movie/action/the-matrix-42/`, Apache extracts three pieces of information using capture groups: the genre 'action', the post slug 'the-matrix', and the ID '42'. It rewrites this to an internal WordPress query. WordPress then loads the post by ID and renders the template. What's clever here is we also use a filter on `post_type_link` to *generate* permalinks in this format, so when the admin creates a link, it automatically gets the right structure."

### Capabilities System

> "Next, permissions. WordPress has this hierarchy where some users are admins and some are editors. But we needed a new role called 'movie-manager'. This person can manage movies but NOT delete users or change plugin settings. WordPress provides a capability system for this. We created a custom role with 40+ specific capabilities—everything from `edit_rt-movies` to `manage_rt-movie-genre`. The clever part is WordPress automatically maps 'meta-capabilities' to 'primitive capabilities', so when you check `current_user_can('edit_post', $movie_id)`, WordPress converts that to checking `edit_rt-movie` capability. We also grant all custom capabilities to the administrator role, ensuring backward compatibility."

### Dashboard Widgets

> "For admin intelligence, we added three dashboard widgets. The first two query the local database with optimized WP_Query calls—we disable `no_found_rows` and `update_post_term_cache` to prevent unnecessary database queries. The third widget pulls from TMDB API. We cache these results for 4 hours using WordPress transients, which are stored as options with automatic expiration. This prevents hammering the external API on every dashboard load."

### TMDB Integration

> "The core innovation is the TMDB API integration. Every 30 minutes, WordPress Cron fires and we sync movie metadata. Here's the flow: We query for the least-recently-synced movies, call TMDB API to search by title, extract the rating and release date, and download the poster image. If the API fails, we catch the exception, log it, and continue. We also compare new values with existing ones—only update if changed, to avoid unnecessary database writes. The poster is downloaded and attached as a featured image using WordPress attachment system."

### Cron System

> "The cron system deserves special attention. WordPress doesn't use real system cron—it uses pseudo-cron. This means cron only runs when someone visits the site. For production, you should disable WP-Cron and set up a real system cron job. We register our cron event in the `wp_options` table with a custom 30-minute interval. During deactivation, we clear the cron hook to prevent orphaned jobs. This is important because if we didn't do this, the cron would keep running even after the plugin was deactivated."

### Security & Performance

> "From a security standpoint, we always check capabilities before allowing sensitive actions. We escape all output to prevent XSS vulnerabilities, and we sanitize all input. Database queries are optimized to prevent slow downs. We batch cron operations to prevent server overload. From a maintenance perspective, we use the Singleton pattern for all core classes to ensure they're instantiated only once, and we use traits to keep the code DRY."

### Conclusion

> "What makes this plugin interesting from an architectural perspective is how it weaves together multiple WordPress systems—rewrite rules, capabilities, custom post types, REST API, cron, taxonomies, and meta boxes—into a cohesive system. The design emphasizes extensibility through hooks, performance through query optimization and caching, and security through capability checks and output escaping. Any questions?"

---

**End of Deep Technical Walkthrough**

This document provides the level of detail suitable for a code review, capstone presentation, or technical interview. It explains not just *what* the code does, but *why* each design decision was made and how WordPress internals support the implementation.
