<?php
/**
 * TUTORIAL: Custom Rewrite Rules Implementation
 * 
 * This is a blank template to help you learn WordPress rewrite rules.
 * Use this file to code along and understand how URL rewriting works.
 * 
 * KEY CONCEPTS:
 * 1. Rewrite Tags: Custom placeholders like %mw_genre% that map URL parts to query variables
 * 2. Rewrite Rules: Regex patterns that match URLs and map them to WordPress queries
 * 3. Permalinks: User-friendly URLs built from post data and custom logic
 * 4. Regex Matching: Understanding $matches[1], $matches[2], etc. for URL segments
 * 
 * @package Your_Package
 * @since 1.0.0
 */

namespace Your_Namespace\Classes\Rewrite;

use RT_Movie_Library\Traits\Singleton as TraitsSingleton;


// Always check if WordPress is loaded
defined( 'ABSPATH' ) || exit;

/**
 * Class Rewrite_Rules
 * 
 * TUTORIAL GOALS:
 * - Understand WordPress rewrite system
 * - Learn how to register custom rewrite tags
 * - Learn how to register custom rewrite rules with regex
 * - Learn how to filter post type links
 * - Understand taxonomy term fetching and fallback handling
 * 
 * TYPICAL URL STRUCTURE WE'LL CREATE:
 * /movie/{genre-slug}/{post-name}-{post-id}/
 * /person/{career-slug}/{post-name}-{post-id}/
 */
class Rewrite_Rules {

	// TODO: Add the Singleton trait to enable single instance pattern
	// This ensures only one instance of this class exists throughout execution
    use TraitsSingleton;

	/**
	 * Define a fallback slug constant.
	 * This is used when a post has no assigned taxonomy terms.
	 * 
	 * TODO: Create a private constant called FALLBACK_SLUG with value 'uncategorized'
	 * HINT: Use const FALLBACK_SLUG = 'value';
	 */
	// private const FALLBACK_SLUG = ?;

    private const FALLBACK_SLUG = 'uncategorized';

	/**
	 * Define the genre taxonomy constant.
	 * 
	 * TODO: Create a private constant called TAXONOMY_GENRE with value 'rt-movie-genre'
	 */
	// private const TAXONOMY_GENRE = ?;
    private const TAXONOMY_GENRE = 'rt-movie-genre';

	/**
	 * Define the career taxonomy constant.
	 * 
	 * TODO: Create a private constant called TAXONOMY_CAREER with value 'rt-person-career'
	 */
	// private const TAXONOMY_CAREER = ?;
    private const TAXONOMY_CAREER = 'rt-person-career';

	/**
	 * Constructor: Hook into WordPress initialization.
	 * 
	 * TODO: Create a protected __construct() function
	 * 
	 * HINT: Inside the constructor, you need to hook into WordPress initialization:
	 * 1. add_action( 'init', array( $this, 'register_rewrite_tags' ) );
	 *    - This hook fires after WordPress core is loaded, perfect for registering rewrite rules
	 * 
	 * 2. add_action( 'init', array( $this, 'register_rewrite_rules' ) );
	 *    - Register the actual rewrite rules
	 * 
	 * 3. add_filter( 'post_type_link', array( $this, 'filter_post_type_link' ), 10, 4 );
	 *    - This filter allows us to customize how permalinks are generated for our CPTs
	 *    - Priority 10 is standard
	 *    - 4 parameters: $post_link, $post, $leavename, $sample
	 */
	// protected function __construct() {
	//     // TODO: Add the three hooks here
	// }

    protected function __construct() {
        add_action( 'init', array($this, 'register_rewrite_tags' ) );
        add_action( 'init', array( $this, 'register_rewrite_rules') );
        add_filter( 'post_type_link' , array( $this, 'filter_post_type_link' ), 10, 4 );
    }

