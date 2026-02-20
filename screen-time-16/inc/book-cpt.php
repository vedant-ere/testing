<?php
/**
 * Register Book CPT.
 *
 * @package ScreenTime
 */

add_action( 'init', 'screentime_register_book_cpt' );

/**
 * Register the standalone Book custom post type.
 *
 * This post type is intentionally independent from the movie/person entities
 * and provides a simple REST-enabled content type for future expansion.
 *
 * @return void
 */
function screentime_register_book_cpt() {
	register_post_type(
		'rt-book',
		array(
			'labels'       => array(
				'name'          => __( 'Books', 'screen-time' ),
				'singular_name' => __( 'Book', 'screen-time' ),
				'add_new_item'  => __( 'Add New Book', 'screen-time' ),
				'edit_item'     => __( 'Edit Book', 'screen-time' ),
				'view_item'     => __( 'View Book', 'screen-time' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'menu_icon'    => 'dashicons-book',
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'rewrite'      => array(
				'slug' => 'books',
			),
		)
	);
}
