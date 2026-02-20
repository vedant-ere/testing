<?php
/**
 * Custom image sizes for Screen Time.
 *
 * @package ScreenTime
 */

add_action( 'after_setup_theme', 'screentime_register_image_sizes' );

/**
 * Registers image sizes used across templates.
 *
 * @return void
 */
function screentime_register_image_sizes() {
	add_image_size( 'screentime-movie-card', 360, 540, true );
	add_image_size( 'screentime-person-card', 320, 320, true );
	add_image_size( 'screentime-hero-poster', 1280, 720, true );
}
