<?php
/**
 * Theme header.
 *
 * @package ScreenTime
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="site-header__top-bar">
		<div class="container site-header__top-inner">
			<button class="header-icon-button" type="button" aria-label="Search">
				<span aria-hidden="true">⌕</span>
				<span class="header-action-text">Search</span>
			</button>

			<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Screen Time home">
				<span class="site-logo__screen">Screen</span>
				<span class="site-logo__time">Time</span>
			</a>

			<div class="site-header__actions">
				<a class="header-action-link" href="<?php echo esc_url( wp_login_url() ); ?>">Sign In</a>
				<button class="header-language" type="button" aria-label="Language">ENG ▾</button>
				<button class="header-menu-toggle" type="button" data-mobile-menu-toggle aria-expanded="false" aria-controls="mobile-menu-panel" aria-label="Open menu">☰</button>
			</div>
		</div>
	</div>

	<nav class="site-header__nav-bar" aria-label="Primary navigation">
		<div class="container">
			<?php get_template_part( 'template-parts/navigation' ); ?>
		</div>
	</nav>

	<div id="mobile-menu-panel" class="mobile-menu" hidden>
		<div class="mobile-menu__panel">
			<div class="mobile-menu__top">
				<button class="header-icon-button" type="button" aria-label="Search menu">
					<span aria-hidden="true">⌕</span>
				</button>
				<a class="site-logo site-logo--mobile" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="site-logo__screen">Screen</span>
					<span class="site-logo__time">Time</span>
				</a>
				<button class="header-icon-button" type="button" data-mobile-menu-close aria-label="Close menu">✕</button>
			</div>
			<a href="<?php echo esc_url( wp_login_url() ); ?>" class="mobile-menu__signin">Sign In</a>
			<div class="mobile-menu__group">
				<h2>Explore</h2>
				<?php get_template_part( 'template-parts/navigation' ); ?>
			</div>
			<div class="mobile-menu__group">
				<h2>Settings</h2>
				<p>Language: ENG ▼</p>
			</div>
		</div>
	</div>
</header>
