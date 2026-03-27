# Rewrite Rules & Codebase Modification Challenges

## 30 Interview-Ready Tasks to Test Knowledge

---

## BEGINNER LEVEL (Understanding Basics)

### Challenge #1: Change Fallback Slug Value
**What it tests:** Understanding constants and configuration

**Current behavior:**
```php
private const FALLBACK_SLUG = 'uncategorized';
```
When a movie has no genre, the URL uses `/movie/uncategorized/...`

**Task:** Change the fallback slug to `'untagged'` so URLs become `/movie/untagged/movie-name-id/`

**Step-by-step instructions:**

1. Open [includes/classes/rewrite/class-rewrite-rules.php](includes/classes/rewrite/class-rewrite-rules.php)
2. Find line 23 (the FALLBACK_SLUG constant)
3. Change `'uncategorized'` to `'untagged'`
4. Save file
5. In WordPress admin:
   - Go to Settings → Permalinks
   - Click "Save Changes" (this flushes rewrite rules)
6. Create a new movie WITHOUT assigning a genre
7. Get the permalink - it should now show `/movie/untagged/...`

**Why this matters:** Tests understanding of WordPress permalink structure and how constants affect URL output.

---

### Challenge #2: Add Debug Output to Rewrite Rules
**What it tests:** Understanding WordPress hooks and debugging

**Task:** Add `error_log()` statements to trace when rewrite rules are registered and flushed.

**Step-by-step instructions:**

1. Open [includes/classes/rewrite/class-rewrite-rules.php](includes/classes/rewrite/class-rewrite-rules.php)
2. Find `register_rewrite_tags()` method (line 54)
3. Add at the start of the method:
   ```php
   error_log( '[Rewrite] register_rewrite_tags() called' );
   ```
4. Find `register_rewrite_rules()` method (line 64)
5. Add at the start:
   ```php
   error_log( '[Rewrite] register_rewrite_rules() called' );
   ```
6. Find `flush_on_activate()` method (line 107)
7. Add after the method is called:
   ```php
   error_log( '[Rewrite] Rules flushed on activation' );
   ```
8. Activate the plugin
9. Check `/wp-content/debug.log` - you should see the debug messages

**Why this matters:** Tests ability to use debugging tools and understand when WordPress hooks fire.

---

### Challenge #3: Extract Movie Slug Into Separate Method
**What it tests:** Code refactoring and DRY principle

**Current code:**
```php
private function build_movie_link( \WP_Post $post ): string {
    $genre_slug = $this->get_first_term_slug( $post->ID, self::TAXONOMY_GENRE );

    return trailingslashit(
        sprintf(
            '%s/movie/%s/%s-%d',
            untrailingslashit( home_url() ),
            $genre_slug,
            $post->post_name,  // ← Movie slug
            $post->ID
        )
    );
}
```

**Task:** Create a new method `get_post_slug()` that returns `sanitize_title( $post->post_name )` and use it in both `build_movie_link()` and `build_person_link()`.

**Step-by-step instructions:**

1. Open [includes/classes/rewrite/class-rewrite-rules.php](includes/classes/rewrite/class-rewrite-rules.php)
2. Add new method after `get_first_term_slug()`:
   ```php
   /**
    * Get sanitized post slug.
    *
    * @param \WP_Post $post Post object.
    * @return string
    */
   private function get_post_slug( \WP_Post $post ): string {
       $slug = sanitize_title( $post->post_name );
       return '' !== $slug ? $slug : 'post-' . $post->ID;
   }
   ```
3. In `build_movie_link()`, replace `$post->post_name` with `$this->get_post_slug( $post )`
4. In `build_person_link()`, replace `$post->post_name` with `$this->get_post_slug( $post )`
5. Test: Create a movie with special characters in slug (should still work)

**Why this matters:** Tests code organization, DRY principle, and method extraction.

---

## INTERMEDIATE LEVEL (Understanding Patterns)

### Challenge #4: Add URL Validation Helper Method
**What it tests:** Defensive programming and validation

**Task:** Create a method that validates a rewrite rule URL matches the expected pattern.

**Step-by-step instructions:**

1. Add this method to the class:
   ```php
   /**
    * Validate that a URL matches the expected rewrite pattern.
    *
    * @param string $url URL to validate.
    * @param string $post_type Post type (rt-movie or rt-person).
    * @return bool
    */
   private function validate_rewrite_url( string $url, string $post_type ): bool {
       if ( 'rt-movie' === $post_type ) {
           return (bool) preg_match( '/^\/movie\/[a-z0-9\-]+\/[a-z0-9\-]+-\d+\/?$/', $url );
       }

       if ( 'rt-person' === $post_type ) {
           return (bool) preg_match( '/^\/person\/[a-z0-9\-]+\/[a-z0-9\-]+-\d+\/?$/', $url );
       }

       return false;
   }
   ```

2. Update `filter_post_type_link()` to use it:
   ```php
   public function filter_post_type_link( string $link, \WP_Post $post ): string {
       if ( 'rt-movie' === $post->post_type ) {
           $generated_link = $this->build_movie_link( $post );
           if ( ! $this->validate_rewrite_url( $generated_link, $post->post_type ) ) {
               error_log( 'Invalid movie URL generated: ' . $generated_link );
               return $link;  // Fall back to default
           }
           return $generated_link;
       }

       if ( 'rt-person' === $post->post_type ) {
           $generated_link = $this->build_person_link( $post );
           if ( ! $this->validate_rewrite_url( $generated_link, $post->post_type ) ) {
               error_log( 'Invalid person URL generated: ' . $generated_link );
               return $link;
           }
           return $generated_link;
       }

       return $link;
   }
   ```

