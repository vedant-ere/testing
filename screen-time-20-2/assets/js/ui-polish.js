/**
 * UI polish behavior layer.
 *
 * Adds accessibility interaction controllers.
 */
( () => {
	const mainElement = document.querySelector( 'main' );
	if ( mainElement && ! mainElement.id ) {
		mainElement.id = 'main-content';
	}
} )();

/**
 * Mobile menu focus trap.
 *
 * Keeps keyboard focus inside the open off-canvas menu until it is closed.
 */
( () => {
	const menuPanel = document.getElementById( 'mobile-menu-panel' );
	if ( ! menuPanel ) {
		return;
	}

	const getFocusableElements = () => {
		const selector = [
			'a[href]',
			'button:not([disabled])',
			'input:not([disabled])',
			'select:not([disabled])',
			'textarea:not([disabled])',
			'[tabindex]:not([tabindex="-1"])',
		].join( ',' );

		return Array.from( menuPanel.querySelectorAll( selector ) ).filter(
			( element ) => ! element.hasAttribute( 'hidden' ) && null !== element.offsetParent
		);
	};

	menuPanel.addEventListener( 'keydown', ( event ) => {
		if ( menuPanel.hidden || 'Tab' !== event.key ) {
			return;
		}

		const focusable = getFocusableElements();
		if ( ! focusable.length ) {
			return;
		}

		const first = focusable[ 0 ];
		const last = focusable[ focusable.length - 1 ];
		const active = menuPanel.ownerDocument.activeElement;

		if ( event.shiftKey && active === first ) {
			event.preventDefault();
			last.focus();
			return;
		}

		if ( ! event.shiftKey && active === last ) {
			event.preventDefault();
			first.focus();
		}
	} );
} )();

/**
 * Search panel state class.
 *
 * Mirrors hidden state into a class for CSS transition hooks.
 */
( () => {
	const searchPanel = document.getElementById( 'site-search-panel' );
	if ( ! searchPanel ) {
		return;
	}

	const syncState = () => {
		searchPanel.classList.toggle( 'is-open', ! searchPanel.hidden );
	};

	const observer = new MutationObserver( syncState );
	observer.observe( searchPanel, { attributes: true, attributeFilter: [ 'hidden' ] } );
	syncState();
} )();