	/**
	 * Register Custom Rewrite Tags.
	 * 
	 * THEORY: Rewrite tags are placeholders in your rewrite rules.
	 * They map URL segments to query variables that WordPress can understand.
	 * 
	 * Syntax: add_rewrite_tag( '%tag_name%', 'regex_pattern' );
	 * 
	 * The regex pattern determines what characters are allowed in this URL segment:
	 * - ([^/]+) = Match any character except forward slash (one or more times)
	 * - ([a-z0-9\-]+) = Match only lowercase letters, numbers, and hyphens
	 * - (\d+) = Match only digits (numbers)
	 * 
	 * TODO: Create a public function register_rewrite_tags() with return type void
	 * 
	 * Inside the function:
	 * 1. Add a rewrite tag for %mw_genre% with pattern '([^/]+)'
	 * 2. Add a rewrite tag for %mw_career% with pattern '([^/]+)'
	 * 
	 * HINT: Use add_rewrite_tag() WordPress function
	 */
	public function register_rewrite_tags(): void {
	    // TODO: Add rewrite tags
        add_rewrite_tag( '%mw_genre%', '([^/]+)' );
	}

	/**
	 * Register Custom Rewrite Rules.
	 * 
	 * THEORY: Rewrite rules are regex patterns that match URLs and tell WordPress
	 * how to query for posts. The matched groups ($matches[1], $matches[2]) become
	 * available in the second parameter.
	 * 
	 * Syntax:
	 * add_rewrite_rule(
	 *     'regex_pattern',
	 *     'query_string',
	 *     'position' ('top' or 'bottom')
	 * );
	 * 
	 * Example breakdown:
	 * Pattern: ^movie/([^/]+)/([^/]+)-(\d+)/?$
	 * - ^ = Start of URL
	 * - movie/ = Literal text "movie/"
	 * - ([^/]+) = Group 1: capture genre (anything except /)
	 * - / = Literal forward slash
	 * - ([^/]+) = Group 2: capture post name
	 * - - = Literal hyphen
	 * - (\d+) = Group 3: capture post ID (numbers only)
	 * - /? = Optional trailing slash
	 * - $ = End of URL
	 * 
	 * Query: index.php?post_type=rt-movie&mw_genre=$matches[1]&name=$matches[2]&p=$matches[3]
	 * - post_type=rt-movie: Tell WordPress to query for movie posts
	 * - mw_genre=$matches[1]: The custom taxonomy value from Group 1
	 * - name=$matches[2]: WordPress will use this to find the post by slug
	 * - p=$matches[3]: Post ID from Group 3
	 * 
	 * Position 'top' = Higher priority (checked first)
	 * Position 'bottom' = Lower priority (checked last)
	 * 
	 * TODO: Create a public function register_rewrite_rules() with return type void
	 * 
	 * Inside the function:
	 * 1. Add a rewrite rule for movie URLs:
	 *    Pattern: ^movie/([^/]+)/([^/]+)-(\d+)/?$
	 *    Query: index.php?post_type=rt-movie&mw_genre=$matches[1]&name=$matches[2]&p=$matches[3]
	 *    Position: 'top'
	 * 
	 * 2. Add a rewrite rule for person URLs:
	 *    Pattern: ^person/([^/]+)/([^/]+)-(\d+)/?$
	 *    Query: index.php?post_type=rt-person&mw_career=$matches[1]&name=$matches[2]&p=$matches[3]
	 *    Position: 'top'
	 * 
	 * HINT: Use add_rewrite_rule() WordPress function
	 */
	// public function register_rewrite_rules(): void {
	//     // TODO: Add rewrite rules for movies and persons
	// }

	/**
	 * Filter Post Type Link.
	 * 
	 * THEORY: WordPress calls the 'post_type_link' filter when generating permalinks.
	 * This is where we customize the actual URL structure for our posts.
	 * 
	 * The function receives:
	 * - $link (string): The default permalink
	 * - $post (\WP_Post): The post object
	 * - $leavename (bool): Whether to leave the post name in the URL (not used here)
	 * - $sample (bool): Whether this is a sample permalink
	 * 
	 * Our job: Check the post type and build a custom URL.
	 * 
	 * TODO: Create a public function filter_post_type_link() that:
	 * 
	 * Parameters:
	 * - string $link: The default link
	 * - \WP_Post $post: The post object
	 * Return: string (the modified link)
	 * 
	 * Inside:
	 * 1. Check if post type is 'rt-movie'
	 *    - If yes, return $this->build_movie_link( $post )
	 * 
	 * 2. Check if post type is 'rt-person'
	 *    - If yes, return $this->build_person_link( $post )
	 * 
	 * 3. Otherwise, return the original $link
	 * 
	 * HINT: Use if statements to check post types
	 */
	// public function filter_post_type_link( string $link, \WP_Post $post ): string {
	//     // TODO: Add logic here
	// }