3. Test: Create a movie and verify the URL is valid. Check debug.log if there are invalid URLs.

**Why this matters:** Tests defensive programming, regex knowledge, and error handling.

---

### Challenge #5: Cache Genre/Career Slugs in Post Meta
**What it tests:** Performance optimization and caching understanding

**Current behavior:**
```php
// Every time build_movie_link() is called, it queries the database
$genre_slug = $this->get_first_term_slug( $post->ID, self::TAXONOMY_GENRE );
```

**Task:** Modify `get_first_term_slug()` to cache the result in post meta so future calls don't hit the database.

**Step-by-step instructions:**

1. Modify `get_first_term_slug()` method:
   ```php
   private function get_first_term_slug( int $post_id, string $taxonomy ): string {
       // Check cache in post meta first
       $cache_key = '_' . $taxonomy . '_slug_cache';
       $cached_slug = get_post_meta( $post_id, $cache_key, true );

       if ( ! empty( $cached_slug ) ) {
           return $cached_slug;
       }

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
           return self::FALLBACK_SLUG;
       }

       $slug = sanitize_title( (string) $terms[0]->slug );

       if ( empty( $slug ) ) {
           return self::FALLBACK_SLUG;
       }

       // Cache the result
       update_post_meta( $post_id, $cache_key, $slug );

       return $slug;
   }
   ```

2. Create a new method to clear the cache when taxonomy is updated:
   ```php
   public function clear_term_slug_cache( int $post_id, string $taxonomy ): void {
       $cache_key = '_' . $taxonomy . '_slug_cache';
       delete_post_meta( $post_id, $cache_key );
   }
   ```

3. Add hook in constructor to clear cache when terms are updated:
   ```php
   add_action( 'set_object_terms', [ $this, 'on_term_change' ], 10, 6 );
   ```

4. Add the callback:
   ```php
   public function on_term_change( int $object_id, array $terms, array $tt_ids, string $taxonomy ): void {
       if ( self::TAXONOMY_GENRE === $taxonomy || self::TAXONOMY_CAREER === $taxonomy ) {
           $this->clear_term_slug_cache( $object_id, $taxonomy );
       }
   }
   ```

5. Test: Edit a movie's genre multiple times. Second and subsequent edits should be faster (less DB queries).

**Why this matters:** Tests caching strategy, database optimization, and hook understanding.

---

### Challenge #6: Support Multiple Categories in URL (Comma-Separated)
**What it tests:** URL structure complexity and taxonomy understanding

**Current behavior:** URL includes only the FIRST genre.
```
/movie/action/the-matrix-42/
```

**Task:** Modify to support comma-separated genres:
```
/movie/action,sci-fi/the-matrix-42/
```

**Step-by-step instructions:**

1. Update `register_rewrite_tags()`:
   ```php
   public function register_rewrite_tags(): void {
       // Changed regex to allow commas
       add_rewrite_tag( '%mw_genre%', '([^/]+)' );  // Already allows commas!
       add_rewrite_tag( '%mw_career%', '([^/]+)' );
   }
   ```

2. Update `register_rewrite_rules()`:
   ```php
   public function register_rewrite_rules(): void {
       add_rewrite_rule(
           '^movie/([^/]+)/([^/]+)-(\d+)/?$',
           'index.php?post_type=rt-movie&mw_genre=$matches[1]&name=$matches[2]&p=$matches[3]',
           'top'
       );
       // The regex is already flexible enough!
   }
   ```

3. Update `build_movie_link()` to fetch multiple genres:
   ```php
   private function build_movie_link( \WP_Post $post ): string {
       $genre_slugs = $this->get_genre_slugs( $post->ID );

       return trailingslashit(
           sprintf(
               '%s/movie/%s/%s-%d',
               untrailingslashit( home_url() ),
               implode( ',', $genre_slugs ),  // Comma-separated
               $post->post_name,
               $post->ID
           )
       );
   }
   ```

4. Add new method:
   ```php
   private function get_genre_slugs( int $post_id ): array {
       $terms = wp_get_object_terms(
           $post_id,
           self::TAXONOMY_GENRE,
           array(
               'orderby' => 'term_id',
               'order'   => 'ASC',
               'number'  => 3,  // Limit to 3 genres
           )
       );

       if ( is_wp_error( $terms ) || empty( $terms ) ) {
           return [ self::FALLBACK_SLUG ];
       }

       $slugs = wp_list_pluck( $terms, 'slug' );
       $slugs = array_map( 'sanitize_title', $slugs );

       return ! empty( $slugs ) ? $slugs : [ self::FALLBACK_SLUG ];
   }
   ```

5. Test: Assign multiple genres to a movie. URL should show all of them.

**Why this matters:** Tests URL design flexibility, regex patterns, and array handling.

---

### Challenge #7: Add Suffix to Differentiate Movie Types
**What it tests:** URL structure design and business logic

**Task:** Add suffix to URLs to distinguish between different movie statuses:
```
/movie/action/the-matrix-42/    (published)
/movie/action/the-matrix-42-draft/  (draft)
/movie/action/the-matrix-42-archived/  (archived)
```

**Step-by-step instructions:**

1. Update rewrite rules:
   ```php
   public function register_rewrite_rules(): void {
       add_rewrite_rule(
           '^movie/([^/]+)/([^/]+)-(\d+)(?:-([a-z]+))?/?$',  // New optional suffix
           'index.php?post_type=rt-movie&mw_genre=$matches[1]&name=$matches[2]&p=$matches[3]&status=$matches[4]',
           'top'
       );
   }
   ```

