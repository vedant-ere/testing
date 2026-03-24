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
	if (
		! isset( $_POST['screentime_review_nonce'] ) ||
		! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['screentime_review_nonce'] ) ),
			'screentime_submit_review'
		)
	) {
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

	// Match wp-comments-post.php behavior for comment cookie handling context.
	if ( ! defined( 'WP_COMMENT_POST' ) ) {
		define( 'WP_COMMENT_POST', true );
	}

	// Build native-like comment POST payload expected by core comment APIs.
	$_POST['comment_post_ID'] = $post_id;
	$_POST['comment_parent']  = isset( $_POST['comment_parent'] ) ? absint( wp_unslash( $_POST['comment_parent'] ) ) : 0;
	$_POST['author']          = isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : '';
	$_POST['email']           = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$_POST['url']             = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
	$_POST['comment']         = isset( $_POST['comment'] ) ? trim( wp_kses_post( (string) wp_unslash( $_POST['comment'] ) ) ) : '';
	$_POST['user_ID']         = get_current_user_id();

	$comment = wp_handle_comment_submission( $_POST );

	if ( is_wp_error( $comment ) ) {
		wp_safe_redirect(
			add_query_arg(
				'review_error',
				rawurlencode( $comment->get_error_message() ),
				get_permalink( $post_id ) . '#reviews'
			)
		);
		exit;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above for this request.
	$cookies_consent = isset( $_POST['wp-comment-cookies-consent'] );

	// Set comment cookies explicitly so "remember me" works reliably on custom admin-post flow.
	wp_set_comment_cookies( $comment, wp_get_current_user(), $cookies_consent );

	/** This action mirrors wp-comments-post.php hook behavior for plugin compatibility. */
	do_action( 'set_comment_cookies', $comment, wp_get_current_user(), $cookies_consent );

	$redirect_url = get_comment_link( $comment );
	if ( ! is_string( $redirect_url ) || '' === $redirect_url ) {
		$redirect_url = get_permalink( $post_id ) . '#reviews';
	}

	wp_safe_redirect( $redirect_url );
	exit;
}