	/**
	 * Build Movie Permalink.
	 * 
	 * THEORY: This private method constructs the actual URL string for movie posts.
	 * 
	 * Expected output: https://yoursite.com/movie/action/avengers-123/
	 * 
	 * Steps:
	 * 1. Get the first genre term slug for this movie
	 * 2. Use sprintf() to format the URL
	 * 3. Wrap with trailing slash for consistency
	 * 
	 * TODO: Create a private function build_movie_link( \WP_Post $post ): string
	 * 
	 * Inside:
	 * 1. Call $this->get_first_term_slug( $post->ID, self::TAXONOMY_GENRE )
	 *    Store result in $genre_slug variable
	 * 
	 * 2. Return trailingslashit() wrapping a sprintf() call:
	 *    sprintf(
	 *        '%s/movie/%s/%s-%d',
	 *        untrailingslashit( home_url() ),
	 *        $genre_slug,
	 *        $post->post_name,
	 *        $post->ID
	 *    )
	 * 
	 * HINT:
	 * - home_url() returns the site URL
	 * - untrailingslashit() removes trailing slashes
	 * - trailingslashit() adds a trailing slash
	 * - sprintf() formats strings with placeholders
	 * - $post->post_name is the post slug
	 * - $post->ID is the post ID
	 */
	// private function build_movie_link( \WP_Post $post ): string {
	//     // TODO: Get genre slug and build URL
	// }

	/**
	 * Build Person Permalink.
	 * 
	 * THEORY: Similar to build_movie_link, but for person CPT.
	 * 
	 * Expected output: https://yoursite.com/person/actor/tom-cruise-456/
	 * 
	 * TODO: Create a private function build_person_link( \WP_Post $post ): string
	 * 
	 * Inside:
	 * 1. Call $this->get_first_term_slug( $post->ID, self::TAXONOMY_CAREER )
	 *    Store result in $career_slug variable
	 * 
	 * 2. Return trailingslashit() wrapping a sprintf() call:
	 *    sprintf(
	 *        '%s/person/%s/%s-%d',
	 *        untrailingslashit( home_url() ),
	 *        $career_slug,
	 *        $post->post_name,
	 *        $post->ID
	 *    )
	 */
	// private function build_person_link( \WP_Post $post ): string {
	//     // TODO: Get career slug and build URL
	// }

	/**
	 * Get First Term Slug for a Taxonomy.
	 * 
	 * THEORY: This helper method:
	 * 1. Fetches terms assigned to a post for a specific taxonomy
	 * 2. Returns the first term's slug
	 * 3. Falls back to 'uncategorized' if no terms exist
	 * 
	 * TODO: Create a private function get_first_term_slug( int $post_id, string $taxonomy ): string
	 * 
	 * Inside:
	 * 1. Call wp_get_object_terms() with:
	 *    - $post_id
	 *    - $taxonomy
	 *    - An array of options:
	 *      array(
	 *          'orderby' => 'term_id',
	 *          'order'   => 'ASC',
	 *          'number'  => 1,
	 *      )
	 *    Store result in $terms variable
	 * 
	 * 2. Check for errors or empty results:
	 *    if ( is_wp_error( $terms ) || empty( $terms ) || ! isset( $terms[0]->slug ) ) {
	 *        return self::FALLBACK_SLUG;
	 *    }
	 * 
	 * 3. Get the slug from first term:
	 *    $slug = sanitize_title( (string) $terms[0]->slug );
	 * 
	 * 4. Return slug if valid, otherwise fallback:
	 *    return '' !== $slug ? $slug : self::FALLBACK_SLUG;
	 * 
	 * HINT:
	 * - is_wp_error() checks for WordPress errors
	 * - empty() checks if array has no items
	 * - isset() checks if array key exists
	 * - sanitize_title() cleans the slug
	 * - self:: accesses class constants
	 */
	// private function get_first_term_slug( int $post_id, string $taxonomy ): string {
	//     // TODO: Fetch term, validate it, return slug or fallback
	// }

