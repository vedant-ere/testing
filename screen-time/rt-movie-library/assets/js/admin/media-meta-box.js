/* global jQuery */

( function( $ ) {
	'use strict';

	function initMediaBox( box ) {
		const button = box.find( '.rt-media-add' );
		const list = box.find( '.rt-media-list' );
		const input = box.find( '.rt-media-input' );
		const multiple = box.data( 'multiple' ) === 1;
		const type = box.data( 'type' );

		let frame;

		button.on( 'click', function( e ) {
			e.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: 'Select Media',
				button: {
					text: 'Use selected media',
				},
				library: {
					type,
				},
				multiple,
			} );

			frame.on( 'select', function() {
				const selection = frame.state().get( 'selection' );
				let ids = [];

				if ( multiple && input.val() ) {
					try {
						ids = JSON.parse( input.val() );
					} catch ( error ) {
						ids = [];
					}
				}

				selection.each( function( attachment ) {
					const data = attachment.toJSON();

					if ( ! ids.includes( data.id ) ) {
						ids.push( data.id );

						let preview = '';

						if ( type === 'image' && data.url ) {
							preview = `<img src="${ data.url }"
								alt="${ data.alt || data.filename }"
								style="max-width:80px;max-height:80px;object-fit:cover;margin-right:10px;border-radius:4px;">`;
						} else if ( type === 'video' && data.url ) {
							preview = `<video src="${ data.url }"
								style="max-width:120px;max-height:80px;margin-right:10px;border-radius:4px;"
								muted
								preload="metadata"></video>`;
						}

						list.append(
							`<div class="rt-media-item" data-id="${ data.id }">
								${ preview }
								<span class="rt-media-filename">${ data.filename }</span>
								<button type="button" class="rt-media-remove" aria-label="Remove ${ data.filename }">×</button>
							</div>`
						);
					}
				} );

				if ( ! multiple ) {
					list.children( ':not(:last)' ).remove();
					ids = ids.slice( -1 );
				}

				input.val( JSON.stringify( ids ) );
			} );

			frame.open();
		} );

		list.on( 'click', '.rt-media-remove', function() {
			$( this ).closest( '.rt-media-item' ).remove();

			const ids = list.find( '.rt-media-item' ).map( function() {
				return $( this ).data( 'id' );
			} ).get();

			input.val( JSON.stringify( ids ) );
		} );
	}

	$( document ).ready( function() {
		$( '.rt-media-box' ).each( function() {
			initMediaBox( $( this ) );
		} );
	} );
}( jQuery ) );