2. Update `build_movie_link()`:
   ```php
   private function build_movie_link( \WP_Post $post ): string {
       $genre_slug = $this->get_first_term_slug( $post->ID, self::TAXONOMY_GENRE );
       $status_suffix = '';

       if ( 'draft' === $post->post_status ) {
           $status_suffix = '-draft';
       } elseif ( 'publish' !== $post->post_status ) {
           $status_suffix = '-' . $post->post_status;
       }

       return trailingslashit(
           sprintf(
               '%s/movie/%s/%s-%d%s',
               untrailingslashit( home_url() ),
               $genre_slug,
               $post->post_name,
               $post->ID,
               $status_suffix
           )
       );
   }
   ```

3. Test: Create a movie, save it as draft, check URL. Publish it, check URL again.

**Why this matters:** Tests URL design flexibility, business logic in URLs, and regex complexity.

---

## INTERMEDIATE-ADVANCED (Extending Functionality)

### Challenge #8: Add Query Variable to Filter by Multiple Genres
**What it tests:** Query variable handling and WP_Query

**Current behavior:** URL includes genre, but WP_Query ignores it.

**Task:** Add query variable so WordPress can filter results by the genre in the URL.

**Step-by-step instructions:**

1. Add to the class:
   ```php
   public function register_custom_query_vars( $query_vars ) {
       $query_vars[] = 'mw_genre';
       $query_vars[] = 'mw_career';
       return $query_vars;
   }
   ```

2. Add hook in constructor:
   ```php
   add_filter( 'query_vars', [ $this, 'register_custom_query_vars' ] );
   ```

3. Add method to modify WP_Query:
   ```php
   public function modify_query_for_genre( $query ) {
       if ( ! is_admin() && $query->is_main_query() && isset( $query->query_vars['mw_genre'] ) ) {
           $genre = sanitize_title( $query->query_vars['mw_genre'] );
           
           $query->set( 'tax_query', array(
               array(
                   'taxonomy' => self::TAXONOMY_GENRE,
                   'field' => 'slug',
                   'terms' => $genre,
               )
           ));
       }
   }
   ```

4. Add hook in constructor:
   ```php
   add_action( 'pre_get_posts', [ $this, 'modify_query_for_genre' ] );
   ```

5. Test: Visit `/movie/action/the-matrix-42/` - the query should verify the movie actually has that genre.

**Why this matters:** Tests WordPress query system, tax_query, and validation.

---

### Challenge #9: Add Breadcrumb Support
**What it tests:** URL structure interpretation and helper methods

**Task:** Create a method that parses the URL to generate breadcrumbs.

**Step-by-step instructions:**

1. Add method:
   ```php
   /**
    * Parse breadcrumbs from rewrite URL.
    *
    * @param string $url The current page URL.
    * @return array Array of breadcrumb items.
    */
   public function get_breadcrumbs( string $url ): array {
       $breadcrumbs = array(
           'home' => home_url(),
       );

       if ( preg_match( '/\/movie\/([^\/]+)\/([^\/]+)-(\d+)/', $url, $matches ) ) {
           $genre_slug = $matches[1];
           $post_id = intval( $matches[3] );

           $breadcrumbs['movies'] = home_url( '/movies' );
           $breadcrumbs['genre'] = home_url( '/movie/' . $genre_slug );
           $breadcrumbs['current'] = get_the_title( $post_id );

           return $breadcrumbs;
       }

       return $breadcrumbs;
   }
   ```

2. In a theme template, use it:
   ```php
   global $wp_rewrite_rules;
   $breadcrumbs = $rewrite_rules->get_breadcrumbs( $_SERVER['REQUEST_URI'] );
   ```

3. Test: Visit a movie page, generate breadcrumbs.

**Why this matters:** Tests URL parsing, regex understanding, and practical implementation.

---

### Challenge #10: Add Permalink Structure Settings
**What it tests:** WordPress settings and configuration

**Task:** Allow admins to choose between different URL structures via settings.

**Step-by-step instructions:**

1. Add constant:
   ```php
   private const PERMALINK_FORMAT_OPTION = 'rt_movie_permalink_format';
   ```

2. Add method to get current format:
   ```php
   private function get_permalink_format(): string {
       $format = get_option( self::PERMALINK_FORMAT_OPTION, 'genre' );
       return in_array( $format, [ 'genre', 'author', 'none' ], true ) ? $format : 'genre';
   }
   ```

3. Update `build_movie_link()`:
   ```php
   private function build_movie_link( \WP_Post $post ): string {
       $format = $this->get_permalink_format();

       if ( 'genre' === $format ) {
           $slug = $this->get_first_term_slug( $post->ID, self::TAXONOMY_GENRE );
       } elseif ( 'author' === $format ) {
           $author = get_userdata( $post->post_author );
           $slug = $author ? sanitize_title( $author->user_login ) : 'author';
       } else {
           $slug = 'movie';
       }

       return trailingslashit(
           sprintf(
               '%s/movie/%s/%s-%d',
               untrailingslashit( home_url() ),
               $slug,
               $post->post_name,
               $post->ID
           )
       );
   }
   ```

4. Create a settings page (in separate class):
   ```php
   public function render_settings() {
       $current = get_option( 'rt_movie_permalink_format', 'genre' );
       ?>
       <select name="rt_movie_permalink_format">
           <option value="genre" <?php selected( $current, 'genre' ); ?>>Genre-Based</option>
           <option value="author" <?php selected( $current, 'author' ); ?>>Author-Based</option>
           <option value="none" <?php selected( $current, 'none' ); ?>>No Category</option>
       </select>
       <?php
   }
   ```

5. Test: Change setting, verify URLs change accordingly.

**Why this matters:** Tests configuration, settings API, and flexibility.

---