	/**
	 * Flush Rewrite Rules on Activation.
	 * 
	 * THEORY: When a WordPress plugin is activated, rewrite rules must be flushed
	 * (regenerated) so WordPress picks up the new rules. Without this, URLs won't work.
	 * 
	 * TODO: Create a public static function flush_on_activate(): void
	 * 
	 * Inside:
	 * 1. Get the singleton instance: $instance = self::get_instance();
	 * 2. Call $instance->register_rewrite_tags();
	 * 3. Call $instance->register_rewrite_rules();
	 * 4. Flush rules: flush_rewrite_rules( false );
	 *    - false = soft flush (doesn't regenerate .htaccess)
	 *    - true = hard flush (regenerates .htaccess)
	 * 
	 * HINT: This is called from a plugin activation hook in your main plugin file
	 */
	// public static function flush_on_activate(): void {
	//     // TODO: Register tags, register rules, then flush
	// }

	/**
	 * Flush Rewrite Rules on Deactivation.
	 * 
	 * THEORY: When a plugin is deactivated, we should reset the rewrite rules
	 * back to WordPress defaults to avoid broken URLs.
	 * 
	 * TODO: Create a public static function flush_on_deactivate(): void
	 * 
	 * Inside:
	 * - Simply call: flush_rewrite_rules( false );
	 * 
	 * HINT: This is called from a plugin deactivation hook in your main plugin file
	 */
	// public static function flush_on_deactivate(): void {
	//     // TODO: Flush rewrite rules
	// }
}

/**
 * ============================================================================
 * ADDITIONAL LEARNING NOTES
 * ============================================================================
 * 
 * WORDPRESS REWRITE SYSTEM FLOW:
 * 1. User visits: /movie/action/avengers-123/
 * 2. WordPress tries to match this URL against rewrite rules
 * 3. Finds matching rule: ^movie/([^/]+)/([^/]+)-(\d+)/?$
 * 4. Extracts groups: [1]='action', [2]='avengers', [3]='123'
 * 5. Builds query: index.php?post_type=rt-movie&mw_genre=action&name=avengers&p=123
 * 6. Runs this query (internally, user doesn't see it)
 * 7. Returns the matched post
 * 
 * PERMALINK GENERATION FLOW:
 * 1. Code calls get_permalink( $post_id )
 * 2. WordPress triggers 'post_type_link' filter for CPT posts
 * 3. Our filter_post_type_link() is called
 * 4. We check post type and call build_movie_link() or build_person_link()
 * 5. Those methods fetch the taxonomy term and format the URL
 * 6. Returns formatted URL to user
 * 
 * IMPORTANT WP FUNCTIONS:
 * - add_rewrite_tag( $tag, $regex ): Register a rewrite tag placeholder
 * - add_rewrite_rule( $pattern, $query, $position ): Register a rewrite rule
 * - flush_rewrite_rules( $hard ): Regenerate .htaccess (flush cache)
 * - add_filter( $hook, $callback, $priority, $accepted_args ): Hook into filters
 * - wp_get_object_terms( $object_id, $taxonomies, $args ): Get terms for a post
 * - get_home_url(): Get site URL
 * - trailingslashit(): Add trailing slash
 * - untrailingslashit(): Remove trailing slash
 * - sanitize_title(): Clean a string for use as a slug
 * 
 * REGEX QUICK REFERENCE:
 * - ^ = Start of string
 * - $ = End of string
 * - () = Capturing group (accessible as $matches[1], $matches[2], etc)
 * - [^/] = Match any character except forward slash
 * - + = One or more of previous character
 * - * = Zero or more of previous character
 * - \d = Any digit (0-9)
 * - [a-z] = Any lowercase letter
 * - ? = Optional (zero or one of previous)
 * 
 * COMMON MISTAKES TO AVOID:
 * 1. Forgetting flush_rewrite_rules() after adding new rules
 * 2. Not using 'top' priority if your rule needs to run before defaults
 * 3. Forgetting sanitize_title() on user-submitted content (slugs)
 * 4. Not checking is_wp_error() before using term data
 * 5. Forgetting trailing slashes in URLs (use trailingslashit())
 * 
 * ============================================================================
 */
