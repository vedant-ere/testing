/**
 * Screen Time FSE — Navigation & Search Panel JS.
 *
 * Handles three interactive header behaviours:
 *   1. Mobile menu open / close with focus trap and Escape key support.
 *   2. Search panel toggle.
 *   3. Language dropdown toggle.
 *
 * Vanilla JS only, no jQuery dependency.
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

( function () {
	'use strict';

	/* ── Utility ──────────────────────────────────────────────────────── */

	/**
	 * Returns all keyboard-focusable elements within a container.
	 *
	 * @param {HTMLElement} container - Root element to search within.
	 * @return {NodeList} Focusable elements.
	 */
	function getFocusableElements( container ) {
		return container.querySelectorAll(
			'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])'
		);
	}

	/* ── Mobile Menu ──────────────────────────────────────────────────── */

	const mobileToggle = document.getElementById( 'mobile-menu-toggle' );
	const mobilePanel  = document.getElementById( 'mobile-menu-panel' );
	const mobileClose  = document.getElementById( 'mobile-menu-close' );

	if ( mobileToggle && mobilePanel ) {

		/**
		 * Opens the mobile menu overlay.
		 * Traps focus inside the panel and prevents body scroll.
		 */
		function openMobileMenu() {
			mobilePanel.hidden       = false;
			mobilePanel.setAttribute( 'aria-hidden', 'false' );
			mobileToggle.setAttribute( 'aria-expanded', 'true' );
			document.body.classList.add( 'mobile-menu-open' );

			// Move focus to close button.
			if ( mobileClose ) {
				mobileClose.focus();
			}

			document.addEventListener( 'keydown', trapFocusHandler );
		}

		/**
		 * Closes the mobile menu overlay.
		 * Restores body scroll and returns focus to the toggle button.
		 */
		function closeMobileMenu() {
			mobilePanel.hidden       = true;
			mobilePanel.setAttribute( 'aria-hidden', 'true' );
			mobileToggle.setAttribute( 'aria-expanded', 'false' );
			document.body.classList.remove( 'mobile-menu-open' );
			mobileToggle.focus();
			document.removeEventListener( 'keydown', trapFocusHandler );
		}

		/**
		 * Traps keyboard focus inside the open mobile menu.
		 *
		 * @param {KeyboardEvent} event
		 */
		function trapFocusHandler( event ) {
			if ( 'Escape' === event.key ) {
				closeMobileMenu();
				return;
			}

			if ( 'Tab' !== event.key ) {
				return;
			}

			const focusable = getFocusableElements( mobilePanel );
			const first     = focusable[ 0 ];
			const last      = focusable[ focusable.length - 1 ];

			if ( event.shiftKey ) {
				if ( document.activeElement === first ) {
					event.preventDefault();
					last.focus();
				}
			} else if ( document.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		}

		mobileToggle.addEventListener( 'click', openMobileMenu );

		if ( mobileClose ) {
			mobileClose.addEventListener( 'click', closeMobileMenu );
		}

		// Close on overlay backdrop click.
		mobilePanel.addEventListener( 'click', function ( event ) {
			if ( event.target === mobilePanel ) {
				closeMobileMenu();
			}
		} );
	}

	/* ── Search Panel ─────────────────────────────────────────────────── */

	const searchToggle = document.getElementById( 'header-search-toggle' );
	const searchPanel  = document.getElementById( 'site-search-panel' );
	const searchInput  = searchPanel ? searchPanel.querySelector( 'input[type="search"]' ) : null;

	if ( searchToggle && searchPanel ) {

		/**
		 * Toggles the search panel visibility and aria state.
		 */
		function toggleSearch() {
			const isOpen = ! searchPanel.hidden;
			searchPanel.hidden = isOpen;
			searchToggle.setAttribute( 'aria-expanded', String( ! isOpen ) );
			if ( ! isOpen && searchInput ) {
				searchInput.focus();
			}
		}

		searchToggle.addEventListener( 'click', toggleSearch );

		// Mobile search toggle inside the menu panel.
		const mobileSearchToggle = document.getElementById( 'mobile-search-toggle' );
		if ( mobileSearchToggle ) {
			mobileSearchToggle.addEventListener( 'click', toggleSearch );
		}

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && ! searchPanel.hidden ) {
				searchPanel.hidden = true;
				searchToggle.setAttribute( 'aria-expanded', 'false' );
				searchToggle.focus();
			}
		} );
	}

	/* ── Language Dropdown ─────────────────────────────────────────────── */

	const langToggle = document.getElementById( 'header-language-toggle' );
	const langMenu   = document.getElementById( 'header-language-menu' );

	if ( langToggle && langMenu ) {

		/**
		 * Toggles the language dropdown menu.
		 */
		function toggleLangMenu() {
			const isOpen = ! langMenu.hidden;
			langMenu.hidden = isOpen;
			langToggle.setAttribute( 'aria-expanded', String( ! isOpen ) );
		}

		langToggle.addEventListener( 'click', toggleLangMenu );

		// Update displayed language code when an option is selected.
		langMenu.querySelectorAll( '[data-language-option]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				const code = btn.getAttribute( 'data-language-code' ) || 'ENG';
				const current = langToggle.querySelector( '[data-language-current]' );
				if ( current ) {
					current.textContent = code;
				}
				langMenu.hidden = true;
				langToggle.setAttribute( 'aria-expanded', 'false' );
				langToggle.focus();
			} );
		} );

		// Close on outside click.
		document.addEventListener( 'click', function ( event ) {
			if ( ! langToggle.contains( event.target ) && ! langMenu.contains( event.target ) ) {
				langMenu.hidden = true;
				langToggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && ! langMenu.hidden ) {
				langMenu.hidden = true;
				langToggle.setAttribute( 'aria-expanded', 'false' );
				langToggle.focus();
			}
		} );
	}
} () );