## ADVANCED LEVEL (Full Feature Implementation)

### Challenge #11: Add Reverse URL Parser
**What it tests:** Advanced regex and WordPress internals

**Task:** Create method that extracts post ID and genre from a URL string.

**Step-by-step instructions:**

1. Add method:
   ```php
   /**
    * Parse movie URL and extract components.
    *
    * @param string $url Movie URL.
    * @return array|false Array with 'id', 'genre', 'slug' or false if invalid.
    */
   public function parse_movie_url( string $url ) {
       if ( ! preg_match( '/\/movie\/([^\/]+)\/([^\/]+)-(\d+)\/?/', $url, $matches ) ) {
           return false;
       }

       return array(
           'genre' => sanitize_title( $matches[1] ),
           'slug' => sanitize_title( $matches[2] ),
           'id' => intval( $matches[3] ),
       );
   }
   ```

2. Add validation method:
   ```php
   public function validate_parsed_movie( array $parsed ): bool {
       $post = get_post( $parsed['id'] );

       if ( ! $post || 'rt-movie' !== $post->post_type ) {
           return false;
       }

       if ( $post->post_name !== $parsed['slug'] ) {
           return false;
       }

       $terms = wp_get_object_terms( $post->ID, self::TAXONOMY_GENRE, array( 'fields' => 'slugs' ) );
       
       return in_array( $parsed['genre'], $terms, true ) || self::FALLBACK_SLUG === $parsed['genre'];
   }
   ```

3. Test:
   ```php
   $parsed = $this->parse_movie_url( '/movie/action/the-matrix-42/' );
   if ( $parsed && $this->validate_parsed_movie( $parsed ) ) {
       echo "Valid movie URL";
   }
   ```

**Why this matters:** Tests regex, WordPress post retrieval, and validation logic.

---

### Challenge #12: Add Redirect for Old URL Format
**What it tests:** WordPress redirect handling and backward compatibility

**Current behavior:** Old URLs like `/?p=42` don't redirect to new format.

**Task:** Add 301 redirect from old to new URL format.

**Step-by-step instructions:**

1. Add method:
   ```php
   public function redirect_old_urls() {
       if ( is_admin() || ! is_singular( array( 'rt-movie', 'rt-person' ) ) ) {
           return;
       }

       global $wp_query;
       $post = $wp_query->get_queried_object();

       if ( ! $post ) {
           return;
       }

       $expected_url = 'rt-movie' === $post->post_type
           ? $this->build_movie_link( $post )
           : $this->build_person_link( $post );

       $current_url = $_SERVER['REQUEST_URI'] ?? '';

       if ( $expected_url !== home_url( $current_url ) && ! empty( $expected_url ) ) {
           wp_safe_remote_post( $expected_url, array( 'blocking' => false ) );
           wp_redirect( $expected_url, 301 );
           exit;
       }
   }
   ```

2. Add hook in constructor:
   ```php
   add_action( 'template_redirect', [ $this, 'redirect_old_urls' ] );
   ```

3. Test: Visit old URL format, should redirect to new format.

**Why this matters:** Tests backward compatibility, redirects, and WordPress hooks.

---

### Challenge #13: Add Canonical Tag Support
**What it tests:** SEO understanding and meta tag handling

**Task:** Add canonical link tag to prevent duplicate content issues.

**Step-by-step instructions:**

1. Add method:
   ```php
   public function add_canonical_tag() {
       if ( ! is_singular( array( 'rt-movie', 'rt-person' ) ) ) {
           return;
       }

       $post = get_queried_object();
       $canonical = 'rt-movie' === $post->post_type
           ? $this->build_movie_link( $post )
           : $this->build_person_link( $post );

       printf(
           '<link rel="canonical" href="%s" />' . "\n",
           esc_url( $canonical )
       );
   }
   ```

2. Add hook in constructor:
   ```php
   add_action( 'wp_head', [ $this, 'add_canonical_tag' ] );
   ```

3. Test: View source of movie page, should see canonical tag.

**Why this matters:** Tests SEO, WordPress head hooks, and meta tag implementation.

---

### Challenge #14: Add Rewrite Rule Priority System
**What it tests:** Rules complexity and WordPress ordering

**Task:** Allow some rules to be marked as high-priority ('top') and others as normal.

**Step-by-step instructions:**

1. Add method:
   ```php
   private function get_rewrite_rules(): array {
       return array(
           array(
               'rule' => '^movie/([^/]+)/([^/]+)-(\d+)/?$',
               'query' => 'index.php?post_type=rt-movie&mw_genre=$matches[1]&name=$matches[2]&p=$matches[3]',
               'position' => 'top',
               'priority' => 100,
           ),
           array(
               'rule' => '^person/([^/]+)/([^/]+)-(\d+)/?$',
               'query' => 'index.php?post_type=rt-person&mw_career=$matches[1]&name=$matches[2]&p=$matches[3]',
               'position' => 'top',
               'priority' => 100,
           ),
       );
   }
   ```

2. Update `register_rewrite_rules()`:
   ```php
   public function register_rewrite_rules(): void {
       $rules = $this->get_rewrite_rules();

       foreach ( $rules as $rule ) {
           add_rewrite_rule(
               $rule['rule'],
               $rule['query'],
               $rule['position']
           );
       }
   }
   ```

3. Test: Verify rules register in correct priority order.

**Why this matters:** Tests code organization, scalability, and rule management.

---

### Challenge #15: Add Rewrite Rule Audit Log
**What it tests:** Debugging and monitoring

**Task:** Log all rewrite rule registrations and flushes for troubleshooting.

**Step-by-step instructions:**

