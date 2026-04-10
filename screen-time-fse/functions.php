<?php
/**
 * Screen Time FSE — theme bootstrap.
 *
 * Defines theme-wide constants and loads modular include files.
 * Business logic lives in the relevant inc/ file; this file only bootstraps.
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

// ─── Theme Constants ─────────────────────────────────────────────────────────

if ( ! defined( 'SCREENTIME_FSE_VERSION' ) ) {
	define( 'SCREENTIME_FSE_VERSION', '1.0.0' );
}

if ( ! defined( 'SCREENTIME_FSE_PATH' ) ) {
	define( 'SCREENTIME_FSE_PATH', get_template_directory() );
}

if ( ! defined( 'SCREENTIME_FSE_URI' ) ) {
	define( 'SCREENTIME_FSE_URI', get_template_directory_uri() );
}

// ─── Modular Includes ────────────────────────────────────────────────────────

require_once SCREENTIME_FSE_PATH . '/inc/theme-setup.php';      // add_theme_support, custom logo, nav menus
require_once SCREENTIME_FSE_PATH . '/inc/menus.php';            // create default navigation menus
require_once SCREENTIME_FSE_PATH . '/inc/enqueue-assets.php';   // fonts, CSS, JS
require_once SCREENTIME_FSE_PATH . '/inc/block-bindings.php';   // register rt-movie meta (REST + Block Binding API)
require_once SCREENTIME_FSE_PATH . '/inc/seed-terms.php';       // default rt-movie-label terms
require_once SCREENTIME_FSE_PATH . '/inc/comments.php';         // strip comment form fields on movie pages
