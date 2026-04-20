<?php
/**
 * Screen Time FSE — theme bootstrap.
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

// ─── Theme Constants ─────────────────────────────────────────────────────────

if ( ! defined( 'SCREENTIME_FSE_VERSION' ) ) {
	define( 'SCREENTIME_FSE_VERSION', '1.0.0' );
}

if ( ! defined( 'SCREENTIME_FSE_PATH' ) ) {
	define( 'SCREENTIME_FSE_PATH', get_template_directory() );
}

if ( ! defined( 'SCREENTIME_FSE_URI' ) ) {
	define( 'SCREENTIME_FSE_URI', get_template_directory_uri() );
}

/**
 * Register file-based block patterns explicitly.
 *
 * WordPress auto-discovery from patterns/ can silently fail.
 * Explicit registration via register_block_pattern() is reliable.
 *
 * @return void
 */
function screentime_fse_register_patterns(): void {
	$patterns = array(
		'screen-time-fse/screen-time-header'    => array(
			'title'      => __( 'Screen Time Header', 'screen-time-fse' ),
			'categories' => array( 'header' ),
			'inserter'   => false,
			'file'       => '/patterns/screen-time-header.php',
		),
		'screen-time-fse/fse-footer'            => array(
			'title'      => __( 'FSE Footer', 'screen-time-fse' ),
			'categories' => array( 'footer' ),
			'inserter'   => false,
			'file'       => '/patterns/fse-footer.php',
		),
		'screen-time-fse/movie-card-synced'     => array(
			'title'    => __( 'Movie Card Synced', 'screen-time-fse' ),
			'inserter' => false,
			'file'     => '/patterns/movie-card-synced.php',
		),
		'screen-time-fse/person-card-cast-crew' => array(
			'title'    => __( 'Person Card Cast and Crew', 'screen-time-fse' ),
			'inserter' => false,
			'file'     => '/patterns/person-card-cast-crew.php',
		),
	);

	foreach ( $patterns as $slug => $args ) {
		ob_start();
		require SCREENTIME_FSE_PATH . $args['file'];
		$content = ob_get_clean();

		register_block_pattern(
			$slug,
			array(
				'title'      => $args['title'],
				'categories' => $args['categories'] ?? array(),
				'inserter'   => $args['inserter'],
				'content'    => $content,
			)
		);
	}
}
add_action( 'init', 'screentime_fse_register_patterns' );

/**
 * Enqueue front-end styles for the theme.
 *
 * @return void
 */