1. Add method:
   ```php
   private function log_rewrite_action( string $action, string $details = '' ): void {
       $log_key = 'rt_rewrite_audit_log';
       $log = get_option( $log_key, array() );

       $log[] = array(
           'timestamp' => current_time( 'mysql' ),
           'action' => $action,
           'details' => $details,
           'user_id' => get_current_user_id(),
       );

       // Keep only last 100 entries
       if ( count( $log ) > 100 ) {
           $log = array_slice( $log, -100 );
       }

       update_option( $log_key, $log );
   }
   ```

2. Update methods to call logging:
   ```php
   public function register_rewrite_tags(): void {
       $this->log_rewrite_action( 'register_tags', 'Movie and Person tags registered' );
       // ... rest of method
   }

   public static function flush_on_activate(): void {
       $instance = self::get_instance();
       $instance->log_rewrite_action( 'flush_activate', 'Rules flushed on plugin activation' );
       // ... rest of method
   }
   ```

3. Create a method to retrieve logs:
   ```php
   public function get_rewrite_audit_log(): array {
       return get_option( 'rt_rewrite_audit_log', array() );
   }
   ```

4. Test: Check option in wp-admin with a plugin like "Advanced Options" or debug output.

**Why this matters:** Tests logging, monitoring, and troubleshooting systems.

---

## EXPERT LEVEL (Complex Features & Performance)

### Challenge #16: Implement Rewrite Rule Caching
**What it tests:** Performance optimization and transient usage

**Task:** Cache the generated rewrite rules to avoid regenerating them on every page load.

**Step-by-step instructions:**

1. Add constants:
   ```php
   private const REWRITE_RULES_CACHE_KEY = 'rt_rewrite_rules_cache';
   private const REWRITE_RULES_CACHE_TTL = DAY_IN_SECONDS;
   ```

2. Update methods:
   ```php
   public function register_rewrite_rules(): void {
       $cached = get_transient( self::REWRITE_RULES_CACHE_KEY );

       if ( false !== $cached ) {
           // Use cached rules
           foreach ( $cached as $rule ) {
               add_rewrite_rule( $rule['pattern'], $rule['query'], $rule['position'] );
           }
           return;
       }

       $rules = array(
           array(
               'pattern' => '^movie/([^/]+)/([^/]+)-(\d+)/?$',
               'query' => 'index.php?post_type=rt-movie&mw_genre=$matches[1]&name=$matches[2]&p=$matches[3]',
               'position' => 'top',
           ),
           array(
               'pattern' => '^person/([^/]+)/([^/]+)-(\d+)/?$',
               'query' => 'index.php?post_type=rt-person&mw_career=$matches[1]&name=$matches[2]&p=$matches[3]',
               'position' => 'top',
           ),
       );

       foreach ( $rules as $rule ) {
           add_rewrite_rule( $rule['pattern'], $rule['query'], $rule['position'] );
       }

       // Cache for 24 hours
       set_transient( self::REWRITE_RULES_CACHE_KEY, $rules, self::REWRITE_RULES_CACHE_TTL );
   }
   ```

3. Add cache invalidation on flush:
   ```php
   public static function flush_on_activate(): void {
       delete_transient( self::REWRITE_RULES_CACHE_KEY );
       // ... rest of method
   }
   ```

4. Test: Activate plugin, check if transient is set with `get_transient()`.

**Why this matters:** Tests transient caching, performance optimization, and cache invalidation.

---

### Challenge #17: Add Rewrite Rule Conflict Detection
**What it tests:** WordPress internals and rule compatibility

**Task:** Detect if custom rewrite rules conflict with existing ones.

**Step-by-step instructions:**

1. Add method:
   ```php
   public function check_rewrite_conflicts(): array {
       global $wp_rewrite;
       $conflicts = array();

       $our_rules = array(
           '^movie/([^/]+)/([^/]+)-(\d+)/?$',
           '^person/([^/]+)/([^/]+)-(\d+)/?$',
       );

       $all_rules = $wp_rewrite->rules ?? array();

       foreach ( $our_rules as $our_rule ) {
           foreach ( $all_rules as $existing_rule => $query ) {
               // Check if patterns are too similar
               if ( $this->rules_might_conflict( $our_rule, $existing_rule ) ) {
                   $conflicts[] = array(
                       'ours' => $our_rule,
                       'existing' => $existing_rule,
                       'severity' => 'warning',
                   );
               }
           }
       }

       return $conflicts;
   }

   private function rules_might_conflict( string $rule1, string $rule2 ): bool {
       // Simplified: check if both start with same pattern
       $pattern1 = explode( '/', $rule1 )[0] ?? '';
       $pattern2 = explode( '/', $rule2 )[0] ?? '';

       return $pattern1 === $pattern2 && $rule1 !== $rule2;
   }
   ```

2. Add check in activation:
   ```php
   public static function flush_on_activate(): void {
       $instance = self::get_instance();
       $conflicts = $instance->check_rewrite_conflicts();

       if ( ! empty( $conflicts ) ) {
           update_option( 'rt_rewrite_conflicts', $conflicts );
       }
       // ... rest
   }
   ```

3. Test: Activate plugin, check `rt_rewrite_conflicts` option.

**Why this matters:** Tests rewrite rule analysis, conflict detection, and WordPress compatibility.

---

### Challenge #18: Add REST Endpoint for URL Generation
**What it tests:** REST API and WordPress integration

**Task:** Create a REST endpoint that generates proper URLs for movies/persons.

**Step-by-step instructions:**

