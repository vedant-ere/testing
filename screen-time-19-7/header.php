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
			<button class="header-icon-button" type="button" aria-label="<?php esc_attr_e( 'Search', 'screen-time' ); ?>">
				<span class="header-icon" aria-hidden="true">
					<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/icons/search.png' ); ?>" alt="" width="20" height="20">
				</span>
				<span class="header-action-text"><?php esc_html_e( 'Search', 'screen-time' ); ?></span>
			</button>

			<?php screentime_the_site_logo( 'site-logo' ); ?>

			<div class="site-header__actions">
				<a class="header-action-link" href="<?php echo esc_url( wp_login_url() ); ?>">
					<span class="header-user-icon" aria-hidden="true">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/icons/user.png' ); ?>" alt="" width="18" height="18">
					</span>
					<?php esc_html_e( 'Sign In', 'screen-time' ); ?>
				</a>
				<button class="header-language" type="button" aria-label="<?php esc_attr_e( 'Language', 'screen-time' ); ?>"><?php esc_html_e( 'ENG', 'screen-time' ); ?> ▾</button>
				<button class="header-menu-toggle" type="button" data-mobile-menu-toggle aria-expanded="false" aria-controls="mobile-menu-panel" aria-label="<?php esc_attr_e( 'Open menu', 'screen-time' ); ?>">
					<span class="sr-only"><?php esc_html_e( 'Open menu', 'screen-time' ); ?></span>
					<span class="header-icon" aria-hidden="true">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/icons/burger.png' ); ?>" alt="" width="18" height="18">
					</span>
				</button>
			</div>
		</div>
	</div>

	<nav class="site-header__nav-bar" aria-label="<?php esc_attr_e( 'Primary navigation', 'screen-time' ); ?>">
		<div class="container">
			<?php
			get_template_part(
				'template-parts/navigation',
				null,
				array(
					'theme_location' => 'primary',
					'menu_class'     => 'site-header__menu',
				)
			);
			?>
		</div>
	</nav>

	<div id="mobile-menu-panel" class="mobile-menu" hidden aria-hidden="true">
		<div class="mobile-menu__panel">
			<div class="mobile-menu__top">
				<button class="header-icon-button" type="button" aria-label="<?php esc_attr_e( 'Search', 'screen-time' ); ?>">
					<span class="header-icon" aria-hidden="true">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/icons/search.png' ); ?>" alt="" width="20" height="20">
					</span>
				</button>
				<?php screentime_the_site_logo( 'site-logo site-logo--mobile' ); ?>
				<button class="header-icon-button mobile-menu__close" type="button" data-mobile-menu-close aria-label="<?php esc_attr_e( 'Close menu', 'screen-time' ); ?>">
					<span class="header-icon" aria-hidden="true">
						<svg width="64px" height="64px" viewBox="-19.71 -19.71 95.75 95.75" xmlns="http://www.w3.org/2000/svg" fill="#ffffffff"><path d="M477.613,422.087l25.6-25.6a1.5,1.5,0,0,0-2.122-2.121l-25.6,25.6-25.6-25.6a1.5,1.5,0,1,0-2.121,2.121l25.6,25.6-25.6,25.6a1.5,1.5,0,0,0,2.121,2.122l25.6-25.6,25.6,25.6a1.5,1.5,0,0,0,2.122-2.122Z" transform="translate(-447.328 -393.924)" fill="#ffffffff"></path></svg>
					</span>
				</button>
			</div>
			<a href="<?php echo esc_url( wp_login_url() ); ?>" class="mobile-menu__signin"><?php esc_html_e( 'Sign In', 'screen-time' ); ?></a>
			<div class="mobile-menu__group">
				<h2><?php esc_html_e( 'Explore', 'screen-time' ); ?></h2>
				<?php
				get_template_part(
					'template-parts/navigation',
					null,
					array(
						'theme_location' => 'primary',
						'menu_class'     => 'site-header__menu',
					)
				);
				?>
			</div>
			<div class="mobile-menu__group">
				<h2><?php esc_html_e( 'Settings', 'screen-time' ); ?></h2>
				<p class="mobile-menu__language"><?php esc_html_e( 'Language:', 'screen-time' ); ?> <span class="mobile-menu__language-value"><?php esc_html_e( 'ENG ▼', 'screen-time' ); ?></span></p>
			</div>
		</div>
	</div>
</header>
