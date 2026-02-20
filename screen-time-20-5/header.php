<?php
/**
 * Theme header.
 *
 * @package ScreenTime
 */

$language_options = array(
	array(
		'code'  => 'ENG',
		'label' => __( 'English', 'screen-time' ),
	),
	array(
		'code'  => 'HIN',
		'label' => __( 'Hindi', 'screen-time' ),
	),
	array(
		'code'  => 'SPA',
		'label' => __( 'Spanish', 'screen-time' ),
	),
);
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
			<button
				class="header-icon-button"
				type="button"
				data-search-toggle
				aria-expanded="false"
				aria-controls="site-search-panel"
				aria-label="<?php esc_attr_e( 'Search', 'screen-time' ); ?>"
			>
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
				<div class="header-language-wrap">
					<button
						class="header-language"
						type="button"
						data-language-toggle
						aria-expanded="false"
						aria-controls="header-language-menu"
						aria-label="<?php esc_attr_e( 'Language', 'screen-time' ); ?>"
					>
						<span data-language-current><?php esc_html_e( 'ENG', 'screen-time' ); ?></span> ▾
					</button>
					<ul id="header-language-menu" class="header-language-menu" data-language-menu hidden>
						<?php foreach ( $language_options as $language_option ) : ?>
							<li>
								<button
									type="button"
									data-language-option
									data-language-code="<?php echo esc_attr( $language_option['code'] ); ?>"
								>
									<?php echo esc_html( $language_option['label'] ); ?>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<button class="header-menu-toggle" type="button" data-mobile-menu-toggle aria-expanded="false" aria-controls="mobile-menu-panel" aria-label="<?php esc_attr_e( 'Open menu', 'screen-time' ); ?>">
					<span class="sr-only"><?php esc_html_e( 'Open menu', 'screen-time' ); ?></span>
					<span class="header-icon" aria-hidden="true">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/icons/burger.png' ); ?>" alt="" width="18" height="18">
					</span>
				</button>
			</div>
		</div>
	</div>

	<div id="site-search-panel" class="site-search-panel" hidden>
		<div class="container">
			<form class="site-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
				<label for="site-search-input" class="sr-only"><?php esc_html_e( 'Search for:', 'screen-time' ); ?></label>
				<input id="site-search-input" type="search" name="s" placeholder="<?php esc_attr_e( 'Search movies, people, or books', 'screen-time' ); ?>" required>
				<button type="submit"><?php esc_html_e( 'Search', 'screen-time' ); ?></button>
			</form>
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
				<button class="header-icon-button" type="button" data-search-toggle aria-expanded="false" aria-controls="site-search-panel" aria-label="<?php esc_attr_e( 'Search', 'screen-time' ); ?>">
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
				<button type="button" class="mobile-menu__section-toggle" data-mobile-section-toggle aria-expanded="true" aria-controls="mobile-explore-links">
					<?php esc_html_e( 'Explore', 'screen-time' ); ?>
				</button>
				<div id="mobile-explore-links" data-mobile-section-content>
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
			</div>
			<div class="mobile-menu__group">
				<button type="button" class="mobile-menu__section-toggle" data-mobile-section-toggle aria-expanded="true" aria-controls="mobile-settings">
					<?php esc_html_e( 'Settings', 'screen-time' ); ?>
				</button>
				<div id="mobile-settings" data-mobile-section-content>
					<div class="mobile-menu__language-wrap">
						<p class="mobile-menu__language"><?php esc_html_e( 'Language:', 'screen-time' ); ?> <span class="mobile-menu__language-value" data-language-current><?php esc_html_e( 'ENG', 'screen-time' ); ?></span></p>
						<ul class="mobile-menu__language-list">
							<?php foreach ( $language_options as $language_option ) : ?>
								<li>
									<button
										type="button"
										data-language-option
										data-language-code="<?php echo esc_attr( $language_option['code'] ); ?>"
									>
										<?php echo esc_html( $language_option['label'] ); ?>
									</button>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</header>
