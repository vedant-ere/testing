/**
 * Mobile navigation controller.
 *
 * Handles open/close state, ARIA attributes, overlay clicks, escape key
 * handling, and viewport reset for the off-canvas mobile menu.
 */
( () => {
	const menuToggle = document.querySelector( '[data-mobile-menu-toggle]' );
	const menuPanel = document.getElementById( 'mobile-menu-panel' );

	if ( ! menuToggle || ! menuPanel ) {
		return;
	}
	const menuClose = document.querySelector( '[data-mobile-menu-close]' );

	// Keep accessibility attributes in sync with the visible menu state.
	const setState = ( isOpen ) => {
		menuToggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		menuToggle.setAttribute( 'aria-label', isOpen ? 'Close menu' : 'Open menu' );
		menuPanel.setAttribute( 'aria-hidden', isOpen ? 'false' : 'true' );
	};

	const openMenu = () => {
		menuPanel.hidden = false;
		requestAnimationFrame( () => {
			menuPanel.classList.add( 'is-open' );
		} );
		document.body.style.overflow = 'hidden';
		setState( true );
	};

	const closeMenu = () => {
		menuPanel.classList.remove( 'is-open' );
		// Wait for CSS transition to finish before re-hiding the panel.
		setTimeout( () => {
			if ( ! menuPanel.classList.contains( 'is-open' ) ) {
				menuPanel.hidden = true;
			}
		}, 220 );
		document.body.style.overflow = '';
		setState( false );
	};

	menuToggle.addEventListener( 'click', () => {
		if ( menuPanel.hidden ) {
			openMenu();
			return;
		}

		closeMenu();
	} );

	if ( menuClose ) {
		menuClose.addEventListener( 'click', closeMenu );
	}

	menuPanel.addEventListener( 'click', ( event ) => {
		if ( event.target === menuPanel ) {
			closeMenu();
		}
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' && ! menuPanel.hidden ) {
			closeMenu();
		}
	} );

	window.addEventListener( 'resize', () => {
		if ( window.innerWidth >= 768 && ! menuPanel.hidden ) {
			closeMenu();
		}
	} );

	closeMenu();
} )();

/**
 * Trailer card controller.
 *
 * Keeps the designed poster-button UI and starts inline playback when a
 * trailer card is clicked. Only one trailer plays at a time.
 */
( () => {
	const trailerCards = document.querySelectorAll(
		'.movie-trailer-card[data-video-url], .person-video-card[data-video-url]'
	);

	if ( ! trailerCards.length ) {
		return;
	}

	const storePreviewState = ( card ) => {
		if ( card.dataset.posterSrc ) {
			return;
		}

		const posterImage = card.querySelector( 'img' );
		if ( ! posterImage ) {
			return;
		}

		card.dataset.posterSrc = posterImage.getAttribute( 'src' ) || '';
		card.dataset.posterAlt = posterImage.getAttribute( 'alt' ) || '';
	};

	const restorePreviewState = ( card ) => {
		const posterSrc = card.dataset.posterSrc || '';
		const posterAlt = card.dataset.posterAlt || '';
		const playClass = card.classList.contains( 'person-video-card' )
			? 'person-video-card__play'
			: 'movie-trailer-card__play';

		card.classList.remove( 'is-playing' );
		card.textContent = '';

		if ( posterSrc ) {
			const image = document.createElement( 'img' );
			image.src = posterSrc;
			image.alt = posterAlt;
			image.width = 384;
			image.height = 246;
			card.appendChild( image );
		}

		const playIcon = document.createElement( 'span' );
		playIcon.className = playClass;
		playIcon.setAttribute( 'aria-hidden', 'true' );
		playIcon.textContent = '▶';
		card.appendChild( playIcon );
	};

	const renderPlayer = ( card ) => {
		const videoUrl = card.dataset.videoUrl || '';
		if ( ! videoUrl ) {
			return;
		}

		const currentPlaying = document.querySelector(
			'.movie-trailer-card.is-playing'
		);

		if ( currentPlaying && currentPlaying !== card ) {
			restorePreviewState( currentPlaying );
		}

		storePreviewState( card );
		card.classList.add( 'is-playing' );
		card.textContent = '';

		const video = document.createElement( 'video' );
		video.controls = true;
		video.autoplay = true;
		video.preload = 'metadata';
		video.playsInline = true;
		video.src = videoUrl;
		video.setAttribute( 'aria-label', card.getAttribute( 'aria-label' ) || '' );

		card.appendChild( video );
	};

	trailerCards.forEach( ( card ) => {
		storePreviewState( card );

		card.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			renderPlayer( card );
		} );
	} );
} )();
