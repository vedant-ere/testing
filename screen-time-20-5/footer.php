<?php
/**
 * Theme footer.
 *
 * @package ScreenTime
 */

$copyright_suffix = get_theme_mod( 'screentime_footer_copyright', __( 'All Rights Reserved.', 'screen-time' ) );
$social_links     = array(
	'facebook'  => get_theme_mod( 'screentime_footer_social_facebook', '#' ),
	'twitter'   => get_theme_mod( 'screentime_footer_social_twitter', '#' ),
	'youtube'   => get_theme_mod( 'screentime_footer_social_youtube', '#' ),
	'instagram' => get_theme_mod( 'screentime_footer_social_instagram', '#' ),
	'rss'       => get_theme_mod( 'screentime_footer_social_rss', '#' ),
);

$social_labels = array(
	'facebook'  => __( 'Facebook', 'screen-time' ),
	'twitter'   => __( 'Twitter', 'screen-time' ),
	'youtube'   => __( 'YouTube', 'screen-time' ),
	'instagram' => __( 'Instagram', 'screen-time' ),
	'rss'       => __( 'RSS', 'screen-time' ),
);
?>
<footer class="site-footer">
	<div class="container site-footer__top">
		<div class="site-footer__brand-col">
			<?php screentime_the_site_logo( 'site-logo site-logo--footer' ); ?>
			<p class="site-footer__heading"><?php esc_html_e( 'Follow Us', 'screen-time' ); ?></p>
			<div class="social-list" aria-label="<?php esc_attr_e( 'Social links', 'screen-time' ); ?>">
				<?php foreach ( $social_links as $network_slug => $network_url ) : ?>
					<?php
					$network_url   = is_string( $network_url ) ? $network_url : '#';
					$network_label = isset( $social_labels[ $network_slug ] ) ? $social_labels[ $network_slug ] : ucfirst( (string) $network_slug );
					$is_external   = '#' !== $network_url;
					?>
					<a
						href="<?php echo esc_url( $network_url ); ?>"
						aria-label="<?php echo esc_attr( $network_label ); ?>"
						<?php if ( $is_external ) : ?>
							target="_blank"
							rel="noopener noreferrer"
						<?php endif; ?>
					>
						<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/social/' . $network_slug . '.png' ); ?>" alt="<?php echo esc_attr( $network_label ); ?>">
					</a>
				<?php endforeach; ?>
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
