<?php
/**
 * Pattern: Cast & Crew Section
 *
 * Title: Cast and Crew
 * Slug: screen-time-fse/cast-crew-section
 * Description: Shows the cast & crew for the current movie in a card layout with a static View All CTA.
 * Categories: screen-time-fse
 * Block Types: core/post-content
 * Post Types: rt-movie
 * Viewport Width: 1440
 * Inserter: false
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

/*
 * Fetch up to 8 cast/crew members for the current post.
 * Uses the rt-movie-meta-crew-* meta structure stored by the plugin.
 */
$movie_id    = get_the_ID();
$crew_meta   = is_int( $movie_id ) ? get_post_meta( $movie_id, 'rt-movie-meta-crew', true ) : array();
$crew_meta   = is_array( $crew_meta ) ? $crew_meta : array();
$cards       = array();
$view_all_url = $movie_id ? add_query_arg( 'view', 'cast-crew', get_permalink( $movie_id ) ) : '#';

// Build card data from the crew meta array (plugin structure).
foreach ( $crew_meta as $role_slug => $person_ids ) {
	if ( ! is_array( $person_ids ) ) {
		continue;
	}
	foreach ( $person_ids as $person_id ) {
		$person_id = absint( $person_id );
		if ( 0 === $person_id || 'publish' !== get_post_status( $person_id ) ) {
			continue;
		}
		$thumb_url = '';
		$thumb_id  = get_post_thumbnail_id( $person_id );
		if ( $thumb_id ) {
			$src = wp_get_attachment_image_url( $thumb_id, 'medium' );
			if ( $src ) {
				$thumb_url = $src;
			}
		}
		$cards[] = array(
			'name'  => get_the_title( $person_id ),
			'image' => $thumb_url,
			'link'  => get_permalink( $person_id ),
		);
		if ( count( $cards ) >= 8 ) {
			break 2;
		}
	}
}

if ( empty( $cards ) ) {
	return; // No cast/crew data — skip section silently.
}
?>

<!-- wp:group {"className":"movie-single-section","id":"cast-crew","style":{"spacing":{"padding":{"top":"var:preset|spacing|64","bottom":"var:preset|spacing|64"}}},"layout":{"type":"constrained","contentSize":"1440px","justifyContent":"center"}} -->
<div class="wp-block-group movie-single-section" id="cast-crew">

<!-- wp:group {"style":{"spacing":{"padding":{"left":"var:preset|spacing|16","right":"var:preset|spacing|16","bottom":"var:preset|spacing|32"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap","verticalAlignment":"center"}} -->
<div class="wp-block-group">

<!-- wp:heading {"level":2,"className":"section-title","style":{"typography":{"fontFamily":"var(--wp--preset--font-family--big-shoulders)","fontSize":"var(--wp--preset--font-size--4xl)","fontWeight":"400","lineHeight":"1.1"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<h2 class="wp-block-heading section-title">Cast &amp; Crew</h2>
<!-- /wp:heading -->

<!-- wp:html -->
<a href="<?php echo esc_url( $view_all_url ); ?>" class="view-all-link" aria-label="View all cast and crew">
	View All <span aria-hidden="true">&#8594;</span>
</a>
<!-- /wp:html -->

</div>
<!-- /wp:group -->

<!-- wp:html -->
<div
	class="movie-cast-grid"
	style="padding-left:var(--wp--preset--spacing--16);padding-right:var(--wp--preset--spacing--16)"
	aria-label="Cast and crew members"
>
	<?php foreach ( $cards as $card ) : ?>
		<article class="movie-cast-card">
			<?php if ( ! empty( $card['image'] ) ) : ?>
				<a href="<?php echo esc_url( $card['link'] ); ?>" tabindex="-1" aria-hidden="true">
					<img
						src="<?php echo esc_url( $card['image'] ); ?>"
						alt="<?php echo esc_attr( $card['name'] ); ?>"
						width="280"
						height="248"
						loading="lazy"
					>
				</a>
			<?php endif; ?>
			<h3>
				<a href="<?php echo esc_url( $card['link'] ); ?>"><?php echo esc_html( $card['name'] ); ?></a>
			</h3>
		</article>
	<?php endforeach; ?>
</div>
<!-- /wp:html -->

<!-- wp:html -->
<div style="padding-left:var(--wp--preset--spacing--16);padding-right:var(--wp--preset--spacing--16);margin-top:var(--wp--preset--spacing--32)">
	<a href="<?php echo esc_url( $view_all_url ); ?>" class="view-all-link view-all-link--bottom" aria-label="View all cast and crew">
		View All <span aria-hidden="true">&#8594;</span>
	</a>
</div>
<!-- /wp:html -->

</div>
<!-- /wp:group -->
