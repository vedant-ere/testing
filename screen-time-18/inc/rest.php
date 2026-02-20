<?php
/**
 * REST API helpers and routes.
 *
 * @package ScreenTime
 */

add_action( 'rest_api_init', 'screentime_register_rest_fields' );

/**
 * Registers extra REST fields for theme use-cases.
 *
 * @return void
 */
function screentime_register_rest_fields() {
	register_rest_field(
		'rt-person',
		'birthdate',
		array(
			'get_callback' => function( $object ) {
				$birthdate = get_post_meta( (int) $object['id'], 'rt-person-meta-birth-date', true );
				return is_string( $birthdate ) ? $birthdate : '';
			},
		)
	);
}
