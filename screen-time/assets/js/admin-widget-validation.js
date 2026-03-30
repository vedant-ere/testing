/**
 * Client-side validation for recommendation widget admin forms.
 *
 * Provides immediate visual feedback (red border + error text) when an
 * admin enters an invalid count or title value. Uses event delegation on
 * the document so it works for dynamically-loaded widget forms in both
 * the classic Widgets screen and the block-based Widgets editor.
 *
 * @package
 */
( function() {
	'use strict';

	const MIN_COUNT = 1;
	const MAX_COUNT = 12;
	const MAX_TITLE_LENGTH = 100;

	const INVALID_CLASS = 'screentime-widget-field--invalid';

	const i18n = window.screentimeWidgetValidation || {};

	/**
	 * Toggle the error state on an input and its sibling error span.
	 *
	 * @param {HTMLInputElement} input   The input element to validate.
	 * @param {string}           message Error message (empty = valid).
	 */
	function setError( input, message ) {
		const wrapper = input.closest( 'p' );
		let error = null;

		if ( wrapper ) {
			error = wrapper.querySelector( '.screentime-widget-error' );
		}

		if ( ! error ) {
			return;
		}

		error.textContent = message;

		if ( message ) {
			error.style.display = 'block';
		} else {
			error.style.display = 'none';
		}

		if ( '' !== message ) {
			input.classList.add( INVALID_CLASS );
		} else {
			input.classList.remove( INVALID_CLASS );
		}
	}

	/**
	 * Validate the count field (required, integer, 1–12).
	 *
	 * @param {HTMLInputElement} input The count input element.
	 * @return {void}
	 */
	function validateCount( input ) {
		const raw = input.value.trim();
		const value = Number( raw );
		let msg = '';

		if ( '' === raw ) {
			if ( i18n.countRequired ) {
				msg = i18n.countRequired;
			} else {
				msg = 'Count is required.';
			}
		} else if ( ! Number.isInteger( value ) ) {
			if ( i18n.countInteger ) {
				msg = i18n.countInteger;
			} else {
				msg = 'Count must be a whole number.';
			}
		} else if ( value < MIN_COUNT ) {
			if ( i18n.countMin ) {
				msg = i18n.countMin;
			} else {
				msg = 'Count must be at least ' + MIN_COUNT + '.';
			}
		} else if ( value > MAX_COUNT ) {
			if ( i18n.countMax ) {
				msg = i18n.countMax;
			} else {
				msg = 'Count must be at most ' + MAX_COUNT + '.';
			}
		}

		setError( input, msg );
	}

	/**
	 * Validate the title field (max-length soft limit).
	 *
	 * @param {HTMLInputElement} input The title input element.
	 * @return {void}
	 */
	function validateTitle( input ) {
		let msg = '';

		if ( input.value.length > MAX_TITLE_LENGTH ) {
			if ( i18n.titleMax ) {
				msg = i18n.titleMax;
			} else {
				msg = 'Title must be ' + MAX_TITLE_LENGTH + ' characters or fewer.';
			}
		}

		setError( input, msg );
	}

	// ── Event delegation ──────────────────────────────────────────────────

	document.addEventListener( 'input', function( e ) {
		if ( e.target.classList.contains( 'screentime-widget-count' ) ) {
			validateCount( e.target );
		}

		if ( e.target.classList.contains( 'screentime-widget-title' ) ) {
			validateTitle( e.target );
		}
	} );

	// Catch spinner clicks and paste events that fire `change` without `input`.
	document.addEventListener( 'change', function( e ) {
		if ( e.target.classList.contains( 'screentime-widget-count' ) ) {
			validateCount( e.target );
		}
	} );
}() );