1. Add method:
   ```php
   public function register_rest_routes(): void {
       register_rest_route( 'rt-movie-library/v1', '/generate-url', array(
           'methods' => 'POST',
           'callback' => [ $this, 'rest_generate_url' ],
           'permission_callback' => function() {
               return current_user_can( 'edit_rt-movies' );
           },
       ));
   }

   public function rest_generate_url( $request ) {
       $post_id = $request['post_id'] ?? null;
       $post_type = $request['post_type'] ?? null;

       if ( ! $post_id || ! $post_type ) {
           return new \WP_Error( 'missing_params', 'post_id and post_type required' );
       }

       $post = get_post( $post_id );

       if ( ! $post || $post->post_type !== $post_type ) {
           return new \WP_Error( 'invalid_post', 'Post not found' );
       }

       $url = 'rt-movie' === $post_type
           ? $this->build_movie_link( $post )
           : $this->build_person_link( $post );

       return array(
           'url' => $url,
           'post_id' => $post_id,
           'post_type' => $post_type,
       );
   }
   ```

2. Add hook in constructor:
   ```php
   add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
   ```

3. Test using cURL:
   ```bash
   curl -X POST https://example.com/wp-json/rt-movie-library/v1/generate-url \
     -d '{"post_id": 42, "post_type": "rt-movie"}'
   ```

**Why this matters:** Tests REST API integration and programmatic URL generation.

---

### Challenge #19: Add Multi-Language URL Support
**What it tests:** Internationalization and WPML compatibility

**Task:** Support different URL structures for different languages.

**Step-by-step instructions:**

1. Add method:
   ```php
   private function get_movie_label( string $language = 'en' ): string {
       $labels = array(
           'en' => 'movie',
           'es' => 'pelicula',
           'fr' => 'film',
           'de' => 'film',
       );

       return $labels[ $language ] ?? $labels['en'];
   }

   private function get_current_language(): string {
       // Support for WPML or Polylang
       if ( function_exists( 'wpml_get_default_language' ) ) {
           return defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : 'en';
       }

       return 'en';
   }
   ```

2. Update `build_movie_link()`:
   ```php
   private function build_movie_link( \WP_Post $post ): string {
       $language = $this->get_current_language();
       $movie_label = $this->get_movie_label( $language );
       $genre_slug = $this->get_first_term_slug( $post->ID, self::TAXONOMY_GENRE );

       return trailingslashit(
           sprintf(
               '%s/%s/%s/%s-%d',
               untrailingslashit( home_url() ),
               $movie_label,  // 'pelicula' for Spanish, 'film' for German, etc
               $genre_slug,
               $post->post_name,
               $post->ID
           )
       );
   }
   ```

3. Update rewrite rules for each language:
   ```php
   public function register_rewrite_rules(): void {
       $languages = array( 'en', 'es', 'fr', 'de' );

       foreach ( $languages as $lang ) {
           $label = $this->get_movie_label( $lang );
           add_rewrite_rule(
               '^' . $label . '/([^/]+)/([^/]+)-(\d+)/?$',
               'index.php?post_type=rt-movie&mw_genre=$matches[1]&name=$matches[2]&p=$matches[3]',
               'top'
           );
       }
   }
   ```

4. Test: Change site language, URLs should reflect it.

**Why this matters:** Tests internationalization, multi-language support, and URL flexibility.

---

### Challenge #20: Add Rewrite Rule Version Control
**What it tests:** Code versioning and migration strategy

**Task:** Track versions of rewrite rules and detect when they need to be re-registered.

**Step-by-step instructions:**

1. Add constant and method:
   ```php
   private const REWRITE_RULES_VERSION = 2;
   private const REWRITE_RULES_VERSION_KEY = 'rt_rewrite_rules_version';

   public function check_rewrite_version(): void {
       $current_version = get_option( self::REWRITE_RULES_VERSION_KEY, 0 );

       if ( $current_version < self::REWRITE_RULES_VERSION ) {
           // Rewrite rules have changed, need to re-register and flush
           $this->register_rewrite_tags();
           $this->register_rewrite_rules();
           flush_rewrite_rules( false );

           update_option( self::REWRITE_RULES_VERSION_KEY, self::REWRITE_RULES_VERSION );
       }
   }
   ```

2. Add hook in constructor:
   ```php
   add_action( 'init', [ $this, 'check_rewrite_version' ], 1 );
   ```

3. Test: Change REWRITE_RULES_VERSION, reload site. Rules should be re-flushed.

**Why this matters:** Tests versioning strategy, migration management, and update handling.

---

### Challenge #21: Add Rewrite Rule Analytics
**What it tests:** Tracking and reporting

**Task:** Track which rewrite rules are being used most frequently.

**Step-by-step instructions:**

1. Add method:
   ```php
   private const ANALYTICS_KEY = 'rt_rewrite_analytics';

   public function track_rewrite_usage(): void {
       if ( is_admin() || ! is_singular( array( 'rt-movie', 'rt-person' ) ) ) {
           return;
       }

       global $wp_query;
       $post = $wp_query->get_queried_object();

       if ( ! $post ) {
           return;
       }

       $analytics = get_option( self::ANALYTICS_KEY, array() );
       $post_type = $post->post_type;

       if ( ! isset( $analytics[ $post_type ] ) ) {
           $analytics[ $post_type ] = 0;
       }

       $analytics[ $post_type ]++;

       update_option( self::ANALYTICS_KEY, $analytics );
   }

   public function get_rewrite_analytics(): array {
       return get_option( self::ANALYTICS_KEY, array() );
   }
   ```

2. Add hook in constructor:
   ```php
   add_action( 'wp_head', [ $this, 'track_rewrite_usage' ] );
   ```

3. Test: Visit several movie pages, check analytics in options.

**Why this matters:** Tests tracking, analytics, and data collection.

---

