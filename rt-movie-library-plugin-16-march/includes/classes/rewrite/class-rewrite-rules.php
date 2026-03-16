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
	}

	/**
	 * Register custom rewrite tags.
	 *
	 * @return void
	 */
	public function register_rewrite_tags(): void {
		add_rewrite_tag( '%mw_genre%', '([^/]+)' );
		add_rewrite_tag( '%mw_career%', '([^/]+)' );
	}

	/**
	 * Register custom movie/person URL rewrite rules.
	 *
	 * @return void
	 */
	public function register_rewrite_rules(): void {
		add_rewrite_rule(
			'^movie/([^/]+)/([^/]+)-(\d+)/?$',
			'index.php?post_type=rt-movie&mw_genre=$matches[1]&name=$matches[2]&p=$matches[3]',
			'top'
		);

		add_rewrite_rule(
			'^person/([^/]+)/([^/]+)-(\d+)/?$',
			'index.php?post_type=rt-person&mw_career=$matches[1]&name=$matches[2]&p=$matches[3]',
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
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
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
			return self::FALLBACK_SLUG;
		}

		$slug = sanitize_title( (string) $terms[0]->slug );

		return '' !== $slug ? $slug : self::FALLBACK_SLUG;
	}
}
