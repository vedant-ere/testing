<?php
/**
 * Title: Screen Time Header
 * Slug: screen-time-fse/screen-time-header
 * Categories: header
 * Inserter: false
 *
 * @package ScreenTimeFSE
 */

?>
<!-- wp:group {"tagName":"header","className":"screen-time-header-wrapper","style":{"spacing":{"blockGap":"0"}}} -->
<header class="wp-block-group screen-time-header-wrapper">

	<!-- wp:group {"className":"desktop-header-only","style":{"spacing":{"blockGap":"0"}},"backgroundColor":"page-bg","layout":{"type":"constrained"}} -->
	<div class="wp-block-group desktop-header-only has-page-bg-background-color has-background">
		<!-- wp:group {"className":"header-top-bar","style":{"dimensions":{"minHeight":"120px"}},"backgroundColor":"page-bg","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
		<div class="wp-block-group header-top-bar has-page-bg-background-color has-background" style="min-height:120px">
			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
			<div class="wp-block-group">
				<!-- wp:image {"id":343,"sizeSlug":"full","linkDestination":"none"} -->
				<figure class="wp-block-image size-full"><img src="http://rt-movie-plugin-assignment.local/wp-content/uploads/2026/04/search-1.png" alt="" class="wp-image-343"/></figure>
				<!-- /wp:image -->

				<!-- wp:paragraph {"className":"header-search-label"} -->
				<p class="header-search-label">search</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
			<div class="wp-block-group"><!-- wp:site-logo {"width":153} /--></div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"header-actions-desktop","layout":{"type":"flex","flexWrap":"nowrap"}} -->
			<div class="wp-block-group header-actions-desktop">
				<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|xs"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
				<div class="wp-block-group">
					<!-- wp:image {"id":537,"sizeSlug":"full","linkDestination":"none"} -->
					<figure class="wp-block-image size-full"><img src="http://rt-movie-plugin-assignment.local/wp-content/uploads/2026/04/user-1.png" alt="" class="wp-image-537"/></figure>
					<!-- /wp:image -->

					<!-- wp:paragraph -->
					<p>SIGN IN</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:paragraph -->
				<p>ENG ▼</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:navigation {"ref":285,"backgroundColor":"nav-bg","icon":"menu","align":"full","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","justifyContent":"center","orientation":"horizontal","flexWrap":"wrap"}} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"mobile-only-header","style":{"spacing":{"blockGap":"0"}}} -->
	<div class="wp-block-group mobile-only-header">
		<!-- wp:html -->
		<div class="mobile-header-bar">
			<div class="mobile-search-trigger">
				<img src="http://rt-movie-plugin-assignment.local/wp-content/uploads/2026/04/search-1.png" alt="Search" />
			</div>
			<div class="mobile-logo">
				<a href="/" class="custom-logo-link" rel="home">
					<span style="color:white; font-family:'Big Shoulders Display', sans-serif; font-weight:900; font-size:28px; letter-spacing:1px; text-transform:uppercase;">SCREEN <span style="color:#d13223">TIME</span></span>
				</a>
			</div>
			<button class="mobile-menu-trigger" aria-label="Open Menu">
				<span></span><span></span><span></span><span></span>
			</button>
		</div>

		<div class="mobile-menu-drawer">
			<div class="drawer-top-row">
				<div class="mobile-search-trigger">
					<img src="http://rt-movie-plugin-assignment.local/wp-content/uploads/2026/04/search-1.png" alt="Search" />
				</div>
				<div class="mobile-logo">
					<span style="color:white; font-family:'Big Shoulders Display', sans-serif; font-weight:900; font-size:28px; letter-spacing:1px; text-transform:uppercase;">SCREEN <span style="color:#d13223">TIME</span></span>
				</div>
				<button class="drawer-close-btn" aria-label="Close Menu">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</div>
			<div class="drawer-content">
				<div class="drawer-accordion is-open">
					<div class="drawer-accordion-header">EXPLORE</div>
					<div class="drawer-accordion-content">
						<ul class="drawer-menu-list">
							<li class="drawer-menu-item"><a href="#">MOVIES</a></li>
							<li class="drawer-menu-item"><a href="#">TV SHOWS</a></li>
							<li class="drawer-menu-item"><a href="#">EVENTS</a></li>
							<li class="drawer-menu-item"><a href="#">THEATRE</a></li>
							<li class="drawer-menu-item"><a href="#">CELEBRITIES</a></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->

</header>
<!-- /wp:group -->