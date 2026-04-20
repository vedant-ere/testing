<?php
/**
 * Custom rewrite rules for Movie and Person CPTs.
 *
 * @package RT_Movie_Library
 * @since   1.0.0
 */

namespace RT_Movie_Library\Classes\Rewrite;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rewrite_Rules
 */
class Rewrite_Rules {

	use Singleton;

	/**
	 * Fallback slug for missing taxonomy terms.
	 *
	 * @var string
	 */
	private const FALLBACK_SLUG = 'uncategorized';

	/**
	 * Movie genre taxonomy.
	 *
	 * @var string
	 */
	private const TAXONOMY_GENRE = 'rt-movie-genre';

	/**
	 * Person career taxonomy.
	 *
	 * @var string
	 */
	private const TAXONOMY_CAREER = 'rt-person-career';

	/**
	 * Bootstrap rewrite hooks.
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'register_rewrite_tags' ) );
		add_action( 'init', array( $this, 'register_rewrite_rules' ) );
		add_filter( 'post_type_link', array( $this, 'filter_post_type_link' ), 10, 4 );
		add_action( 'template_redirect', array( $this, 'validate_url_integrity' ) );
	}

	/**
	 * Register custom rewrite tags.
	 *
	 * Custom query vars (rt_slug, rt_genre, rt_career) that WP_Query does
	 * not act on, so the post always resolves by `p` (ID) alone and
	 * validation happens in a single template_redirect hook.
	 *
	 * @return void
	 */
	public function register_rewrite_tags(): void {
		add_rewrite_tag( '%rt_genre%', '([^/]+)' );
		add_rewrite_tag( '%rt_career%', '([^/]+)' );
		add_rewrite_tag( '%rt_slug%', '([^/]+)' );
	}

	/**
	 * Register custom movie/person URL rewrite rules.
	 *
	 * @return void
	 */
	public function register_rewrite_rules(): void {
		add_rewrite_rule(
			'^movie/([^/]+)/([^/]+)-(\d+)/?$',
			'index.php?post_type=rt-movie&rt_genre=$matches[1]&rt_slug=$matches[2]&p=$matches[3]',
			'top'
		);

		add_rewrite_rule(
			'^person/([^/]+)/([^/]+)-(\d+)/?$',
			'index.php?post_type=rt-person&rt_career=$matches[1]&rt_slug=$matches[2]&p=$matches[3]',
			'top'
		);
	}

	/**
	 * Filter permalink generation for movie/person posts.
	 *
	 * @param string   $link      Default permalink.
	 * @param \WP_Post $post      Post object.
	 * @return string
	 */
	public function filter_post_type_link( string $link, \WP_Post $post ): string {
		if ( 'rt-movie' === $post->post_type ) {
			return $this->build_movie_link( $post );
		}

		if ( 'rt-person' === $post->post_type ) {
			return $this->build_person_link( $post );
		}

		return $link;
	}

	/**
	 * Flush rewrite rules on activation.
	 *
	 * @return void
	 */
	public static function flush_on_activate(): void {
		$instance = self::get_instance();
		$instance->register_rewrite_tags();
		$instance->register_rewrite_rules();

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		flush_rewrite_rules( false );
	}

	/**
	 * Flush rewrite rules on deactivation.
	 *
	 * @return void
	 */
	public static function flush_on_deactivate(): void {
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		flush_rewrite_rules( false );
	}

	/**
	 * Validate URL matches actual post taxonomy and slug, return 404 if not.
	 *
	 * Because rewrite rules use `rt_slug` (a custom query var) instead of
	 * WordPress's built-in `name`, WP_Query always resolves the post by
	 * `p` (ID) alone.  This single hook then validates both the taxonomy
	 * slug and the post name — no canonical redirect filter needed.
	 *
	 * @return void
	 */
	public function validate_url_integrity(): void {
		global $wp_query;

		// Only validate on singular custom post pages.
		if ( ! is_singular( array( 'rt-movie', 'rt-person' ) ) ) {
			return;
		}

		if ( is_404() ) {
			return;
		}

		$post_id = (int) $wp_query->get( 'p' );

		if ( ! $post_id ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		$url_slug = (string) $wp_query->get( 'rt_slug' );

		// Validate post name from URL against actual post_name.
		if ( '' !== $url_slug && $post->post_name !== $url_slug ) {
			$wp_query->set_404();
			status_header( 404 );
			return;
		}

		if ( 'rt-movie' === $post->post_type ) {
			$expected_genre = $this->get_first_term_slug( $post->ID, self::TAXONOMY_GENRE );
			$actual_genre   = (string) $wp_query->get( 'rt_genre' );

			if ( $actual_genre !== $expected_genre ) {
				$wp_query->set_404();
				status_header( 404 );
			}
		}

		if ( 'rt-person' === $post->post_type ) {
			$expected_career = $this->get_first_term_slug( $post->ID, self::TAXONOMY_CAREER );
			$actual_career   = (string) $wp_query->get( 'rt_career' );

			if ( $actual_career !== $expected_career ) {
				$wp_query->set_404();
				status_header( 404 );
			}
		}
	}

	/**
	 * Build movie permalink.
	 *
	 * @param \WP_Post $post Movie post object.
	 * @return string
	 */
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

	/**
	 * Build person permalink.
	 *
	 * @param \WP_Post $post Person post object.
	 * @return string
	 */
	private function build_person_link( \WP_Post $post ): string {
		$career_slug = $this->get_first_term_slug( $post->ID, self::TAXONOMY_CAREER );

		return trailingslashit(
			sprintf(
				'%s/person/%s/%s-%d',
				untrailingslashit( home_url() ),
				$career_slug,
				$post->post_name,
				$post->ID
			)
		);
	}

	/**
	 * Get first term slug for a taxonomy assignment.
	 *
	 * Uses a static cache to avoid repeated DB queries when get_permalink()
	 * is called for every post on admin list screens.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
	private function get_first_term_slug( int $post_id, string $taxonomy ): string {
		static $cache = array();

		$cache_key = $post_id . ':' . $taxonomy;

		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
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
			$cache[ $cache_key ] = self::FALLBACK_SLUG;
			return self::FALLBACK_SLUG;
		}

		$slug = sanitize_title( (string) $terms[0]->slug );
		$slug = '' !== $slug ? $slug : self::FALLBACK_SLUG;

		$cache[ $cache_key ] = $slug;

		return $slug;
	}
}
