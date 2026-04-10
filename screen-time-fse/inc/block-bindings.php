<?php
/**
 * Block Binding API — rt-movie post meta registration.
 *
 * The rt-movie-library plugin saves meta via get_post_meta() directly and
 * does not call register_meta(). We register the fields here with
 * show_in_rest: true so the Block Binding API (core/post-meta source) can
 * read and bind their values to core/paragraph blocks in the Site Editor.
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers rt-movie post meta fields for the Block Binding API.
 *
 * Runs at priority 20 — after the plugin registers the post type (≈10).
 * Bails early if the post type is not yet registered to avoid PHP notices
 * on sites where the plugin is deactivated.
 *
 * @since 1.0.0
 */
function screentime_fse_register_movie_meta() {
	if ( ! post_type_exists( 'rt-movie' ) ) {
		return;
	}

	$fields = array(
		'rt-movie-meta-basic-rating'         => 'string',
		'rt-movie-meta-basic-runtime'        => 'string',
		'rt-movie-meta-basic-release-date'   => 'string',
		'rt-movie-meta-basic-content-rating' => 'string',
	);

	foreach ( $fields as $key => $type ) {
		// Skip if already registered by another plugin or theme.
		if ( registered_meta_key_exists( 'post', $key, 'rt-movie' ) ) {
			continue;
		}

		register_meta(
			'post',
			$key,
			array(
				'object_subtype' => 'rt-movie',
				'type'           => $type,
				'single'         => true,
				'show_in_rest'   => true,
				'auth_callback'  => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'screentime_fse_register_movie_meta', 20 );
