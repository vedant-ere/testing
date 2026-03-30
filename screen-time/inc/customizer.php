<?php
/**
 * Customizer registration for theme-level display settings.
 *
 * Registers a dedicated panel with scoped sections whose visibility adapts
 * to the page the Customizer preview is currently displaying.
 *
 * Uses the Singleton pattern to match the project convention established
 * in the RT_Movie_Library plugin.
 *
 * @package ScreenTime
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Screen_Time_Customizer
 *
 * Single-instance class responsible for all Customizer panel, section,
 * setting, and control registration for the Screen Time theme.
 */
class Screen_Time_Customizer {

	use Singleton;

	/*
	 * =========================================================================
	 * Class Constants — single source of truth for defaults and ranges.
	 * =========================================================================
	 */

	/**
	 * Default values for every Screen Time Options setting.
	 *
	 * Referenced by:
	 * - Setting registration (default parameter).
	 * - Inline CSS output (fallback comparison).
	 * - Reset Layout Defaults button (via get_layout_defaults()).
	 *
	 * @var array<string, mixed>
	 */
	const DEFAULTS = array(
		'screentime_background_color'    => '#1f1f1f',
		'screentime_display_navigation'  => true,
		'screentime_time_format'         => 'hr_min',
		'screentime_separator'           => 'bullet',
		'screentime_sidebar_width'       => 280,
		'screentime_movie_image_width'   => 552,
		'screentime_movie_image_height'  => 876,
		'screentime_person_image_width'  => 488,
		'screentime_person_image_height' => 572,
	);

	/**
	 * Allowed min/max ranges for numeric settings.
	 *
	 * Used by both sanitize_numeric() and validate_numeric() so
	 * range rules are defined once and enforced everywhere.
	 *
	 * @var array<string, array{min: int, max: int}>
	 */
	const RANGES = array(
		'screentime_sidebar_width'       => array(
			'min' => 50,
			'max' => 800,
		),
		'screentime_movie_image_width'   => array(
			'min' => 100,
			'max' => 1200,
		),
		'screentime_movie_image_height'  => array(
			'min' => 100,
			'max' => 1600,
		),
		'screentime_person_image_width'  => array(
			'min' => 100,
			'max' => 1200,
		),
		'screentime_person_image_height' => array(
			'min' => 100,
			'max' => 1600,
		),
	);



	/*
	 * =========================================================================
	 * Constructor & Hook Registration.
	 * =========================================================================
	 */

	/**
	 * Constructor.
	 *
	 * Registers all WordPress hooks used by the Customizer.
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Binds methods to their respective WordPress action hooks.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		add_action( 'customize_register', array( $this, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'inline_css' ), 20 );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_controls' ) );
		add_action( 'customize_preview_init', array( $this, 'enqueue_preview' ) );
	}

	/*
	 * =========================================================================
	 * Registration — dispatches to focused private methods.
	 * =========================================================================
	 */

	/**
	 * Registers all Customizer panels, sections, settings, and controls.
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
	 * @return void
	 */
	public function register( $wp_customize ) {
		$this->register_footer_options( $wp_customize );
		$this->register_panel( $wp_customize );
		$this->register_global_section( $wp_customize );
		$this->register_navigation_section( $wp_customize );
		$this->register_movie_details_section( $wp_customize );
		$this->register_single_post_section( $wp_customize );
	}

	/**
	 * Registers the pre-existing Footer Options section.
	 *
	 * Kept intact from the original theme; only wrapped in a method.
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
	 * @return void
	 */
	private function register_footer_options( $wp_customize ) {
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

		$social_networks = array(
			'facebook'  => __( 'Facebook URL', 'screen-time' ),
			'twitter'   => __( 'Twitter URL', 'screen-time' ),
			'youtube'   => __( 'YouTube URL', 'screen-time' ),
			'instagram' => __( 'Instagram URL', 'screen-time' ),
			'rss'       => __( 'RSS URL', 'screen-time' ),
		);

		foreach ( $social_networks as $network_slug => $network_label ) {
			$setting_id = 'screentime_footer_social_' . $network_slug;

			$wp_customize->add_setting(
				$setting_id,
				array(
					'default'           => '#',
					'sanitize_callback' => 'esc_url_raw',
				)
			);

			$wp_customize->add_control(
				$setting_id,
				array(
					'label'   => $network_label,
					'section' => 'screentime_footer_options',
					'type'    => 'url',
				)
			);
		}
	}

