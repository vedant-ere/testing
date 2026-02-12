<?php
/**
 * Theme footer.
 *
 * @package ScreenTime
 */
?>
<footer class="site-footer">
	<div class="container site-footer__top">
		<div class="site-footer__brand-col">
			<a class="site-logo site-logo--footer" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="site-logo__screen">Screen</span>
				<span class="site-logo__time">Time</span>
			</a>
			<p class="site-footer__heading">Follow Us</p>
			<ul class="social-list" aria-label="Social links">
				<li><a href="#" aria-label="Facebook">f</a></li>
				<li><a href="#" aria-label="Twitter">t</a></li>
				<li><a href="#" aria-label="YouTube">▶</a></li>
				<li><a href="#" aria-label="Instagram">◎</a></li>
				<li><a href="#" aria-label="RSS">◔</a></li>
			</ul>
		</div>

		<div class="site-footer__links-col">
			<h2>Company</h2>
			<ul>
				<li><a href="#">About Us</a></li>
				<li><a href="#">Team</a></li>
				<li><a href="#">Careers</a></li>
				<li><a href="#">Culture</a></li>
				<li><a href="#">Community</a></li>
			</ul>
		</div>

		<div class="site-footer__links-col">
			<h2>Explore</h2>
			<ul>
				<li><a href="#">Movies</a></li>
				<li><a href="#">TV Shows</a></li>
				<li><a href="#">Events</a></li>
				<li><a href="#">Theatre</a></li>
				<li><a href="#">Celebrities</a></li>
			</ul>
		</div>
	</div>

	<div class="container site-footer__divider" aria-hidden="true"></div>

	<div class="container site-footer__bottom">
		<p>© 2022 ScreenTime. All Rights Reserved. Terms of Service | Privacy Policy</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
