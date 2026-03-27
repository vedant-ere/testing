<?php
/**
 * Widget and sidebar registration.
 *
 * Registers the two named widget areas used by recommendation widgets on
 * single movie and person pages, then registers the widget classes themselves.
 *
 * Sidebar before/after wrappers are intentionally empty strings because each
 * widget's widget() method owns all its own markup, including the <section>
 * wrapper. Delegating wrappers to register_sidebar() would add an unwanted
 * extra <div> around the section element.
 *
 * @package ScreenTime
 */

add_action( 'widgets_init', 'screentime_register_sidebars' );
add_action( 'widgets_init', 'screentime_register_widgets' );
add_action( 'admin_enqueue_scripts', 'screentime_enqueue_widget_admin_assets' );

/**
 * Registers the two recommendation sidebar areas.
 *
 * @return void
 */
function screentime_register_sidebars() {
	register_sidebar(
		array(
			'id'            => 'screentime-movie-recommendations',
			'name'          => __( 'Movie Recommendations', 'screen-time' ),
			'description'   => __( 'Displays related movie cards above the footer on single movie pages.', 'screen-time' ),
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		)
	);

	register_sidebar(
		array(
			'id'            => 'screentime-person-recommendations',
			'name'          => __( 'Person Recommendations', 'screen-time' ),
			'description'   => __( 'Displays related person cards above the footer on single person pages.', 'screen-time' ),
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		)
	);
}

/**
 * Registers the recommendation widget classes with WordPress.
 *
 * Both classes must already be loaded via require_once in functions.php
 * before this callback fires.
 *
 * @return void
 */
function screentime_register_widgets() {
	register_widget( 'Screentime_Movie_Recommendations_Widget' );
	register_widget( 'Screentime_Person_Recommendations_Widget' );
}

/**
 * Enqueues client-side validation assets on the Widgets admin screen
 * and the Customizer.
 *
 * Loads a lightweight JS file that provides real-time error feedback for
 * the recommendation widget settings (count range, title length). Uses
 * event delegation so it works for dynamically-loaded widget forms in
 * both the classic Widgets page and the block-based Widgets editor.
 *
 * @param string $hook_suffix The current admin page hook suffix.
 * @return void
 */
function screentime_enqueue_widget_admin_assets( $hook_suffix ) {

	if ( 'widgets.php' !== $hook_suffix && 'customize.php' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_script(
		'screentime-admin-widget-validation',
		SCREENTIME_URI . '/assets/js/admin-widget-validation.js',
		array(),
		screentime_asset_version( '/assets/js/admin-widget-validation.js' ),
		true
	);

	wp_localize_script(
		'screentime-admin-widget-validation',
		'screentimeWidgetValidation',
		array(
			'countRequired' => __( 'Count is required.', 'screen-time' ),
			'countInteger'  => __( 'Count must be a whole number.', 'screen-time' ),
			'countMin'      => sprintf(
				/* translators: %d: minimum allowed count. */
				__( 'Count must be at least %d.', 'screen-time' ),
				1
			),
			'countMax'      => sprintf(
				/* translators: %d: maximum allowed count. */
				__( 'Count must be at most %d.', 'screen-time' ),
				12
			),
			'titleMax'      => sprintf(
				/* translators: %d: maximum allowed title length. */
				__( 'Title must be %d characters or fewer.', 'screen-time' ),
				100
			),
		)
	);

	wp_enqueue_style(
		'screentime-widget-recommendations',
		SCREENTIME_URI . '/assets/css/components/widget-recommendations.css',
		array(),
		screentime_asset_version( '/assets/css/components/widget-recommendations.css' )
	);
}
