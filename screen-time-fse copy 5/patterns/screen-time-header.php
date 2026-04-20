<?php
/**
 * Title: Screen Time Header
 * Slug: screen-time-fse/screen-time-header
 * Categories: header
 * Inserter: false
 *
 * Single source of truth for the site header.
 * Both the desktop and mobile variants live here.
 * The template part (parts/screen-time-header-template-part.html)
 * is a thin shell that calls this pattern.
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

$assets = get_template_directory_uri() . '/assets/images/';
?>
<!-- wp:html -->
<div
    id="header-search-overlay"
    class="header-search-overlay"
    role="dialog"
    aria-modal="true"
    aria-label="<?php esc_attr_e( 'Search', 'screen-time-fse' ); ?>"
    aria-hidden="true"
>
    <div class="search-overlay-inner">
        <button
            class="search-overlay-close"
            aria-label="<?php esc_attr_e( 'Close search', 'screen-time-fse' ); ?>"
        >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <form
            role="search"
            method="get"
            action="<?php echo esc_url( home_url( '/' ) ); ?>"
            class="search-overlay-form"
        >
            <label for="header-search-input" class="screen-reader-text">
                <?php esc_html_e( 'Search movies', 'screen-time-fse' ); ?>
            </label>
            <input
                id="header-search-input"
                type="search"
                name="s"
                placeholder="<?php esc_attr_e( 'Search movies\u2026', 'screen-time-fse' ); ?>"
                autocomplete="off"
                class="search-overlay-input"
            />
            <button
                type="submit"
                class="search-overlay-submit"
                aria-label="<?php esc_attr_e( 'Submit search', 'screen-time-fse' ); ?>"
            >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                    <path d="M21 21l-4.35-4.35" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </form>
    </div>
</div>
<!-- /wp:html -->

<!-- wp:group {"tagName":"header","className":"screen-time-header-wrapper","style":{"spacing":{"blockGap":"0"}}} -->
<header class="wp-block-group screen-time-header-wrapper">

    <!-- wp:group {
        "className":"desktop-header-only",
        "style":{"spacing":{"blockGap":"0"}},
        "backgroundColor":"page-bg",
        "layout":{"type":"constrained"}
    } -->
    <div class="wp-block-group desktop-header-only has-page-bg-background-color has-background">

        <!-- wp:group {
            "className":"header-top-bar",
            "style":{"dimensions":{"minHeight":"120px"}},
            "backgroundColor":"page-bg",
            "layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}
        } -->
        <div class="wp-block-group header-top-bar has-page-bg-background-color has-background" style="min-height:120px">

            <!-- ── Left: Search trigger ──────────────────────── -->
            <!-- wp:html -->
            <button
                class="header-search-trigger"
                aria-label="<?php esc_attr_e( 'Open search', 'screen-time-fse' ); ?>"
                aria-controls="header-search-overlay"
                aria-expanded="false"
                type="button"
            >
                <img
                    src="<?php echo esc_url( $assets . 'search.png' ); ?>"
                    alt=""
                    aria-hidden="true"
                    width="20"
                    height="20"
                    loading="lazy"
                />
                <span class="header-search-label">
                    <?php esc_html_e( 'search', 'screen-time-fse' ); ?>
                </span>
            </button>
            <!-- /wp:html -->

            <!-- ── Centre: Site logo ─────────────────────────── -->
            <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
            <div class="wp-block-group">
                <!-- wp:site-logo {"width":153} /-->
            </div>
            <!-- /wp:group -->

            <!-- ── Right: Sign in + Language ────────────────── -->
            <!-- wp:group {
                "className":"header-actions-desktop",
                "layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}
            } -->
            <div class="wp-block-group header-actions-desktop">
                <!-- wp:html -->
                <div class="header-signin-group">
                    <img
                        src="<?php echo esc_url( $assets . 'user.png' ); ?>"
                        alt=""
                        aria-hidden="true"
                        width="18"
                        height="18"
                        loading="lazy"
                    />
                    <span><?php esc_html_e( 'SIGN IN', 'screen-time-fse' ); ?></span>
                </div>
                <!-- /wp:html -->

                <!-- wp:html -->
                <!--
                    Language selector — UI placeholder.
                    Replace this entire wp:html block with a Polylang or WPML
                    language switcher shortcode when multilingual support is needed:

                    <!-- wp:shortcode -->
                    [polylang_switcher display="name" /]
                    <!-- /wp:shortcode -->
                -->
                <div class="header-language-selector">
                    <label for="header-lang-select" class="screen-reader-text">
                        <?php esc_html_e( 'Select language', 'screen-time-fse' ); ?>
                    </label>
                    <select
                        id="header-lang-select"
                        class="language-dropdown"
                        aria-label="<?php esc_attr_e( 'Language', 'screen-time-fse' ); ?>"
                    >
                        <option value="en" selected>
                            <?php esc_html_e( 'ENG', 'screen-time-fse' ); ?>
                        </option>
                    </select>
                    <span class="language-dropdown-chevron" aria-hidden="true">&#9660;</span>
                </div>
                <!-- /wp:html -->
            </div>
            <!-- /wp:group -->

        </div>
        <!-- /wp:group -->

        <!-- wp:navigation {
            "ref":285,
            "backgroundColor":"nav-bg",
            "icon":"menu",
            "align":"full",
            "style":{"spacing":{"blockGap":"0"}},
            "layout":{
                "type":"flex",
                "justifyContent":"center",
                "orientation":"horizontal",
                "flexWrap":"wrap"
            }
        } /-->

    </div>
    <!-- /wp:group -->

    <!-- ── Mobile header ─────────────────────────────────────── -->
    <!-- wp:group {"className":"mobile-header-only","style":{"spacing":{"blockGap":"0"}}} -->
    <div class="wp-block-group mobile-header-only">
        <!-- wp:html -->
        <div class="mobile-header-bar" role="banner">

            <!-- Left: Search trigger -->
            <button
                class="mobile-search-trigger header-search-trigger"
                aria-label="<?php esc_attr_e( 'Open search', 'screen-time-fse' ); ?>"
                aria-controls="header-search-overlay"
                aria-expanded="false"
                type="button"
            >
                <img
                    src="<?php echo esc_url( $assets . 'search.png' ); ?>"
                    alt=""
                    aria-hidden="true"
                    width="20"
                    height="20"
                    loading="lazy"
                />
            </button>

            <!-- Centre: Logo -->
            <a
                href="<?php echo esc_url( home_url( '/' ) ); ?>"
                rel="home"
                class="mobile-logo-link"
                aria-label="<?php esc_attr_e( 'Screen Time — Home', 'screen-time-fse' ); ?>"
            >
                <span class="mobile-logo-text" aria-hidden="true">
                    <?php esc_html_e( 'SCREEN', 'screen-time-fse' ); ?>
                    <span class="mobile-logo-accent">
                        <?php esc_html_e( 'TIME', 'screen-time-fse' ); ?>
                    </span>
                </span>
            </a>

            <!-- Right: Hamburger -->
            <button
                class="mobile-menu-trigger"
                aria-label="<?php esc_attr_e( 'Open navigation menu', 'screen-time-fse' ); ?>"
                aria-controls="mobile-menu-drawer"
                aria-expanded="false"
                type="button"
            >
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </button>
        </div>

        <!-- Mobile drawer -->
        <nav
            id="mobile-menu-drawer"
            class="mobile-menu-drawer"
            aria-label="<?php esc_attr_e( 'Mobile navigation', 'screen-time-fse' ); ?>"
            aria-hidden="true"
        >
            <div class="drawer-top-row">
                <button
                    class="mobile-search-trigger header-search-trigger"
                    aria-label="<?php esc_attr_e( 'Open search', 'screen-time-fse' ); ?>"
                    aria-controls="header-search-overlay"
                    aria-expanded="false"
                    type="button"
                >
                    <img
                        src="<?php echo esc_url( $assets . 'search.png' ); ?>"
                        alt=""
                        aria-hidden="true"
                        width="20"
                        height="20"
                        loading="lazy"
                    />
                </button>

                <span class="mobile-logo-text" aria-hidden="true">
                    <?php esc_html_e( 'SCREEN', 'screen-time-fse' ); ?>
                    <span class="mobile-logo-accent">
                        <?php esc_html_e( 'TIME', 'screen-time-fse' ); ?>
                    </span>
                </span>

                <button
                    class="drawer-close-btn"
                    aria-label="<?php esc_attr_e( 'Close navigation menu', 'screen-time-fse' ); ?>"
                    type="button"
                >
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <div class="drawer-content">
                <div class="drawer-accordion is-open">
                    <!--
                        WCAG 2.1 SC 4.1.2: The accordion header must be a <button>
                        (not a <div>) so it is keyboard-operable and has an
                        implicit role of "button" for AT users.
                    -->
                    <button
                        class="drawer-accordion-header"
                        aria-expanded="true"
                        aria-controls="drawer-explore-list"
                        type="button"
                    >
                        <?php esc_html_e( 'EXPLORE', 'screen-time-fse' ); ?>
                    </button>
                    <ul id="drawer-explore-list" class="drawer-menu-list">
                        <li class="drawer-menu-item">
                            <a href="<?php echo esc_url( home_url( '/rt-movie/' ) ); ?>">
                                <?php esc_html_e( 'MOVIES', 'screen-time-fse' ); ?>
                            </a>
                        </li>
                        <li class="drawer-menu-item">
                            <a href="#">
                                <?php esc_html_e( 'TV SHOWS', 'screen-time-fse' ); ?>
                            </a>
                        </li>
                        <li class="drawer-menu-item">
                            <a href="#">
                                <?php esc_html_e( 'EVENTS', 'screen-time-fse' ); ?>
                            </a>
                        </li>
                        <li class="drawer-menu-item">
                            <a href="#">
                                <?php esc_html_e( 'THEATRE', 'screen-time-fse' ); ?>
                            </a>
                        </li>
                        <li class="drawer-menu-item">
                            <a href="#">
                                <?php esc_html_e( 'CELEBRITIES', 'screen-time-fse' ); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <!-- /wp:html -->
    </div>
    <!-- /wp:group -->

</header>
<!-- /wp:group -->