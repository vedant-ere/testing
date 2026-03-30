<?php
/**
 * Post navigation template part.
 *
 * Renders previous / next post links inside the site footer. Only outputs
 * markup when at least one adjacent post exists within the same post type.
 *
 * @package ScreenTime
 */

$show_nav = get_theme_mod( 'screentime_display_navigation', Screen_Time_Customizer::DEFAULTS['screentime_display_navigation'] );

if ( ! $show_nav || ( ! is_singular( 'rt-movie' ) && ! is_singular( 'rt-person' ) ) ) {
	return;
}

$prev_post = get_previous_post();
$next_post = get_next_post();

if ( empty( $prev_post ) && empty( $next_post ) ) {
	return;
}
?>
<nav class="post-navigation" aria-label="<?php esc_attr_e( 'Post navigation', 'screen-time' ); ?>">
	<div class="container post-navigation__inner">
		<?php if ( ! empty( $prev_post ) ) : ?>
			<a href="<?php echo esc_url( get_permalink( $prev_post ) ); ?>" class="post-navigation__link post-navigation__link--prev" rel="prev">
				<span class="post-navigation__arrow" aria-hidden="true">←</span>
				<span class="post-navigation__label"><?php echo esc_html( get_the_title( $prev_post ) ); ?></span>
			</a>
		<?php else : ?>
			<span class="post-navigation__link post-navigation__link--disabled"></span>
		<?php endif; ?>

		<?php if ( ! empty( $next_post ) ) : ?>
			<a href="<?php echo esc_url( get_permalink( $next_post ) ); ?>" class="post-navigation__link post-navigation__link--next" rel="next">
				<span class="post-navigation__label"><?php echo esc_html( get_the_title( $next_post ) ); ?></span>
				<span class="post-navigation__arrow" aria-hidden="true">→</span>
			</a>
		<?php else : ?>
			<span class="post-navigation__link post-navigation__link--disabled"></span>
		<?php endif; ?>
	</div>
</nav>
