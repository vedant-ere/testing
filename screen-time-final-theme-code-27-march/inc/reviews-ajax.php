<?php
/**
 * AJAX handler for progressively loading movie reviews.
 *
 * @package ScreenTime
 */

add_action( 'wp_ajax_screentime_load_more_reviews', 'screentime_ajax_load_more_reviews' );
add_action( 'wp_ajax_nopriv_screentime_load_more_reviews', 'screentime_ajax_load_more_reviews' );

/**
 * Returns additional approved reviews for a movie post.
 *
 * @return void
 */
function screentime_ajax_load_more_reviews() {
	check_ajax_referer( 'screentime_load_reviews', 'nonce' );

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request nonce verified by check_ajax_referer().
	$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request nonce verified by check_ajax_referer().
	$page = isset( $_POST['page'] ) ? max( 1, absint( wp_unslash( $_POST['page'] ) ) ) : 1;

	if ( $post_id <= 0 || 'rt-movie' !== get_post_type( $post_id ) ) {
		wp_send_json_error();
	}

	$per_page = 4;
	$offset   = ( $page - 1 ) * $per_page;

	$comments = get_comments(
		array(
			'post_id' => $post_id,
			'status'  => 'approve',
			'parent'  => 0,
			'order'   => 'DESC',
			'number'  => $per_page,
			'offset'  => $offset,
		)
	);

	ob_start();

	foreach ( $comments as $comment ) {
		$author = get_comment_author( $comment );
		$icon   = strtoupper( substr( wp_strip_all_tags( $author ), 0, 1 ) );
		$date   = get_comment_date( 'F j, Y', $comment );
		$text   = wp_strip_all_tags( $comment->comment_content );
		?>
		<article class="movie-review-card">
			<p class="movie-review-card__author">
				<span class="movie-review-card__icon"><?php echo esc_html( $icon ); ?></span>
				<?php echo esc_html( $author ); ?>
			</p>
			<p class="movie-review-card__text"><?php echo esc_html( $text ); ?></p>
			<p class="movie-review-card__date"><?php echo esc_html( $date ); ?></p>
		</article>
		<?php
	}

	wp_send_json_success(
		array(
			'html'  => (string) ob_get_clean(),
			'count' => count( $comments ),
		)
	);
}
