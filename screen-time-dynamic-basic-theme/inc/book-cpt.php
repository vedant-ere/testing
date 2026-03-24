<?php
/**
 * Register Book CPT.
 *
 * @package ScreenTime
 */

add_action( 'init', 'screentime_register_book_cpt' );

/**
 * Registers Book custom post type.
 *
 * @return void
 */
function screentime_register_book_cpt() {
	$labels = array(
		'name'                  => __( 'Books', 'screen-time' ),
		'singular_name'         => __( 'Book', 'screen-time' ),
		'menu_name'             => __( 'Books', 'screen-time' ),
		'name_admin_bar'        => __( 'Book', 'screen-time' ),
		'add_new'               => __( 'Add New', 'screen-time' ),
		'add_new_item'          => __( 'Add New Book', 'screen-time' ),
		'edit_item'             => __( 'Edit Book', 'screen-time' ),
		'new_item'              => __( 'New Book', 'screen-time' ),
		'view_item'             => __( 'View Book', 'screen-time' ),
		'view_items'            => __( 'View Books', 'screen-time' ),
		'search_items'          => __( 'Search Books', 'screen-time' ),
		'not_found'             => __( 'No books found.', 'screen-time' ),
		'not_found_in_trash'    => __( 'No books found in Trash.', 'screen-time' ),
		'all_items'             => __( 'All Books', 'screen-time' ),
		'archives'              => __( 'Book Archives', 'screen-time' ),
		'insert_into_item'      => __( 'Insert into book', 'screen-time' ),
		'uploaded_to_this_item' => __( 'Uploaded to this book', 'screen-time' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'has_archive'        => true,
		'show_in_rest'       => true,
		'menu_icon'          => 'dashicons-book',
		'rewrite'            => array(
			'slug'       => 'books',
			'with_front' => false,
		),
		'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_nav_menus'  => true,
	);

	register_post_type( 'book', $args );
}
