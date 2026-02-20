<?php
/**
 * Theme setup hooks.
 *
 * @package ScreenTime
 */

add_action( 'after_setup_theme', 'screentime_theme_setup' );

/**
 * Registers core theme supports and menu locations.
 *
 * The assignment requires an editable logo and dynamic navigation regions,
 * so these capabilities are declared once during theme setup and consumed
 * by header/footer templates.
 *
 * @return void
 */
function screentime_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 320,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

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
			'primary'        => __( 'Primary Menu', 'screen-time' ),
			'footer_company' => __( 'Footer Company Links', 'screen-time' ),
			'footer_explore' => __( 'Footer Explore Links', 'screen-time' ),
			'footer_bottom'  => __( 'Footer Bottom Links', 'screen-time' ),
		)
	);
}
