<?php
/**
 * Theme footer.
 *
 * @package ScreenTime
 */

$copyright_suffix = get_theme_mod( 'screentime_footer_copyright', __( 'All Rights Reserved.', 'screen-time' ) );
?>
<footer class="site-footer">
	<div class="container site-footer__top">
		<div class="site-footer__brand-col">
			<?php screentime_the_site_logo( 'site-logo site-logo--footer' ); ?>
			<p class="site-footer__heading"><?php esc_html_e( 'Follow Us', 'screen-time' ); ?></p>
			<div class="social-list" aria-label="<?php esc_attr_e( 'Social links', 'screen-time' ); ?>">
				<a href="#" aria-label="<?php esc_attr_e( 'Facebook', 'screen-time' ); ?>"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/social/facebook.png' ); ?>" alt="<?php esc_attr_e( 'Facebook', 'screen-time' ); ?>"></a>
				<a href="#" aria-label="<?php esc_attr_e( 'Twitter', 'screen-time' ); ?>"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/social/twitter.png' ); ?>" alt="<?php esc_attr_e( 'Twitter', 'screen-time' ); ?>"></a>
				<a href="#" aria-label="<?php esc_attr_e( 'YouTube', 'screen-time' ); ?>"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/social/youtube.png' ); ?>" alt="<?php esc_attr_e( 'YouTube', 'screen-time' ); ?>"></a>
				<a href="#" aria-label="<?php esc_attr_e( 'Instagram', 'screen-time' ); ?>"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/social/instagram.png' ); ?>" alt="<?php esc_attr_e( 'Instagram', 'screen-time' ); ?>"></a>
				<a href="#" aria-label="<?php esc_attr_e( 'RSS', 'screen-time' ); ?>"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/social/rss.png' ); ?>" alt="<?php esc_attr_e( 'RSS', 'screen-time' ); ?>"></a>
			</div>
		</div>

		<div class="site-footer__links-col">
			<h2><?php esc_html_e( 'Company', 'screen-time' ); ?></h2>
			<?php
			get_template_part(
				'template-parts/navigation',
				null,
				array(
					'theme_location' => 'footer_company',
					'menu_class'     => 'site-footer__menu',
				)
			);
			?>
		</div>

		<div class="site-footer__links-col">
			<h2><?php esc_html_e( 'Explore', 'screen-time' ); ?></h2>
			<?php
			get_template_part(
				'template-parts/navigation',
				null,
				array(
					'theme_location' => 'footer_explore',
					'menu_class'     => 'site-footer__menu',
				)
			);
			?>
		</div>
	</div>

	<div class="container site-footer__divider" aria-hidden="true"></div>

	<div class="container site-footer__bottom">
		<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php echo esc_html( $copyright_suffix ); ?></p>
		<div class="site-footer__bottom-links">
			<?php
			get_template_part(
				'template-parts/navigation',
				null,
				array(
					'theme_location' => 'footer_bottom',
					'menu_class'     => 'site-footer__menu site-footer__menu--inline',
				)
			);
			?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