function screentime_fse_enqueue_assets(): void {

	wp_enqueue_style(
		'screentime-fse-style',
		get_stylesheet_uri(),
		array(),
		SCREENTIME_FSE_VERSION
	);

	wp_enqueue_style(
		'screentime-fse-responsive',
		SCREENTIME_FSE_URI . '/assets/css/responsive.css',
		array( 'screentime-fse-style' ),
		SCREENTIME_FSE_VERSION
	);

	wp_enqueue_style(
		'screentime-mobile-bootstrap',
		SCREENTIME_FSE_URI . '/assets/css/mobile-bootstrap.css',
		array( 'screentime-fse-style' ),
		SCREENTIME_FSE_VERSION
	);

	wp_enqueue_style(
		'screentime-mobile-footer',
		SCREENTIME_FSE_URI . '/assets/css/mobile-footer.css',
		array( 'screentime-fse-style' ),
		SCREENTIME_FSE_VERSION
	);

	if ( is_singular( 'rt-movie' ) ) {
		wp_enqueue_style(
			'screentime-single-movie',
			SCREENTIME_FSE_URI . '/assets/css/pages/single-movie.css',
			array( 'screentime-fse-responsive' ),
			SCREENTIME_FSE_VERSION
		);
	} elseif ( is_post_type_archive( 'rt-movie' ) ) {
		wp_enqueue_style(
			'screentime-archive-movie',
			SCREENTIME_FSE_URI . '/assets/css/pages/archive.css',
			array( 'screentime-fse-responsive' ),
			SCREENTIME_FSE_VERSION
		);
	} elseif ( is_front_page() || is_home() ) {
		wp_enqueue_style(
			'screentime-home',
			SCREENTIME_FSE_URI . '/assets/css/pages/home.css',
			array( 'screentime-fse-responsive' ),
			SCREENTIME_FSE_VERSION
		);
	}

	// ── JavaScript ─────────────────────────────────────────────

	/**
	 * Mobile header drawer and accordion.
	 * Loaded in footer (true) so DOM is ready on execution.
	 */
	wp_enqueue_script(
		'screentime-mobile-header',
		SCREENTIME_FSE_URI . '/assets/js/mobile-header.js',
		array(), // No dependencies — vanilla JS, no jQuery.
		SCREENTIME_FSE_VERSION,
		true
	);

	/**
	 * Search overlay open/close.
	 * Depends on mobile-header for the shared `.header-search-trigger` selector.
	 */
	wp_enqueue_script(
		'screentime-search-overlay',
		SCREENTIME_FSE_URI . '/assets/js/search-overlay.js',
		array( 'screentime-mobile-header' ),
		SCREENTIME_FSE_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'screentime_fse_enqueue_assets' );

/**
 * Enqueue block editor scripts.
 *
 * @return void
 */
function screentime_fse_editor_assets(): void {

	wp_enqueue_script(
		'screentime-block-variations',
		SCREENTIME_FSE_URI . '/assets/js/block-variations.js',
		array(
			'wp-blocks',
			'wp-dom-ready',
			'wp-edit-post',
			'wp-i18n',
		),
		SCREENTIME_FSE_VERSION,
		true
	);

	wp_set_script_translations(
		'screentime-block-variations',
		'screen-time-fse',
		SCREENTIME_FSE_PATH . '/languages'
	);
}
add_action( 'enqueue_block_editor_assets', 'screentime_fse_editor_assets' );

/**
 * Register rt-movie basic meta keys so the Block Bindings API
 * can read them in the Site Editor and on the frontend.
 *
 * @return void
 */
function screentime_fse_register_movie_meta(): void {
	$meta_keys = array(
		'rt-movie-meta-basic-rating'         => 'string',
		'rt-movie-meta-basic-runtime'        => 'string',
		'rt-movie-meta-basic-release-date'   => 'string',
		'rt-movie-meta-basic-content-rating' => 'string',
	);

	foreach ( $meta_keys as $key => $type ) {
		register_post_meta(
			'rt-movie',
			$key,
			array(
				'type'         => $type,
				'single'       => true,
				'show_in_rest' => true,
			)
		);
	}
}
add_action( 'init', 'screentime_fse_register_movie_meta' );

/**
 * Make the Cast & Crew Query Loop dynamic on single rt-movie pages.
 *
 * @param array $query The parsed block query vars.
 * @return array       Modified query vars.
 */
function screentime_fse_filter_cast_query( array $query ): array {

	if (
		! is_singular( 'rt-movie' ) ||
		empty( $query['post_type'] ) ||
		'rt-person' !== $query['post_type']
	) {
		return $query;
	}

	$movie_id = get_the_ID();

	$crew_meta_keys = array(
		'rt-movie-meta-crew-director',
		'rt-movie-meta-crew-producer',
		'rt-movie-meta-crew-writer',
		'rt-movie-meta-crew-actor',
	);

	$person_ids = array();

	foreach ( $crew_meta_keys as $key ) {
		$raw = get_post_meta( $movie_id, $key, true );
		if ( empty( $raw ) ) {
			continue;
		}
		$decoded = is_string( $raw ) ? json_decode( $raw, true ) : (array) $raw;
		if ( is_array( $decoded ) ) {
			$person_ids = array_merge( $person_ids, array_map( 'intval', $decoded ) );
		}
	}

	$person_ids = array_unique( array_filter( $person_ids ) );

	$query['post__in'] = ! empty( $person_ids ) ? $person_ids : array( 0 );

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'screentime_fse_filter_cast_query', 10, 1 );

/**
 * Modify rt-movie post type arguments to use rt-movie as the archive slug.
 *
 * @param array  $args      Post type arguments.
 * @param string $post_type Post type name.
 * @return array
 */
function screentime_fse_modify_movie_slugs( array $args, string $post_type ): array {
	if ( 'rt-movie' === $post_type ) {
		$args['has_archive'] = 'rt-movie';
		$args['rewrite']     = array(
			'slug'       => 'rt-movie',
			'with_front' => false,
		);
	}
	return $args;
}
add_filter( 'register_post_type_args', 'screentime_fse_modify_movie_slugs', 10, 2 );

/**
 * Dynamically replace the directors group block with linked person names.
 *
 * Intercepts any group block carrying the CSS class "movie-directors-group"
 * on single rt-movie pages. Reads rt-movie-meta-crew-director (JSON array
 * of rt-person post IDs) and rebuilds the block HTML with actual names
 * linked to their person archive pages.
 *
 * Why render_block and not Block Bindings:
 * The Block Bindings API (core/post-meta source) maps a meta key to a
 * block attribute 1:1 as a string. The director meta is a JSON array of
 * IDs that must be resolved to post titles — this requires PHP logic that
 * the Bindings API cannot express. render_block is the correct hook.
 *
 * @param string $block_content The rendered HTML for this block.
 * @param array  $block         The parsed block array.
 * @return string Modified block HTML.
 */
function screentime_fse_render_directors( string $block_content, array $block ): string {
	// Only run on single rt-movie pages — bail early everywhere else.
	if ( ! is_singular( 'rt-movie' ) ) {
		return $block_content;
	}

	// Identify the block by its className attribute. This is more reliable
	// than matching on block name (core/group) which is too broad.
	$class = $block['attrs']['className'] ?? '';
	if ( ! str_contains( $class, 'movie-directors-group' ) ) {
		return $block_content;
	}

	$movie_id = get_the_ID();
	if ( ! $movie_id ) {
		return $block_content;
	}

	$raw = get_post_meta( $movie_id, 'rt-movie-meta-crew-director', true );
	if ( empty( $raw ) ) {
		return $block_content;
	}

	// Meta is stored as a JSON-encoded array by the plugin.
	$person_ids = is_string( $raw ) ? json_decode( $raw, true ) : (array) $raw;
	if ( ! is_array( $person_ids ) || empty( $person_ids ) ) {
		return $block_content;
	}

	// Resolve each ID to a linked name. Skip unpublished or missing posts.
	$links = array();
	foreach ( $person_ids as $raw_id ) {
		$id   = absint( $raw_id );
		$post = get_post( $id );

		if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
			continue;
		}

		$links[] = sprintf(
'<a href="%s">%s</a>',
esc_url( get_permalink( $id ) ),
esc_html( $post->post_title )
		);
	}

	if ( empty( $links ) ) {
		return $block_content;
	}

	// Rebuild the group block preserving its wrapper class.
	// The inner paragraphs carry no special block attributes — plain HTML output.
	return sprintf(
'<div class="wp-block-group movie-directors-group">'
. '<p>%s</p>'
. '<p>%s</p>'
. '</div>',
esc_html__( 'Directors:', 'screen-time-fse' ),
implode( ', ', $links ) // Already per-link escaped above.
);
}
add_filter( 'render_block', 'screentime_fse_render_directors', 10, 2 );
