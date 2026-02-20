<?php
/**
 * Review submission handlers for single movie page forms.
 *
 * Keeps the custom review UI while routing submissions through WordPress
 * comment APIs with nonce validation and safe redirects.
 *
 * @package ScreenTime
 */

add_action( 'admin_post_nopriv_screentime_submit_review', 'screentime_handle_review_submission' );
add_action( 'admin_post_screentime_submit_review', 'screentime_handle_review_submission' );

/**
 * Handles custom movie review form submissions.
 *
 * Validates nonce/input, submits via wp_handle_comment_submission(), and
 * redirects back to the movie review section (or specific comment link).
 *
 * @return void
 */
function screentime_handle_review_submission() {
	$nonce = isset( $_POST['screentime_review_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['screentime_review_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'screentime_submit_review' ) ) {
		wp_die( esc_html__( 'Invalid review request.', 'screen-time' ) );
	}

	$post_id = isset( $_POST['comment_post_ID'] ) ? absint( wp_unslash( $_POST['comment_post_ID'] ) ) : 0;

	if ( $post_id <= 0 || 'rt-movie' !== get_post_type( $post_id ) ) {
		wp_die( esc_html__( 'Invalid movie review target.', 'screen-time' ) );
	}

	if ( ! comments_open( $post_id ) ) {
		wp_safe_redirect( get_permalink( $post_id ) . '#reviews' );
		exit;
	}

	$comment_data = array(
		'comment_post_ID'      => $post_id,
		'comment_parent'       => isset( $_POST['comment_parent'] ) ? absint( wp_unslash( $_POST['comment_parent'] ) ) : 0,
		'comment_author'       => isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : '',
		'comment_author_email' => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'comment_author_url'   => isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '',
		'comment_content'      => isset( $_POST['comment'] ) ? trim( (string) wp_unslash( $_POST['comment'] ) ) : '',
		'user_id'              => get_current_user_id(),
	);

	$comment = wp_handle_comment_submission( $comment_data );

	if ( is_wp_error( $comment ) ) {
		wp_safe_redirect( add_query_arg( 'review_error', rawurlencode( $comment->get_error_message() ), get_permalink( $post_id ) . '#reviews' ) );
		exit;
	}

	/** This action mirrors wp-comments-post.php cookie behavior. */
	do_action( 'set_comment_cookies', $comment, wp_get_current_user() );

	$redirect_url = get_comment_link( $comment );
	if ( ! is_string( $redirect_url ) || '' === $redirect_url ) {
		$redirect_url = get_permalink( $post_id ) . '#reviews';
	}

	wp_safe_redirect( $redirect_url );
	exit;
}
