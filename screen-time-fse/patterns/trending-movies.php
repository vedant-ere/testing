<?php
/**
 * Pattern: Trending Movies Grid
 *
 * Title: Trending Movies
 * Slug: screen-time-fse/trending-movies
 * Description: Query loop grid filtered by the "trending" rt-movie-label term.
 * Categories: screen-time-fse, query
 * Viewport Width: 1440
 * Inserter: true
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

$trending_term = taxonomy_exists( 'rt-movie-label' )
	? get_term_by( 'slug', 'trending', 'rt-movie-label' )
	: false;

$trending_id = ( $trending_term && ! is_wp_error( $trending_term ) )
	? absint( $trending_term->term_id )
	: 0;

$tax_query_json = $trending_id > 0
	? '{"rt-movie-label":[' . $trending_id . ']}'
	: '{}';
?>

<!-- wp:group {"className":"movie-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|64","bottom":"var:preset|spacing|96"}}},"layout":{"type":"constrained","contentSize":"1440px","justifyContent":"center"}} -->
<div class="wp-block-group movie-section">

<!-- wp:group {"style":{"spacing":{"padding":{"left":"var:preset|spacing|16","right":"var:preset|spacing|16"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group">

<!-- wp:heading {"level":2,"className":"section-title","style":{"typography":{"fontFamily":"var(--wp--preset--font-family--big-shoulders)","fontWeight":"400","lineHeight":"1.1","fontSize":"var(--wp--preset--font-size--4xl)"},"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|32"}}}} -->
<h2 class="wp-block-heading section-title">Trending Now</h2>
<!-- /wp:heading -->

</div>
<!-- /wp:group -->

<!-- wp:query {"queryId":11,"query":{"perPage":6,"pages":0,"offset":0,"postType":"rt-movie","order":"desc","orderBy":"date","inherit":false,"taxQuery":<?php echo $tax_query_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — integer-only JSON fragment. ?>},"className":"movie-query","style":{"spacing":{"padding":{"left":"var:preset|spacing|16","right":"var:preset|spacing|16"}}}} -->
<div class="wp-block-query movie-query">

<!-- wp:post-template {"className":"movie-grid","layout":{"type":"grid","columnCount":3}} -->

<!-- wp:group {"className":"movie-card","style":{"color":{"background":"var(--wp--preset--color--surface-card)"},"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group movie-card has-surface-card-background-color has-background">

<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"384/411","className":"movie-card__poster","style":{"layout":{"selfStretch":"fill","flexSize":null}}} /-->

<!-- wp:group {"className":"movie-card__content","style":{"spacing":{"padding":{"top":"var:preset|spacing|24","right":"var:preset|spacing|16","bottom":"var:preset|spacing|32","left":"var:preset|spacing|16"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group movie-card__content">

<!-- wp:group {"className":"movie-card__row","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group movie-card__row">

<!-- wp:post-title {"level":3,"isLink":true,"className":"movie-card__title","style":{"typography":{"fontSize":"1.5rem","fontWeight":"600","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} /-->

<!-- wp:paragraph {"className":"movie-card__runtime","metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"rt-movie-meta-basic-runtime"}}}},"style":{"typography":{"fontSize":"var(--wp--preset--font-size--sm)"},"color":{"text":"rgba(255,255,255,0.5)"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<p class="wp-block-paragraph movie-card__runtime"></p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

<!-- wp:post-terms {"term":"rt-movie-genre","className":"movie-card__subtitle","style":{"typography":{"fontSize":"var(--wp--preset--font-size--sm)"},"color":{"text":"rgba(255,255,255,0.5)"},"spacing":{"margin":{"top":"var:preset|spacing|8","bottom":"0"}}}} /-->

</div>
<!-- /wp:group -->

</div>
<!-- /wp:group -->

<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"placeholder":"No trending movies found."} -->
<p>No trending movies found.</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results -->

</div>
<!-- /wp:query -->

</div>
<!-- /wp:group -->
