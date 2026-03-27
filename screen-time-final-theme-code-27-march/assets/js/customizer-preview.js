/**
 * Customizer Preview — live CSS custom property updates.
 *
 * Runs inside the Customizer preview iframe. Handles real-time updates
 * for settings that use the postMessage transport, eliminating full-page
 * reloads when only CSS custom properties need to change.
 *
 * Dimension settings are debounced so that rapid keystrokes (e.g. typing
 * "500") batch into a single reflow instead of three (5 → 50 → 500).
 *
 * @param {Object} api wp.customize instance.
 */
( function( api ) {
	'use strict';

	/**
	 * Returns a debounced function that delays invoking `fn` until after
	 * `delay` ms have elapsed since the last call, then runs the update
	 * inside a requestAnimationFrame for optimal paint timing.
	 *
	 * @param {(...args: unknown[]) => void} fn    Callback to debounce.
	 * @param {number}                       delay Milliseconds to wait after the last call.
	 * @return {(...args: unknown[]) => void} Debounced wrapper.
	 */
	function debounce( fn, delay ) {
		let timer = 0;

		return function() {
			const args = arguments;

			clearTimeout( timer );

			timer = setTimeout( function() {
				requestAnimationFrame( function() {
					fn.apply( null, args );
				} );
			}, delay );
		};
	}

	/**
	 * Sets a CSS custom property on :root.
	 *
	 * @param {string} prop CSS custom property name.
	 * @param {string} val  Property value including unit.
	 */
	function setCSSProperty( prop, val ) {
		document.documentElement.style.setProperty( prop, val );
	}

	/**
	 * Binds a Customizer setting to a debounced CSS custom property update.
	 *
	 * @param {string} settingId Customizer setting ID.
	 * @param {string} cssProp   CSS custom property name.
	 * @param {string} unit      CSS unit to append (e.g. 'px').
	 * @param {number} delay     Debounce delay in milliseconds.
	 */
	function bindDimension( settingId, cssProp, unit, delay ) {
		api( settingId, function( value ) {
			value.bind( debounce( function( newVal ) {
				setCSSProperty( cssProp, newVal + unit );
			}, delay ) );
		} );
	}

	/**
	 * Background color — updates --color-page-bg on :root.
	 * No debounce needed; color picker already throttles internally.
	 */
	api( 'screentime_background_color', function( value ) {
		value.bind( function( newVal ) {
			setCSSProperty( '--color-page-bg', newVal );
		} );
	} );

	/*
	 * Dimension settings — debounced at 100 ms to batch rapid keystrokes
	 * and avoid expensive grid reflows on every character typed.
	 */
	bindDimension( 'screentime_sidebar_width', '--screentime-sidebar-width', 'px', 100 );
	bindDimension( 'screentime_movie_image_width', '--screentime-movie-image-width', 'px', 100 );
	bindDimension( 'screentime_movie_image_height', '--screentime-movie-image-height', 'px', 100 );
	bindDimension( 'screentime_person_image_width', '--screentime-person-image-width', 'px', 100 );
	bindDimension( 'screentime_person_image_height', '--screentime-person-image-height', 'px', 100 );
}( wp.customize ) );
