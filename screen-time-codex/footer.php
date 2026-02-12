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
				<li><a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" focusable="false"><path d="M14 8h3V4h-3c-2.8 0-5 2.2-5 5v3H6v4h3v4h4v-4h3l1-4h-4V9c0-.6.4-1 1-1z"/></svg></a></li>
				<li><a href="#" aria-label="Twitter"><svg viewBox="0 0 24 24" focusable="false"><path d="M22 6.1c-.7.3-1.5.5-2.2.6a3.8 3.8 0 0 0 1.7-2.1c-.8.5-1.7.8-2.6 1a3.8 3.8 0 0 0-6.5 3.5A10.8 10.8 0 0 1 4 5.2a3.8 3.8 0 0 0 1.2 5.1c-.6 0-1.2-.2-1.7-.5v.1a3.8 3.8 0 0 0 3 3.7c-.5.1-1 .1-1.5.1a3.8 3.8 0 0 0 3.6 2.6A7.7 7.7 0 0 1 3 18.1a10.8 10.8 0 0 0 5.8 1.7c7 0 10.8-5.8 10.8-10.8v-.5c.7-.5 1.4-1.2 1.9-1.9z"/></svg></a></li>
				<li><a href="#" aria-label="YouTube"><svg viewBox="0 0 24 24" focusable="false"><path d="M22 12c0-2.7-.3-4.4-.8-5.2-.5-.8-1.4-1.2-2.8-1.3C16.7 5.3 14.8 5.2 12 5.2s-4.7.1-6.4.3c-1.4.1-2.3.5-2.8 1.3C2.3 7.6 2 9.3 2 12s.3 4.4.8 5.2c.5.8 1.4 1.2 2.8 1.3 1.7.2 3.6.3 6.4.3s4.7-.1 6.4-.3c1.4-.1 2.3-.5 2.8-1.3.5-.8.8-2.5.8-5.2zm-12.4 3.4V8.6l5.8 3.4-5.8 3.4z"/></svg></a></li>
				<li><a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" focusable="false"><path d="M16.5 3h-9A4.5 4.5 0 0 0 3 7.5v9A4.5 4.5 0 0 0 7.5 21h9a4.5 4.5 0 0 0 4.5-4.5v-9A4.5 4.5 0 0 0 16.5 3zm2.7 13.5a2.7 2.7 0 0 1-2.7 2.7h-9a2.7 2.7 0 0 1-2.7-2.7v-9a2.7 2.7 0 0 1 2.7-2.7h9a2.7 2.7 0 0 1 2.7 2.7v9z"/><circle cx="12" cy="12" r="3.4"/><circle cx="17.4" cy="6.6" r="1"/></svg></a></li>
				<li><a href="#" aria-label="RSS"><svg viewBox="0 0 24 24" focusable="false"><circle cx="6" cy="18" r="2"/><path d="M4 10a10 10 0 0 1 10 10h3A13 13 0 0 0 4 7v3zm0-6v3c10.5 0 19 8.5 19 19h3C26 13.3 14.7 2 4 2z"/></svg></a></li>
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
		<p>© 2022 ScreenTime. All Rights Reserved.</p>
		<p class="site-footer__bottom-links"><a href="#">Terms of Service</a> | <a href="#">Privacy Policy</a></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
