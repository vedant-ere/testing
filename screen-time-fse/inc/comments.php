<?php
/**
 * Comment form customisation — strips unneeded fields on movie review forms.
 *
 * The single-rt-movie template uses the core/comments block which renders
 * via comment_form(). These filters clean up the form so visitors only see
 * the textarea and submit button, matching the design of the classical theme.
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Removes the Name, Email, Website, and "save me" cookie fields from the
 * comment form on single movie pages.
 *
 * @param array $fields Default comment form fields.
 * @return array
 * @since 1.0.0
 */
function screentime_fse_filter_comment_fields( array $fields ) {
	if ( is_singular( 'rt-movie' ) ) {
		unset( $fields['author'], $fields['email'], $fields['url'], $fields['cookies'] );
	}
	return $fields;
}
add_filter( 'comment_form_default_fields', 'screentime_fse_filter_comment_fields' );

/**
 * Removes the "Logged in as …" notice and the pre/post-textarea notes from
 * the comment form on single movie pages.
 *
 * @param array $args Comment form arguments.
 * @return array
 * @since 1.0.0
 */
function screentime_fse_filter_comment_form_args( array $args ) {
	if ( is_singular( 'rt-movie' ) ) {
		$args['logged_in_as']         = '';
		$args['comment_notes_before'] = '';
		$args['comment_notes_after']  = '';
	}
	return $args;
}
add_filter( 'comment_form_defaults', 'screentime_fse_filter_comment_form_args' );
