<?php
/**
 * Title: Movie Card Synced
 * Slug: screen-time-fse/movie-card-synced
 * Inserter: false
 *
 * Movie Card pattern markup.
 *
 * @package ScreenTimeFSE
 */

?>
<!-- wp:group {"className":"movie-card-fse","layout":{"type":"constrained","justifyContent":"left","wideSize":"","contentSize":"384px"}} -->
<div class="wp-block-group movie-card-fse"><!-- wp:post-featured-image {"width":"384px","height":"411px","sizeSlug":"full"} /-->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|sm","bottom":"var:preset|spacing|sm","left":"var:preset|spacing|sm","right":"var:preset|spacing|sm"}}},"backgroundColor":"footer-bg","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-footer-bg-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--sm);padding-right:var(--wp--preset--spacing--sm);padding-bottom:var(--wp--preset--spacing--sm);padding-left:var(--wp--preset--spacing--sm)"><!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|2xs","bottom":"var:preset|spacing|2xs"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--2-xs);padding-bottom:var(--wp--preset--spacing--2-xs)"><!-- wp:post-title {"isLink":true,"fontFamily":"heebo"} /-->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"rt-movie-meta-basic-runtime"}}}},"style":{"elements":{"link":{"color":{"text":"var:preset|color|text-muted"}}}},"textColor":"text-muted","fontFamily":"heebo"} -->
<p class="has-text-muted-color has-text-color has-link-color has-heebo-font-family"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"rt-movie-meta-basic-release-date"}}}},"style":{"typography":{"fontStyle":"normal","fontWeight":"200"},"elements":{"link":{"color":{"text":"var:preset|color|divider"}}}},"textColor":"divider","fontFamily":"heebo"} -->
<p class="has-divider-color has-text-color has-link-color has-heebo-font-family"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->