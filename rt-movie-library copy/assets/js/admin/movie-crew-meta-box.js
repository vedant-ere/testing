/* global jQuery */

( function( $ ) {
	'use strict';

	/**
	 * Escape HTML entities.
	 *
	 * @param {string} text Input text.
	 * @return {string} Escaped string.
	 */
	function escapeHtml( text ) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;',
		};

		return String( text ).replace( /[&<>"']/g, function( m ) {
			return map[ m ];
		} );
	}

	/**
	 * Disable already selected options.
	 */
	function initDisabledOptions() {
		$( '.rt-crew-selected-list' ).each( function() {
			const $list = $( this );
			const crewType = $list.data( 'crew-type' );
			const $dropdown = $( `.rt-crew-dropdown[data-crew-type="${ crewType }"]` );

			$list.find( '[data-person-id]' ).each( function() {
				const personId = $( this ).data( 'person-id' );
				$dropdown.find( `option[value="${ personId }"]` ).prop( 'disabled', true );
			} );
		} );
	}

	$( document ).ready( function() {
		$( '.rt-crew-add-btn' ).on( 'click', function() {
			const $button = $( this );
			const crewType = $button.data( 'crew-type' );
			const $dropdown = $( `.rt-crew-dropdown[data-crew-type="${ crewType }"]` );

			const selectedId = $dropdown.val();

			// Guard early
			if ( ! selectedId ) {
				return;
			}

			const $list = $( `.rt-crew-selected-list[data-crew-type="${ crewType }"]` );

			// Duplicate guard — must come BEFORE selectedName assignment
			if ( $list.find( `[data-person-id="${ selectedId }"]` ).length ) {
				$dropdown.val( '' );
				return;
			}

			const selectedName = $dropdown.find( ':selected' ).data( 'name' );

			$list.find( '.rt-crew-empty' ).remove();

			let itemHTML = '';

			if ( crewType === 'actor' ) {
				itemHTML = `
					<div class="rt-crew-item rt-crew-actor-item" data-person-id="${ selectedId }">
						<div class="rt-crew-actor-info">
							<span class="rt-crew-name">${ escapeHtml( selectedName ) }</span>
							<input
								type="text"
								name="rt_movie_actor_character[${ selectedId }]"
								placeholder="Character name (optional)"
								class="rt-character-input"
							>
						</div>
						<input type="hidden" name="rt_movie_${ crewType }[]" value="${ selectedId }">
						<button type="button" class="button-link rt-crew-remove">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>
				`;
			} else {
				itemHTML = `
					<div class="rt-crew-item" data-person-id="${ selectedId }">
						<span class="rt-crew-name">${ escapeHtml( selectedName ) }</span>
						<input type="hidden" name="rt_movie_${ crewType }[]" value="${ selectedId }">
						<button type="button" class="button-link rt-crew-remove">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>
				`;
			}

			$list.append( itemHTML );
			$dropdown.val( '' );
			$dropdown.find( `option[value="${ selectedId }"]` ).prop( 'disabled', true );
		} );

		$( document ).on( 'click', '.rt-crew-remove', function() {
			const $item = $( this ).closest( '.rt-crew-item' );
			const $list = $item.closest( '.rt-crew-selected-list' );
			const crewType = $list.data( 'crew-type' );
			const personId = $item.data( 'person-id' );

			$item.remove();

			const $dropdown = $( `.rt-crew-dropdown[data-crew-type="${ crewType }"]` );
			$dropdown.find( `option[value="${ personId }"]` ).prop( 'disabled', false );

			if ( ! $list.children( '.rt-crew-item' ).length ) {
				const messages = {
					director: 'No directors added yet.',
					producer: 'No producers added yet.',
					writer: 'No writers added yet.',
					actor: 'No actors added yet.',
				};

				$list.html( `<p class="rt-crew-empty">${ messages[ crewType ] }</p>` );
			}
		} );

		initDisabledOptions();
	} );
}( jQuery ) );
