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
 * Header search panel controller.
 *
 * Opens/closes the shared search panel from header action buttons.
 */
( () => {
	const searchPanel = document.getElementById( 'site-search-panel' );
	const searchToggles = document.querySelectorAll( '[data-search-toggle]' );

	if ( ! searchPanel || ! searchToggles.length ) {
		return;
	}

	const searchInput = searchPanel.querySelector( 'input[type="search"]' );

	const setExpanded = ( isOpen ) => {
		searchPanel.hidden = ! isOpen;
		searchToggles.forEach( ( toggle ) => {
			toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		} );
	};

	searchToggles.forEach( ( toggle ) => {
		toggle.addEventListener( 'click', () => {
			const isOpen = searchPanel.hidden;
			setExpanded( isOpen );

			if ( isOpen && searchInput ) {
				window.requestAnimationFrame( () => searchInput.focus() );
			}
		} );
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key && ! searchPanel.hidden ) {
			setExpanded( false );
		}
	} );
} )();

/**
 * Header language selector controller.
 *
 * Shows language choices and mirrors selected code in header/mobile labels.
 */
( () => {
	const languageToggle = document.querySelector( '[data-language-toggle]' );
	const languageMenu = document.querySelector( '[data-language-menu]' );
	const languageOptions = document.querySelectorAll( '[data-language-option]' );
	const currentLabels = document.querySelectorAll( '[data-language-current]' );

	if ( ! languageOptions.length ) {
		return;
	}

	const setCurrentLanguage = ( code ) => {
		currentLabels.forEach( ( node ) => {
			node.textContent = code;
		} );
	};

	if ( languageToggle && languageMenu ) {
		const setMenuOpen = ( isOpen ) => {
			languageMenu.hidden = ! isOpen;
			languageToggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		};

		// Always start closed, even if cached DOM state differs.
		setMenuOpen( false );

		languageToggle.addEventListener( 'click', () => {
			setMenuOpen( languageMenu.hidden );
		} );

		document.addEventListener( 'click', ( event ) => {
			const target = event.target;
			if (
				target instanceof Element &&
				! languageToggle.contains( target ) &&
				! languageMenu.contains( target )
			) {
				setMenuOpen( false );
			}
		} );

		document.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key ) {
				setMenuOpen( false );
			}
		} );
	}

	languageOptions.forEach( ( option ) => {
		option.addEventListener( 'click', () => {
			const code = option.getAttribute( 'data-language-code' ) || 'ENG';
			setCurrentLanguage( code );

			if ( languageMenu ) {
				languageMenu.hidden = true;
			}
			if ( languageToggle ) {
				languageToggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	} );
} )();

/**
 * Mobile menu section toggle controller.
 *
 * Keeps Explore and Settings expanded by default, with manual collapse/expand.
 */
( () => {
	const sectionToggles = document.querySelectorAll( '[data-mobile-section-toggle]' );

	if ( ! sectionToggles.length ) {
		return;
	}

	sectionToggles.forEach( ( toggle ) => {
		const contentId = toggle.getAttribute( 'aria-controls' );
		if ( ! contentId ) {
			return;
		}

		const content = document.getElementById( contentId );
		if ( ! content ) {
			return;
		}

		// Expanded by default.
		content.hidden = false;
		toggle.setAttribute( 'aria-expanded', 'true' );

		toggle.addEventListener( 'click', () => {
			const shouldOpen = content.hidden;
			content.hidden = ! shouldOpen;
			toggle.setAttribute( 'aria-expanded', shouldOpen ? 'true' : 'false' );
		} );
	} );
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

/**
 * Person archive load more controller.
 *
 * Appends additional person cards from the REST list endpoint while preserving
 * the archive card classnames expected by existing CSS.
 */
( () => {
	const loadMoreButton = document.querySelector( '[data-person-load-more]' );
	const listContainer = document.querySelector( '[data-person-list]' );
	const statusNode = document.querySelector( '[data-person-load-more-status]' );
	const loadMoreWrap = loadMoreButton
		? loadMoreButton.closest( '.load-more-wrap' )
		: null;
	const config =
		typeof window.screenTimePersonArchive === 'object' &&
		window.screenTimePersonArchive
			? window.screenTimePersonArchive
			: null;

	if ( ! loadMoreButton || ! listContainer || ! config ) {
		return;
	}

	const endpoint = String( config.endpoint || '' );
	const nonce = String( config.nonce || '' );
	const perPage = Number.parseInt( String( config.perPage || '12' ), 10 ) || 12;
	const i18n = config.i18n || {};
	const maxPages = Number.parseInt( loadMoreButton.dataset.maxPages || '1', 10 ) || 1;
	let nextPage = Number.parseInt( loadMoreButton.dataset.nextPage || '2', 10 ) || 2;
	let loading = false;

	const textOrFallback = ( value, fallback ) => {
		const normalized = String( value || '' ).trim();
		return normalized || fallback;
	};
	const defaultButtonText = textOrFallback( i18n.loadMore, 'Load More' );

	const setStatus = ( message ) => {
		if ( statusNode ) {
			statusNode.textContent = message;
		}
	};

	const parser = new window.DOMParser();

	const htmlToText = ( value ) => {
		const parsed = parser.parseFromString( String( value || '' ), 'text/html' );
		return ( parsed.documentElement.textContent || '' ).trim();
	};

	const getPlainExcerpt = ( item ) => {
		const rendered = item?.excerpt?.rendered ? String( item.excerpt.rendered ) : '';
		if ( ! rendered ) {
			return '';
		}

		return htmlToText( rendered );
	};

	const getImageData = ( item ) => {
		const media =
			item?._embedded &&
			Array.isArray( item._embedded[ 'wp:featuredmedia' ] ) &&
			item._embedded[ 'wp:featuredmedia' ].length
				? item._embedded[ 'wp:featuredmedia' ][ 0 ]
				: null;

		if ( ! media ) {
			return {
				src: textOrFallback( config.fallbackImage, '' ),
				alt: '',
			};
		}

		const medium =
			media.media_details &&
			media.media_details.sizes &&
			media.media_details.sizes.medium
				? media.media_details.sizes.medium.source_url
				: '';

		const mediaSrc = textOrFallback( medium || media.source_url, '' );

		return {
			src: mediaSrc || textOrFallback( config.fallbackImage, '' ),
			alt: textOrFallback( media.alt_text, '' ),
		};
	};

	const createElementWithClass = ( tagName, className ) => {
		const node = document.createElement( tagName );
		if ( className ) {
			node.className = className;
		}
		return node;
	};

	const createPersonCard = ( item ) => {
		const card = createElementWithClass( 'article', 'person-card' );
		const imageData = getImageData( item );
		const image = createElementWithClass( 'img', 'person-card__image' );
		const content = createElementWithClass( 'div', 'person-card__content' );
		const name = createElementWithClass( 'h3', 'person-card__name' );
		const dob = createElementWithClass( 'p', 'person-card__dob' );
		const excerpt = createElementWithClass( 'p', 'person-card__excerpt' );
		const link = createElementWithClass( 'a', 'person-card__link' );
		const arrow = createElementWithClass( 'span', '' );

		image.src = imageData.src;
		image.alt = htmlToText( String( item.title?.rendered || '' ) ) + ' portrait';
		image.width = 153;
		image.height = 224;

		name.textContent = htmlToText( String( item.title?.rendered || '' ) );

		const birthdate = textOrFallback( item.birthdate, '' );
		dob.textContent = birthdate
			? textOrFallback( i18n.bornPrefix, 'Born -' ) + ' ' + birthdate
			: '';

		excerpt.textContent = getPlainExcerpt( item );

		link.href = textOrFallback( item.link, '#' );
		link.textContent = textOrFallback( i18n.learnMore, 'Learn more' ) + ' ';
		arrow.setAttribute( 'aria-hidden', 'true' );
		arrow.textContent = '→';
		link.appendChild( arrow );

		content.appendChild( name );
		content.appendChild( dob );
		content.appendChild( excerpt );
		content.appendChild( link );
		card.appendChild( image );
		card.appendChild( content );

		return card;
	};

	const disableIfFinished = () => {
		if ( nextPage > maxPages ) {
			loadMoreButton.disabled = true;
			loadMoreButton.hidden = true;
			setStatus( textOrFallback( i18n.noMore, 'No more people to load.' ) );
		}
	};

	const fetchPage = async ( pageNumber ) => {
		const url = new URL( endpoint );
		url.searchParams.set( 'page', String( pageNumber ) );
		url.searchParams.set( 'per_page', String( perPage ) );
		url.searchParams.set( '_embed', 'wp:featuredmedia' );

		const response = await window.fetch( url.toString(), {
			headers: {
				'X-WP-Nonce': nonce,
			},
		} );

		if ( ! response.ok ) {
			throw new Error( 'Request failed with status ' + response.status );
		}

		return response.json();
	};

	const handleLoadMore = async () => {
		if ( loading || nextPage > maxPages ) {
			return;
		}

		loading = true;
		loadMoreButton.disabled = true;
		loadMoreButton.remove();
		if ( loadMoreWrap ) {
			loadMoreWrap.classList.add( 'is-loading-all' );
		}
		setStatus( textOrFallback( i18n.loading, 'Loading...' ) );

		try {
			for ( let page = nextPage; page <= maxPages; page += 1 ) {
				const items = await fetchPage( page );

				if ( ! Array.isArray( items ) || ! items.length ) {
					break;
				}

				items.forEach( ( item ) => {
					listContainer.appendChild( createPersonCard( item ) );
				} );
			}

			nextPage = maxPages + 1;
			loadMoreButton.dataset.nextPage = String( nextPage );
			setStatus( '' );
			disableIfFinished();
		} catch ( error ) {
			setStatus( textOrFallback( i18n.error, 'Unable to load more people right now.' ) );
		} finally {
			loading = false;
			loadMoreButton.disabled = true;
			loadMoreButton.hidden = true;
			loadMoreButton.textContent = defaultButtonText;
		}
	};

	loadMoreButton.addEventListener( 'click', () => {
		handleLoadMore();
	} );
} )();

/**
 * Movie cast and crew archive load-more controller.
 *
 * Reveals hidden cards in the cast & crew page and removes the trigger button.
 */
( () => {
	const loadMoreButton = document.querySelector( '[data-cast-crew-load-more]' );

	if ( ! loadMoreButton ) {
		return;
	}

	loadMoreButton.addEventListener( 'click', () => {
		const hiddenCards = document.querySelectorAll( '.person-card--hidden' );

		hiddenCards.forEach( ( card ) => {
			card.classList.remove( 'person-card--hidden' );
		} );

		loadMoreButton.remove();
	} );
} )();
