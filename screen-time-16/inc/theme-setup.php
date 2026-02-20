<?php
/**
 * Theme setup hooks.
 *
 * @package ScreenTime
 */

add_action( 'after_setup_theme', 'screentime_theme_setup' );

/**
 * Register theme supports and nav menus.
 *
 * Centralizes all `after_setup_theme` capabilities so templates can assume
 * basic features (title tags, thumbnails, HTML5 markup, and menu locations).
 *
 * @return void
 */
function screentime_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );

	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'screen-time' ),
			'footer'  => __( 'Footer Menu', 'screen-time' ),
		)
	);
}
