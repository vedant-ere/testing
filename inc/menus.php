<?php
/**
 * Navigation menus — bootstrap default menus on theme activation.
 *
 * Creates the Primary and Mobile navigation menus with the required menu items
 * (MOVIES, TV SHOWS, EVENTS, THEATRE, CELEBRITIES).
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates the primary and mobile navigation menus on theme activation.
 *
 * Runs once per theme activation via after_switch_theme hook.
 *
 * @since 1.0.0
 */
function screentime_fse_create_menus() {
	// Check if menus already exist to avoid duplicates.
	$primary_menu_id = get_nav_menu_locations()['primary'] ?? null;
	$mobile_menu_id = get_nav_menu_locations()['mobile'] ?? null;

	// Create Primary Menu if not assigned.
	if ( ! $primary_menu_id ) {
		$primary_menu = wp_create_nav_menu( __( 'Primary Menu', 'screen-time-fse' ) );
		if ( ! is_wp_error( $primary_menu ) ) {
			$items = array(
				array( 'title' => 'Movies', 'url' => '#' ),
				array( 'title' => 'TV Shows', 'url' => '#' ),
				array( 'title' => 'Events', 'url' => '#' ),
				array( 'title' => 'Theatre', 'url' => '#' ),
				array( 'title' => 'Celebrities', 'url' => '#' ),
			);

			foreach ( $items as $item ) {
				wp_update_nav_menu_item( $primary_menu, 0, array(
					'menu-item-title'  => $item['title'],
					'menu-item-url'    => $item['url'],
					'menu-item-status' => 'publish',
				) );
			}

			// Assign to primary location.
			$locations = get_theme_mod( 'nav_menu_locations', array() );
			$locations['primary'] = $primary_menu;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}

	// Create Mobile Menu if not assigned.
	if ( ! $mobile_menu_id ) {
		$mobile_menu = wp_create_nav_menu( __( 'Mobile Menu', 'screen-time-fse' ) );
		if ( ! is_wp_error( $mobile_menu ) ) {
			$items = array(
				array( 'title' => 'Movies', 'url' => '#' ),
				array( 'title' => 'TV Shows', 'url' => '#' ),
				array( 'title' => 'Events', 'url' => '#' ),
				array( 'title' => 'Theatre', 'url' => '#' ),
				array( 'title' => 'Celebrities', 'url' => '#' ),
			);

			foreach ( $items as $item ) {
				wp_update_nav_menu_item( $mobile_menu, 0, array(
					'menu-item-title'  => $item['title'],
					'menu-item-url'    => $item['url'],
					'menu-item-status' => 'publish',
				) );
			}

			// Assign to mobile location.
			$locations = get_theme_mod( 'nav_menu_locations', array() );
			$locations['mobile'] = $mobile_menu;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}
}
add_action( 'after_switch_theme', 'screentime_fse_create_menus' );