## FINAL CHALLENGES (Full Feature Overhaul)

### Challenge #22: Refactor All Methods to Use Data Structures
**What it tests:** Code organization and OOP principles

**Task:** Replace individual methods with a unified data structure approach.

**Current approach:**
```php
private const FALLBACK_SLUG = 'uncategorized';
private const TAXONOMY_GENRE = 'rt-movie-genre';

public function build_movie_link() { ... }
public function build_person_link() { ... }
```

**Target approach:**
```php
private function get_cpt_config( string $post_type ): array {
    $config = array(
        'rt-movie' => array(
            'base_url' => 'movie',
            'taxonomy' => 'rt-movie-genre',
            'fallback_slug' => 'uncategorized',
        ),
        'rt-person' => array(
            'base_url' => 'person',
            'taxonomy' => 'rt-person-career',
            'fallback_slug' => 'uncategorized',
        ),
    );

    return $config[ $post_type ] ?? null;
}

public function build_link( \WP_Post $post ): string {
    $config = $this->get_cpt_config( $post->post_type );
    if ( ! $config ) {
        return get_permalink( $post );
    }

    $slug = $this->get_first_term_slug( $post->ID, $config['taxonomy'] );

    return trailingslashit(
        sprintf(
            '%s/%s/%s/%s-%d',
            untrailingslashit( home_url() ),
            $config['base_url'],
            $slug,
            $post->post_name,
            $post->ID
        )
    );
}
```

**Step-by-step instructions:**

1. Create the `get_cpt_config()` method
2. Refactor `build_movie_link()` to use `build_link()`
3. Refactor `build_person_link()` to use `build_link()`
4. Refactor `filter_post_type_link()` to use unified method
5. Remove duplicate code
6. Test: Create both movies and persons, verify URLs work

**Why this matters:** Tests refactoring skills, DRY principle, and code organization.

---

### Challenge #23: Add Support for Custom Post Types (Plugin API)
**What it tests:** Extensibility and plugin architecture

**Task:** Create a filter so other plugins can register custom post types with rewrite rules.

**Step-by-step instructions:**

1. Add method:
   ```php
   public function register_custom_cpt_rewrites(): void {
       /**
        * Filter to allow other plugins to register custom post types.
        *
        * @param array $cpts Array of CPT configurations.
        */
       $cpts = apply_filters( 'rt_movie_library_custom_cpts', array() );

       foreach ( $cpts as $post_type => $config ) {
           if ( ! isset( $config['base_url'], $config['taxonomy'] ) ) {
               continue;
           }

           add_rewrite_tag( '%' . $post_type . '_term%', '([^/]+)' );
           add_rewrite_rule(
               '^' . $config['base_url'] . '/([^/]+)/([^/]+)-(\d+)/?$',
               'index.php?post_type=' . $post_type . '&' . $post_type . '_term=$matches[1]&name=$matches[2]&p=$matches[3]',
               'top'
           );
       }
   }
   ```

2. Add hook in constructor:
   ```php
   add_action( 'init', [ $this, 'register_custom_cpt_rewrites' ], 5 );
   ```

3. Test: In another plugin, use the filter:
   ```php
   add_filter( 'rt_movie_library_custom_cpts', function( $cpts ) {
       $cpts['rt-book'] = array(
           'base_url' => 'book',
           'taxonomy' => 'rt-book-genre',
       );
       return $cpts;
   });
   ```

**Why this matters:** Tests extensibility, plugin API design, and filter usage.

---

### Challenge #24: Add Performance Monitoring
**What it tests:** Performance analysis and optimization

**Task:** Add query monitoring to track database performance of rewrite-related operations.

**Step-by-step instructions:**

1. Add constants and methods:
   ```php
   private const PERFORMANCE_LOG = 'rt_rewrite_performance_log';

   public function start_performance_monitor( string $operation ): float {
       return microtime( true );
   }

   public function end_performance_monitor( string $operation, float $start_time ): void {
       $elapsed = microtime( true ) - $start_time;

       if ( $elapsed > 0.1 ) {  // Log if over 100ms
           $log = get_option( self::PERFORMANCE_LOG, array() );

           $log[] = array(
               'operation' => $operation,
               'elapsed_ms' => round( $elapsed * 1000, 2 ),
               'timestamp' => current_time( 'mysql' ),
           );

           // Keep last 500 entries
           if ( count( $log ) > 500 ) {
               $log = array_slice( $log, -500 );
           }

           update_option( self::PERFORMANCE_LOG, $log );
       }
   }
   ```

2. Wrap operations:
   ```php
   public function get_first_term_slug( int $post_id, string $taxonomy ): string {
       $start = $this->start_performance_monitor( "term_slug:{$taxonomy}" );

       $terms = wp_get_object_terms( ... );
       // ... rest of method

       $this->end_performance_monitor( "term_slug:{$taxonomy}", $start );

       return $slug;
   }
   ```

3. Test: Run several operations, check performance log.

**Why this matters:** Tests performance monitoring, bottleneck detection, and optimization.

---

### Challenge #25: Add A/B Testing for URL Formats
**What it tests:** Experimentation and testing

**Task:** Allow A/B testing of different URL formats for analytics comparison.

**Step-by-step instructions:**

