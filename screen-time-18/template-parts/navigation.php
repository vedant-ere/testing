<?php
/**
 * Shared navigation renderer.
 *
 * Accepts optional `$args` from `get_template_part()`:
 * - `theme_location` (string) Menu location key.
 * - `menu_class` (string) CSS class string for the `<ul>`.
 *
 * @package ScreenTime
 */

$theme_location = isset( $args['theme_location'] ) ? sanitize_key( $args['theme_location'] ) : 'primary';
$menu_class     = isset( $args['menu_class'] ) ? (string) $args['menu_class'] : 'site-header__menu';

$menu_classes = array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', $menu_class ) ) );
$menu_class   = ! empty( $menu_classes ) ? implode( ' ', $menu_classes ) : 'site-header__menu';

$default_items = array(
	array(
		'label' => __( 'Movies', 'screen-time' ),
		'url'   => home_url( '/rt-movie/' ),
	),
	array(
		'label' => __( 'TV Shows', 'screen-time' ),
		'url'   => home_url(),
	),
	array(
		'label' => __( 'Events', 'screen-time' ),
		'url'   => home_url(),
	),
	array(
		'label' => __( 'Theatre', 'screen-time' ),
		'url'   => home_url(),
	),
	array(
		'label' => __( 'Celebrities', 'screen-time' ),
		'url'   => home_url( '/rt-person/' ),
	),
);

if ( 'footer_company' === $theme_location ) {
	$default_items = array(
		array( 'label' => __( 'About Us', 'screen-time' ), 'url' => '#' ),
		array( 'label' => __( 'Team', 'screen-time' ), 'url' => '#' ),
		array( 'label' => __( 'Careers', 'screen-time' ), 'url' => '#' ),
		array( 'label' => __( 'Culture', 'screen-time' ), 'url' => '#' ),
		array( 'label' => __( 'Community', 'screen-time' ), 'url' => '#' ),
	);
}

if ( 'footer_explore' === $theme_location ) {
	$default_items = array(
		array( 'label' => __( 'Movies', 'screen-time' ), 'url' => home_url( '/rt-movie/' ) ),
		array( 'label' => __( 'People', 'screen-time' ), 'url' => home_url( '/rt-person/' ) ),
		array( 'label' => __( 'Book Archive', 'screen-time' ), 'url' => home_url( '/books/' ) ),
	);
}

if ( 'footer_bottom' === $theme_location ) {
	$default_items = array(
		array( 'label' => __( 'Terms of Service', 'screen-time' ), 'url' => '#' ),
		array( 'label' => __( 'Privacy Policy', 'screen-time' ), 'url' => '#' ),
	);
}

if ( has_nav_menu( $theme_location ) ) {
	wp_nav_menu(
		array(
			'theme_location' => $theme_location,
			'container'      => false,
			'menu_class'     => $menu_class,
			'fallback_cb'    => false,
		)
	);
} else {
	?>
	<ul class="<?php echo esc_attr( $menu_class ); ?>">
		<?php foreach ( $default_items as $item ) : ?>
			<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
		<?php endforeach; ?>
	</ul>
	<?php
}