	/**
	 * Registers the Screen Time Options panel.
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
	 * @return void
	 */
	private function register_panel( $wp_customize ) {
		$wp_customize->add_panel(
			'screentime_options',
			array(
				'title'       => __( 'Screen Time Options', 'screen-time' ),
				'priority'    => 30,
				'description' => __( 'Theme display settings scoped to specific page types.', 'screen-time' ),
			)
		);
	}

	/**
	 * Section 1 — Site / Global (always visible).
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
	 * @return void
	 */
	private function register_global_section( $wp_customize ) {
		$wp_customize->add_section(
			'screentime_global',
			array(
				'title'       => __( 'Site / Global', 'screen-time' ),
				'panel'       => 'screentime_options',
				'priority'    => 10,
				'description' => __( 'Site-wide appearance settings.', 'screen-time' ),
			)
		);

		$wp_customize->add_setting(
			'screentime_background_color',
			array(
				'default'           => self::DEFAULTS['screentime_background_color'],
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'screentime_background_color',
				array(
					'label'       => __( 'Background Color', 'screen-time' ),
					'description' => __( 'Controls the background color of all pages.', 'screen-time' ),
					'section'     => 'screentime_global',
				)
			)
		);
	}

	/**
	 * Section 2 — Navigation (visible on single posts).
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
	 * @return void
	 */
	private function register_navigation_section( $wp_customize ) {
		$wp_customize->add_section(
			'screentime_navigation',
			array(
				'title'           => __( 'Navigation', 'screen-time' ),
				'panel'           => 'screentime_options',
				'priority'        => 20,
				'description'     => __( 'Previous / next post navigation for single pages.', 'screen-time' ),
				'active_callback' => array( $this, 'active_on_singular_movie_or_person' ),
			)
		);

		$wp_customize->add_setting(
			'screentime_display_navigation',
			array(
				'default'           => self::DEFAULTS['screentime_display_navigation'],
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'screentime_display_navigation',
			array(
				'label'       => __( 'Display Navigation', 'screen-time' ),
				'description' => __( 'Show previous / next post links in the site footer.', 'screen-time' ),
				'section'     => 'screentime_navigation',
				'type'        => 'checkbox',
			)
		);
	}

	/**
	 * Section 3 — Movie Details (visible on single Movie CPT pages).
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
	 * @return void
	 */
	private function register_movie_details_section( $wp_customize ) {
		$wp_customize->add_section(
			'screentime_movie_details',
			array(
				'title'           => __( 'Movie Details', 'screen-time' ),
				'panel'           => 'screentime_options',
				'priority'        => 30,
				'description'     => __( 'Display options for single movie pages.', 'screen-time' ),
				'active_callback' => array( $this, 'active_on_singular_movie' ),
			)
		);

		$wp_customize->add_setting(
			'screentime_time_format',
			array(
				'default'           => self::DEFAULTS['screentime_time_format'],
				'sanitize_callback' => array( $this, 'sanitize_select' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'screentime_time_format',
			array(
				'label'       => __( 'Time Format', 'screen-time' ),
				'description' => __( 'Controls how movie duration is displayed.', 'screen-time' ),
				'section'     => 'screentime_movie_details',
				'type'        => 'select',
				'choices'     => self::get_time_format_choices(),
			)
		);

		$wp_customize->add_setting(
			'screentime_separator',
			array(
				'default'           => self::DEFAULTS['screentime_separator'],
				'sanitize_callback' => array( $this, 'sanitize_select' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'screentime_separator',
			array(
				'label'       => __( 'Separator', 'screen-time' ),
				'description' => __( 'Character used between metadata items and directors.', 'screen-time' ),
				'section'     => 'screentime_movie_details',
				'type'        => 'select',
				'choices'     => self::get_separator_choices(),
			)
		);
	}

	/**
	 * Section 4 — Single Post Pages (visible on Movie or Person singles).
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
	 * @return void
	 */
	private function register_single_post_section( $wp_customize ) {
		$wp_customize->add_section(
			'screentime_single_post',
			array(
				'title'           => __( 'Single Post Pages', 'screen-time' ),
				'panel'           => 'screentime_options',
				'priority'        => 40,
				'description'     => __( 'Layout settings for Movie and Person single pages.', 'screen-time' ),
				'active_callback' => array( $this, 'active_on_singular_movie_or_person' ),
			)
		);

		// -- Sidebar Width (px) --------------------------------------------------
		$sidebar_range = self::RANGES['screentime_sidebar_width'];

		$wp_customize->add_setting(
			'screentime_sidebar_width',
			array(
				'default'           => self::DEFAULTS['screentime_sidebar_width'],
				'sanitize_callback' => array( $this, 'sanitize_numeric' ),
				'validate_callback' => array( $this, 'validate_numeric' ),
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_control(
			'screentime_sidebar_width',
			array(
				'label'       => __( 'Sidebar Width (px)', 'screen-time' ),
				'description' => sprintf(
					/* translators: 1: minimum, 2: maximum. */
					__( 'Allowed range: %1$s–%2$s px', 'screen-time' ),
					$sidebar_range['min'],
					$sidebar_range['max']
				),
				'section'     => 'screentime_single_post',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => $sidebar_range['min'],
					'max'  => $sidebar_range['max'],
					'step' => 1,
				),
			)
		);

		// -- Movie Featured Image Width ---------------------------------------
		$movie_w_range = self::RANGES['screentime_movie_image_width'];

		$wp_customize->add_setting(
			'screentime_movie_image_width',
			array(
				'default'           => self::DEFAULTS['screentime_movie_image_width'],
				'sanitize_callback' => array( $this, 'sanitize_numeric' ),
				'validate_callback' => array( $this, 'validate_numeric' ),
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_control(
			'screentime_movie_image_width',
			array(
				'label'           => __( 'Movie Poster Width (px)', 'screen-time' ),
				'section'         => 'screentime_single_post',
				'type'            => 'number',
				'active_callback' => array( $this, 'active_on_singular_movie' ),
				/* translators: 1: minimum allowed pixel value, 2: maximum allowed pixel value. */
				'description'     => sprintf( __( 'Allowed range: %1$d–%2$d px', 'screen-time' ), $movie_w_range['min'], $movie_w_range['max'] ),
				'input_attrs'     => array(
					'min'  => $movie_w_range['min'],
					'max'  => $movie_w_range['max'],
					'step' => 1,
				),
			)
		);

		// -- Movie Featured Image Height --------------------------------------
		$movie_h_range = self::RANGES['screentime_movie_image_height'];

		$wp_customize->add_setting(
			'screentime_movie_image_height',
			array(
				'default'           => self::DEFAULTS['screentime_movie_image_height'],
				'sanitize_callback' => array( $this, 'sanitize_numeric' ),
				'validate_callback' => array( $this, 'validate_numeric' ),
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_control(
			'screentime_movie_image_height',
			array(
				'label'           => __( 'Movie Poster Height (px)', 'screen-time' ),
				'section'         => 'screentime_single_post',
				'type'            => 'number',
				'active_callback' => array( $this, 'active_on_singular_movie' ),
				/* translators: 1: minimum allowed pixel value, 2: maximum allowed pixel value. */
				'description'     => sprintf( __( 'Allowed range: %1$d–%2$d px', 'screen-time' ), $movie_h_range['min'], $movie_h_range['max'] ),
				'input_attrs'     => array(
					'min'  => $movie_h_range['min'],
					'max'  => $movie_h_range['max'],
					'step' => 1,
				),
			)
		);

		// -- Person Featured Image Width --------------------------------------
		$person_w_range = self::RANGES['screentime_person_image_width'];

		$wp_customize->add_setting(
			'screentime_person_image_width',
			array(
				'default'           => self::DEFAULTS['screentime_person_image_width'],
				'sanitize_callback' => array( $this, 'sanitize_numeric' ),
				'validate_callback' => array( $this, 'validate_numeric' ),
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_control(
			'screentime_person_image_width',
			array(
				'label'           => __( 'Person Profile Width (px)', 'screen-time' ),
				'section'         => 'screentime_single_post',
				'type'            => 'number',
				'active_callback' => array( $this, 'active_on_singular_person' ),
				/* translators: 1: minimum allowed pixel value, 2: maximum allowed pixel value. */
				'description'     => sprintf( __( 'Allowed range: %1$d–%2$d px', 'screen-time' ), $person_w_range['min'], $person_w_range['max'] ),
				'input_attrs'     => array(
					'min'  => $person_w_range['min'],
					'max'  => $person_w_range['max'],
					'step' => 1,
				),
			)
		);

		// -- Person Featured Image Height -------------------------------------
		$person_h_range = self::RANGES['screentime_person_image_height'];

		$wp_customize->add_setting(
			'screentime_person_image_height',
			array(
				'default'           => self::DEFAULTS['screentime_person_image_height'],
				'sanitize_callback' => array( $this, 'sanitize_numeric' ),
				'validate_callback' => array( $this, 'validate_numeric' ),
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_control(
			'screentime_person_image_height',
			array(
				'label'           => __( 'Person Profile Height (px)', 'screen-time' ),
				'section'         => 'screentime_single_post',
				'type'            => 'number',
				'active_callback' => array( $this, 'active_on_singular_person' ),
				/* translators: 1: minimum allowed pixel value, 2: maximum allowed pixel value. */
				'description'     => sprintf( __( 'Allowed range: %1$d–%2$d px', 'screen-time' ), $person_h_range['min'], $person_h_range['max'] ),
				'input_attrs'     => array(
					'min'  => $person_h_range['min'],
					'max'  => $person_h_range['max'],
					'step' => 1,
				),
			)
		);
	}

	/*
	 * =========================================================================
	 * Active Callbacks — control section visibility per preview context.
	 * =========================================================================
	 */

	/**
	 * Returns true when the Customizer preview shows any single post.
	 *
	 * @return bool
	 */
	public function active_on_singular() {
		return is_singular();
	}

	/**
	 * Returns true when the preview shows a single Movie CPT page.
	 *
	 * @return bool
	 */
	public function active_on_singular_movie() {
		return is_singular( 'rt-movie' );
	}

	/**
	 * Returns true when the preview shows a single Person CPT page.
	 *
	 * @return bool
	 */
	public function active_on_singular_person() {
		return is_singular( 'rt-person' );
	}

	/**
	 * Returns true when the preview shows a single Movie or Person CPT page.
	 *
	 * @return bool
	 */
	public function active_on_singular_movie_or_person() {
		return is_singular( 'rt-movie' ) || is_singular( 'rt-person' );
	}

	/*
	 * =========================================================================
	 * Sanitize Callbacks.
	 * =========================================================================
	 */

	/**
	 * Sanitizes a checkbox value to boolean.
	 *
	 * @param mixed $value Raw input value.
	 * @return bool
	 */
	public function sanitize_checkbox( $value ) {
		return (bool) $value;
	}

	/**
	 * Sanitizes a select / dropdown value against its registered choices.
	 *
	 * Falls back to the setting default when the submitted value is not a
	 * recognised option. Reused for Time Format and Separator.
	 *
	 * @param string               $value   Submitted value.
	 * @param WP_Customize_Setting $setting Current setting instance.
	 * @return string
	 */
	public function sanitize_select( $value, $setting ) {
		$value   = sanitize_key( $value );
		$control = $setting->manager->get_control( $setting->id );

		if ( ! $control || ! isset( $control->choices[ $value ] ) ) {
			return $setting->default;
		}

		return $value;
	}

	/**
	 * Generic numeric sanitizer — looks up the range from the RANGES constant.
	 *
	 * WordPress passes the WP_Customize_Setting instance as the second
	 * argument to sanitize callbacks, so we identify the setting via its ID
	 * and clamp the value to the matching range.
	 *
	 * @param mixed                $value   Raw input.
	 * @param WP_Customize_Setting $setting Current setting instance.
	 * @return int
	 */
	public function sanitize_numeric( $value, $setting ) {
		$range = isset( self::RANGES[ $setting->id ] ) ? self::RANGES[ $setting->id ] : null;

		if ( ! $range ) {
			return absint( $value );
		}

		return $this->clamp_int( $value, $range['min'], $range['max'] );
	}

	/*
	 * =========================================================================
	 * Validate Callbacks.
	 * =========================================================================
	 */

	/**
	 * Generic numeric validator — returns WP_Error when out of range.
	 *
	 * WordPress passes the WP_Customize_Setting instance as the third
	 * argument to validate callbacks.
	 *
	 * @param WP_Error             $validity Current validity object.
	 * @param mixed                $value    Submitted value.
	 * @param WP_Customize_Setting $setting  Current setting instance.
	 * @return WP_Error
	 */
	public function validate_numeric( $validity, $value, $setting ) {
		$range = isset( self::RANGES[ $setting->id ] ) ? self::RANGES[ $setting->id ] : null;

		if ( ! $range ) {
			return $validity;
		}

		return $this->validate_range( $validity, $value, $range['min'], $range['max'] );
	}

	/*
	 * =========================================================================
	 * Private Helpers.
	 * =========================================================================
	 */

	/**
	 * Clamps an integer value between a minimum and maximum.
	 *
	 * @param mixed $value Raw input.
	 * @param int   $min   Minimum allowed value.
	 * @param int   $max   Maximum allowed value.
	 * @return int
	 */
	private function clamp_int( $value, $min, $max ) {
		$value = absint( $value );

		if ( $value < $min ) {
			return $min;
		}

		if ( $value > $max ) {
			return $max;
		}

		return $value;
	}

	/**
	 * Adds a WP_Error when the value falls outside the allowed range.
	 *
	 * @param WP_Error  $validity Current validity object.
	 * @param mixed     $value    Submitted value.
	 * @param int|float $min      Minimum allowed value.
	 * @param int|float $max      Maximum allowed value.
	 * @return WP_Error
	 */
	private function validate_range( $validity, $value, $min, $max ) {
		$num_value = floatval( $value );

		if ( $num_value < $min || $num_value > $max ) {
			$validity->add(
				'out_of_range',
				sprintf(
					/* translators: 1: minimum, 2: maximum. */
					__( 'Value must be between %1$s and %2$s.', 'screen-time' ),
					$min,
					$max
				)
			);
		}

		return $validity;
	}

	/*
	 * =========================================================================
	 * Static Getters — choice arrays, separator map.
	 * =========================================================================
	 */

	/**
	 * Returns the available time-format choices.
	 *
	 * @return array<string, string>
	 */
	public static function get_time_format_choices() {
		return array(
			'hr_min'  => __( 'Hours & Minutes (2 hr 15 min)', 'screen-time' ),
			'hh_mm'   => __( 'HH:MM (02:15)', 'screen-time' ),
			'minutes' => __( 'Minutes only (135 min)', 'screen-time' ),
		);
	}

	/**
	 * Returns the available separator choices.
	 *
	 * @return array<string, string>
	 */
	public static function get_separator_choices() {
		return array(
			'bullet' => __( 'Bullet (•)', 'screen-time' ),
			'dash'   => __( 'Dash (–)', 'screen-time' ),
			'pipe'   => __( 'Pipe (|)', 'screen-time' ),
			'slash'  => __( 'Slash (/)', 'screen-time' ),
		);
	}

	/**
	 * Returns the key → character map for separators.
	 *
	 * Unicode escapes are used instead of literal characters to prevent
	 * encoding issues during migration or minification.
	 *
	 * @return array<string, string>
	 */
	public static function get_separator_map() {
		return array(
			'bullet' => "\u{2022}",
			'dash'   => "\u{2013}",
			'pipe'   => '|',
			'slash'  => '/',
		);
	}

	/**
	 * Returns the separator character chosen in the Customizer.
	 *
	 * Called by the backward-compatible screentime_get_separator() wrapper
	 * and used in metadata.php and single-rt-movie.php templates.
	 *
	 * @return string
	 */
	public static function get_separator_character() {
		$key = get_theme_mod( 'screentime_separator', self::DEFAULTS['screentime_separator'] );
		$map = self::get_separator_map();

		return isset( $map[ $key ] ) ? $map[ $key ] : $map['bullet'];
	}

	/**
	 * Returns only the layout-related defaults for the Reset button.
	 *
	 * Derived from the DEFAULTS constant so values are never duplicated.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_layout_defaults() {
		$layout_keys = array(
			'screentime_sidebar_width',
			'screentime_movie_image_width',
			'screentime_movie_image_height',
			'screentime_person_image_width',
			'screentime_person_image_height',
		);

		return array_intersect_key( self::DEFAULTS, array_flip( $layout_keys ) );
	}

	/*
	 * =========================================================================
	 * Inline CSS — outputs Customizer values as CSS custom properties.
	 * =========================================================================
	 */

	/**
	 * Injects CSS custom properties driven by Customizer settings.
	 *
	 * Hooked at priority 20 so the global stylesheet is already enqueued.
	 *
	 * @return void
	 */
	public function inline_css() {
		$root_vars = array();

		// Background color — site-wide.
		$bg_color = get_theme_mod( 'screentime_background_color', self::DEFAULTS['screentime_background_color'] );

		if ( self::DEFAULTS['screentime_background_color'] !== $bg_color ) {
			$root_vars[] = '--color-page-bg: ' . sanitize_hex_color( $bg_color );
		}

		// Sidebar width — Movie / Person single pages.
		if ( is_singular( 'rt-movie' ) || is_singular( 'rt-person' ) ) {
			$sidebar_width = absint( get_theme_mod( 'screentime_sidebar_width', self::DEFAULTS['screentime_sidebar_width'] ) );

			$root_vars[] = '--screentime-sidebar-width: ' . $sidebar_width . 'px';
		}

		// Movie poster dimensions.
		if ( is_singular( 'rt-movie' ) ) {
			$movie_img_w = absint( get_theme_mod( 'screentime_movie_image_width', self::DEFAULTS['screentime_movie_image_width'] ) );
			$movie_img_h = absint( get_theme_mod( 'screentime_movie_image_height', self::DEFAULTS['screentime_movie_image_height'] ) );

			$root_vars[] = '--screentime-movie-image-width: ' . $movie_img_w . 'px';
			$root_vars[] = '--screentime-movie-image-height: ' . $movie_img_h . 'px';
		}

		// Person profile dimensions.
		if ( is_singular( 'rt-person' ) ) {
			$person_img_w = absint( get_theme_mod( 'screentime_person_image_width', self::DEFAULTS['screentime_person_image_width'] ) );
			$person_img_h = absint( get_theme_mod( 'screentime_person_image_height', self::DEFAULTS['screentime_person_image_height'] ) );

			$root_vars[] = '--screentime-person-image-width: ' . $person_img_w . 'px';
			$root_vars[] = '--screentime-person-image-height: ' . $person_img_h . 'px';
		}

		if ( ! empty( $root_vars ) ) {
			$css = ':root { ' . implode( '; ', $root_vars ) . '; }';
			wp_add_inline_style( 'screentime-global', $css );
		}
	}

	/*
	 * =========================================================================
	 * Customizer Controls — Reset Layout Defaults button.
	 * =========================================================================
	 */

	/**
	 * Enqueues the reset-defaults script in the Customizer controls pane.
	 *
	 * Passes all layout setting IDs and their defaults so the JS can reset
	 * each one without hardcoding values on the client side.
	 *
	 * @return void
	 */
	public function enqueue_controls() {
		wp_enqueue_script(
			'screentime-customizer-reset',
			SCREENTIME_URI . '/assets/js/customizer-reset.js',
			array( 'customize-controls' ),
			screentime_asset_version( '/assets/js/customizer-reset.js' ),
			true
		);

		wp_localize_script(
			'screentime-customizer-reset',
			'screentimeCustomizerDefaults',
			array(
				'buttonLabel' => __( 'Reset Layout Defaults', 'screen-time' ),
				'settings'    => self::get_layout_defaults(),
			)
		);
	}

	/*
	 * =========================================================================
	 * Customizer Preview — live CSS updates via postMessage.
	 * =========================================================================
	 */

	/**
	 * Enqueues the preview script inside the Customizer preview iframe.
	 *
	 * Handles real-time CSS custom property updates for settings that use
	 * the postMessage transport, so the preview does not reload.
	 *
	 * @return void
	 */
	public function enqueue_preview() {
		wp_enqueue_script(
			'screentime-customizer-preview',
			SCREENTIME_URI . '/assets/js/customizer-preview.js',
			array( 'customize-preview' ),
			screentime_asset_version( '/assets/js/customizer-preview.js' ),
			true
		);
	}
}
