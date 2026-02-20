<?php
/**
 * Customizer registration for theme-level display settings.
 *
 * @package ScreenTime
 */

add_action( 'customize_register', 'screentime_register_customizer_settings' );

/**
 * Adds focused Customizer controls used by assignment templates.
 *
 * Footer links are editable through menu locations in the Customizer. This
 * function adds a dedicated copyright text setting used by footer output.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
 * @return void
 */
function screentime_register_customizer_settings( $wp_customize ) {
	$wp_customize->add_section(
		'screentime_footer_options',
		array(
			'title'       => __( 'Footer Options', 'screen-time' ),
			'priority'    => 170,
			'description' => __( 'Configure footer copyright text.', 'screen-time' ),
		)
	);

	$wp_customize->add_setting(
		'screentime_footer_copyright',
		array(
			'default'           => __( 'All Rights Reserved.', 'screen-time' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'screentime_footer_copyright',
		array(
			'label'       => __( 'Copyright Text', 'screen-time' ),
			'section'     => 'screentime_footer_options',
			'type'        => 'text',
			'description' => __( 'Year and site name are added automatically.', 'screen-time' ),
		)
	);
}
