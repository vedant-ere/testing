<?php
/**
 * Comments template.
 *
 * Renders native WordPress comments using movie review layout classes.
 *
 * @package ScreenTime
 */

if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="movie-comments">
	<?php if ( have_comments() ) : ?>
		<div class="movie-review-grid">
			<?php
			wp_list_comments(
				array(
					'style'       => 'div',
					'short_ping'  => true,
					'avatar_size' => 0,
					'callback'    => 'screentime_render_movie_review_comment',
				)
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( comments_open() ) : ?>
		<section class="movie-single-form">
			<?php comment_form( screentime_get_movie_review_form_args() ); ?>
		</section>
	<?php endif; ?>
</div>