1. Add method:
   ```php
   private const AB_TEST_OPTION = 'rt_url_format_ab_test';

   public function get_url_variant( \WP_Post $post ): string {
       $ab_test = get_option( self::AB_TEST_OPTION, array() );

       if ( empty( $ab_test['enabled'] ) ) {
           return 'control';  // Default variant
       }

       // Assign variant based on post ID (consistent per post)
       $variant = (intval( $post->ID ) % 2 === 0) ? 'control' : 'test';

       return $variant;
   }

   private function build_movie_link_control( \WP_Post $post ): string {
       // Current format: /movie/{genre}/{slug}-{id}
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

   private function build_movie_link_test( \WP_Post $post ): string {
       // Test format: /movie/{id}/{slug}
       return trailingslashit(
           sprintf(
               '%s/movie/%d/%s',
               untrailingslashit( home_url() ),
               $post->ID,
               $post->post_name
           )
       );
   }

   private function build_movie_link( \WP_Post $post ): string {
       $variant = $this->get_url_variant( $post );

       if ( 'test' === $variant ) {
           return $this->build_movie_link_test( $post );
       }

       return $this->build_movie_link_control( $post );
   }
   ```

2. Add rewrite rules for both variants:
   ```php
   public function register_rewrite_rules(): void {
       // Control variant
       add_rewrite_rule(
           '^movie/([^/]+)/([^/]+)-(\d+)/?$',
           'index.php?post_type=rt-movie&mw_genre=$matches[1]&name=$matches[2]&p=$matches[3]&variant=control',
           'top'
       );

       // Test variant
       add_rewrite_rule(
           '^movie/(\d+)/([^/]+)/?$',
           'index.php?post_type=rt-movie&p=$matches[1]&name=$matches[2]&variant=test',
           'top'
       );
   }
   ```

3. Add tracking:
   ```php
   public function track_url_variant(): void {
       if ( is_singular( array( 'rt-movie' ) ) ) {
           $variant = isset( $_GET['variant'] ) ? sanitize_text_field( $_GET['variant'] ) : 'control';
           // Send to analytics: variant = control or test
       }
   }
   ```

**Why this matters:** Tests experimentation, multiple variants, and analytics tracking.

---

### Challenge #26-30: System Integration Challenges

These final challenges require integration across multiple systems:

### Challenge #26: Integrate with WooCommerce for Movie Products
**What it tests:** Third-party plugin integration

Create rewrite rules that work alongside WooCommerce products where each movie can have associated merchandise.

---

### Challenge #27: Add GraphQL Support
**What it tests:** Modern API knowledge

Create GraphQL queries/mutations that work with the rewrite system to fetch movies by URL components.

---

### Challenge #28: Add Webpack/Build Process
**What it tests:** Modern development workflow

Refactor code to use ES6 modules and Webpack bundling for the JavaScript components.

---

### Challenge #29: Add Docker Support for Local Development
**What it tests:** DevOps and containerization

Create Docker setup for testing rewrite rules in isolated environment.

---

### Challenge #30: Create Full Test Suite
**What it tests:** Testing knowledge (PHPUnit, Jest, etc.)

Write comprehensive unit and integration tests for all rewrite rule scenarios.

---

## Summary Table

| # | Challenge | Difficulty | Key Concepts | Time |
|---|-----------|-----------|--------------|------|
| 1 | Change fallback slug | Beginner | Constants, Configuration | 5 min |
| 2 | Add debug output | Beginner | Hooks, Debugging | 10 min |
| 3 | Extract method | Beginner | Refactoring, DRY | 15 min |
| 4 | Add URL validation | Intermediate | Regex, Validation | 20 min |
| 5 | Cache term slugs | Intermediate | Caching, Hooks | 30 min |
| 6 | Multi-category URLs | Intermediate | URL Design, Arrays | 25 min |
| 7 | Add status suffix | Intermediate | URL Structure, Logic | 20 min |
| 8 | Query variable filtering | Intermediate | WP_Query, tax_query | 25 min |
| 9 | Breadcrumb support | Intermediate | URL Parsing, Regex | 20 min |
| 10 | Permalink settings | Intermediate | Settings, Configuration | 30 min |
| 11 | Reverse URL parser | Advanced | Regex, Validation | 25 min |
| 12 | Redirect old URLs | Advanced | Hooks, Redirects | 20 min |
| 13 | Canonical tags | Advanced | SEO, Meta tags | 15 min |
| 14 | Rule priority system | Advanced | Code Organization | 25 min |
| 15 | Audit logging | Advanced | Logging, Monitoring | 20 min |
| 16 | Cache rewrite rules | Expert | Transients, Performance | 25 min |
| 17 | Conflict detection | Expert | WordPress Internals | 30 min |
| 18 | REST endpoint | Expert | REST API | 30 min |
| 19 | Multi-language | Expert | i18n, WPML | 35 min |
| 20 | Version control | Expert | Versioning, Migration | 25 min |
| 21 | Analytics tracking | Expert | Tracking, Data | 25 min |
| 22 | Refactor data structures | Expert | OOP, Architecture | 45 min |
| 23 | Plugin API | Expert | Extensibility, Filters | 30 min |
| 24 | Performance monitoring | Expert | Optimization, Profiling | 35 min |
| 25 | A/B testing | Expert | Testing, Variants | 40 min |
| 26-30 | Integration challenges | Master | System Integration | 60+ min |

---

## How to Use These Challenges

### For Interview Preparation
1. Pick a random challenge (mix difficulty levels)
2. Implement it without looking at examples
3. Test thoroughly
4. Explain your approach and decisions

### For Code Review Practice
1. Implement a challenge
2. Have someone review it
3. Discuss architecture and performance implications

### For Learning Path
1. Start with Beginner (1-3)
2. Move to Intermediate (4-10)
3. Progress to Advanced (11-15)
4. Master Expert (16-25)
5. Tackle Integration (26-30)

### For Team Interview Questions
Pick any 3-5 challenges and have candidate implement them in 2-3 hours. Observe their:
- Problem-solving approach
- Code quality
- Testing methodology
- Communication skills
- WordPress knowledge depth
