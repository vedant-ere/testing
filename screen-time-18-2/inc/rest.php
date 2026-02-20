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
				$birthdate_raw = get_post_meta( (int) $object['id'], 'rt-person-meta-basic-birth-date', true );

				if ( ! is_string( $birthdate_raw ) || '' === $birthdate_raw ) {
					return '';
				}

				$timestamp = strtotime( $birthdate_raw );
				if ( false === $timestamp ) {
					return $birthdate_raw;
				}

				return wp_date( get_option( 'date_format' ), $timestamp );
			},
			'schema'       => array(
				'description' => __( 'Person birth date.', 'screen-time' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		)
	);
}
