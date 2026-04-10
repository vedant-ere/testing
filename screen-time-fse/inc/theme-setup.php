<?php
/**
 * Theme setup — add_theme_support declarations and editor stylesheet.
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers theme supports, the editor stylesheet, and navigation menus.
 *
 * @since 1.0.0
 */
function screentime_fse_setup() {
	// Block editor stylesheet (editor iFrame).
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );

	// Opt in to responsive embedded media.
	add_theme_support( 'responsive-embeds' );

	// Register navigation menus.
	register_nav_menus(
		array(
			'primary'       => __( 'Primary Menu', 'screen-time-fse' ),
			'mobile'        => __( 'Mobile Menu', 'screen-time-fse' ),
		)
	);

	// Support custom logo upload via customizer.
	add_theme_support( 'custom-logo', array(
		'height'      => 100,
		'width'       => 300,
		'flex-height' => true,
		'flex-width'  => true,
	) );
}
add_action( 'after_setup_theme', 'screentime_fse_setup' );
