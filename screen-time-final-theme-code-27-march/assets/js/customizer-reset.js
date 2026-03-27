/**
 * Customizer Reset Layout Defaults.
 *
 * Adds a "Reset to Defaults" button at the bottom of the
 * Single Post Pages section.
 *
 * @param {Object} api wp.customize instance.
 */
( function( api ) {
	'use strict';

	const defaults = window.screentimeCustomizerDefaults || {};
	let injected = false;

	/* ---- Reset button ---- */

	api.section( 'screentime_single_post', function( section ) {
		/**
		 * Attempts to inject the reset button into the section container.
		 *
		 * WordPress lazy-renders panel-child sections, so the DOM node may
		 * not exist on the first expand event. This function is called on
		 * every expand and bails out once the button has been added.
		 */
		function maybeInjectButton() {
			if ( injected ) {
				return;
			}

			/*
			 * section.contentContainer and section.container are jQuery objects
			 * in the WordPress Customizer API. Convert to DOM element.
			 */
			let wrap = section.contentContainer || section.container;
			wrap = wrap ? wrap[ 0 ] : null;

			if ( ! wrap ) {
				return;
			}

			// The controls live inside a <ul> with one of these classes.
			let list = wrap.querySelector( 'ul.customize-pane-child' ) ||
				wrap.querySelector( 'ul.accordion-section-content' );

			if ( ! list ) {
				list = wrap;
			}

			// Prevent duplicate buttons on repeated expand / collapse.
			if ( list.querySelector( '.screentime-reset-defaults' ) ) {
				injected = true;
				return;
			}

			const button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'button screentime-reset-defaults';
			button.textContent = defaults.buttonLabel || 'Reset Layout Defaults';
			button.style.marginTop = '16px';
			button.style.marginBottom = '12px';
			button.style.marginLeft = '12px';
			button.style.display = 'block';

			button.addEventListener( 'click', function() {
				Object.entries( defaults.settings || {} ).forEach( ( [ id, defaultValue ] ) => {
					if ( api( id ) ) {
						api( id ).set( defaultValue );
					}
				} );
			} );

			list.appendChild( button );
			injected = true;
		}

		// Inject on expand — may need a short delay for lazy-rendered content.
		section.expanded.bind( function( isExpanded ) {
			if ( ! isExpanded ) {
				return;
			}

			// Try immediately, then retry after WordPress finishes rendering.
			maybeInjectButton();

			if ( ! injected ) {
				setTimeout( maybeInjectButton, 200 );
			}
		} );
	} );
}( wp.customize ) );
