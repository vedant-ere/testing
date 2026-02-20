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
				<span class="header-icon" aria-hidden="true">
					<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/icons/search.png' ); ?>" alt="" width="20" height="20">
				</span>
				<span class="header-action-text">Search</span>
			</button>

			<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Screen Time home">
				<span class="site-logo__screen">Screen</span>
				<span class="site-logo__time">Time</span>
			</a>

			<div class="site-header__actions">
				<a class="header-action-link" href="<?php echo esc_url( wp_login_url() ); ?>">
					<span class="header-user-icon" aria-hidden="true">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/icons/user.png' ); ?>" alt="" width="18" height="18">
					</span>
					Sign In
				</a>
				<button class="header-language" type="button" aria-label="Language">ENG ▾</button>
				<button class="header-menu-toggle" type="button" data-mobile-menu-toggle aria-expanded="false" aria-controls="mobile-menu-panel" aria-label="Open menu">
					<span class="sr-only">Open menu</span>
					<span class="header-icon" aria-hidden="true">
						<svg fill="#ffffffff" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>right-align</title> <path d="M0 6.016v-4h32v4h-32zM4 22.016v-4h28v4h-28zM8 14.016v-4h24v4h-24zM12 30.016v-4h20v4h-20z"></path> </g></svg>
					</span>
				</button>
			</div>
		</div>
	</div>

	<nav class="site-header__nav-bar" aria-label="Primary navigation">
		<div class="container">
			<?php get_template_part( 'template-parts/navigation' ); ?>
		</div>
	</nav>

	<div id="mobile-menu-panel" class="mobile-menu" hidden aria-hidden="true">
		<div class="mobile-menu__panel">
			<div class="mobile-menu__top">
				<button class="header-icon-button" type="button" aria-label="Search">
					<span class="header-icon" aria-hidden="true">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/icons/search.png' ); ?>" alt="" width="20" height="20">
					</span>
				</button>
				<a class="site-logo site-logo--mobile" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="site-logo__screen">Screen</span>
					<span class="site-logo__time">Time</span>
				</a>
				<button class="header-icon-button mobile-menu__close" type="button" data-mobile-menu-close aria-label="Close menu">
					<span class="header-icon" aria-hidden="true">
						<svg width="64px" height="64px" viewBox="-19.71 -19.71 95.75 95.75" xmlns="http://www.w3.org/2000/svg" fill="#ffffffff" stroke="#ffffffff" stroke-width="1.633454"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.33795600000000003"></g><g id="SVGRepo_iconCarrier"> <path id="Path_14" data-name="Path 14" d="M477.613,422.087l25.6-25.6a1.5,1.5,0,0,0-2.122-2.121l-25.6,25.6-25.6-25.6a1.5,1.5,0,1,0-2.121,2.121l25.6,25.6-25.6,25.6a1.5,1.5,0,0,0,2.121,2.122l25.6-25.6,25.6,25.6a1.5,1.5,0,0,0,2.122-2.122Z" transform="translate(-447.328 -393.924)" fill="#ffffffff"></path> </g></svg>
					</span>
				</button>
			</div>
			<a href="<?php echo esc_url( wp_login_url() ); ?>" class="mobile-menu__signin">
				Sign In
			</a>
			<div class="mobile-menu__group">
				<h2>Explore</h2>
				<?php get_template_part( 'template-parts/navigation' ); ?>
			</div>
			<div class="mobile-menu__group">
				<h2>Settings</h2>
				<p class="mobile-menu__language">Language: <span class="mobile-menu__language-value">ENG ▼</span></p>
			</div>
		</div>
	</div>
</header>
