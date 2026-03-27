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
	const uiConfig =
		typeof window.screenTimeUi === 'object' && window.screenTimeUi
			? window.screenTimeUi
			: {};
	const openMenuLabel = String( uiConfig.openMenuLabel || 'Open menu' );
	const closeMenuLabel = String( uiConfig.closeMenuLabel || 'Close menu' );
	const menuClose = document.querySelector( '[data-mobile-menu-close]' );

	// Keep accessibility attributes in sync with the visible menu state.
	/**
	 * Syncs ARIA state with menu visibility.
	 *
	 * @param {boolean} isOpen Whether menu is open.
	 * @return {void}
	 */
	const setState = ( isOpen ) => {
		menuToggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		menuToggle.setAttribute( 'aria-label', isOpen ? closeMenuLabel : openMenuLabel );
		menuPanel.setAttribute( 'aria-hidden', isOpen ? 'false' : 'true' );
	};

	/**
	 * Opens the mobile menu panel.
	 *
	 * @return {void}
	 */
	const openMenu = () => {
		menuPanel.hidden = false;
		requestAnimationFrame( () => {
			menuPanel.classList.add( 'is-open' );
		} );
		document.body.style.overflow = 'hidden';
		setState( true );
	};

	/**
	 * Closes the mobile menu panel.
	 *
	 * @return {void}
	 */
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

	/**
	 * Toggles search panel visibility and ARIA expanded state.
	 *
	 * @param {boolean} isOpen Whether search panel should be open.
	 * @return {void}
	 */
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
	const languageOptions = document.querySelectorAll( '[data-language-option]' );

	if ( ! languageOptions.length ) {
		return;
	}
	const languageToggle = document.querySelector( '[data-language-toggle]' );
	const languageMenu = document.querySelector( '[data-language-menu]' );
	const currentLabels = document.querySelectorAll( '[data-language-current]' );

	/**
	 * Mirrors current language code in all label placeholders.
	 *
	 * @param {string} code Language code.
	 * @return {void}
	 */
	const setCurrentLanguage = ( code ) => {
		currentLabels.forEach( ( node ) => {
			node.textContent = code;
		} );
	};

	if ( languageToggle && languageMenu ) {
		/**
		 * Shows/hides language dropdown and updates toggle ARIA state.
		 *
		 * @param {boolean} isOpen Whether language menu should be open.
		 * @return {void}
		 */
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
 * Single movie genre chip navigation.
 *
 * Preserves existing genre chip styling and adds click/keyboard navigation
 * for taxonomy links through data attributes.
 */
( () => {
	const genreItems = document.querySelectorAll( '[data-genre-link]' );

	if ( ! genreItems.length ) {
		return;
	}

	/**
	 * Navigates to a movie genre URL stored in data attributes.
	 *
	 * @param {Element} node Genre button/link node.
	 * @return {void}
	 */
	const navigateToGenre = ( node ) => {
		const link = node.getAttribute( 'data-genre-link' ) || '';

		if ( ! link ) {
			return;
		}

		window.location.href = link;
	};

	genreItems.forEach( ( item ) => {
		item.addEventListener( 'click', () => {
			navigateToGenre( item );
		} );

		item.addEventListener( 'keydown', ( event ) => {
			if ( 'Enter' === event.key || ' ' === event.key ) {
				event.preventDefault();
				navigateToGenre( item );
			}
		} );
	} );
} )();

/**
 * Trailer and videos modal controller.
 *
 * Opens media cards in a modal dialog. Uses YouTube iframe embed for YouTube
 * links and HTML5 video for direct media URLs.
 */
( () => {
	const trailerCards = document.querySelectorAll(
		'.movie-trailer-card[data-video-url], .person-video-card[data-video-url]'
	);

	if ( ! trailerCards.length ) {
		return;
	}

	let modal = null;
	let modalBody = null;
	let closeButton = null;
	let toggleButton = null;
	let activeTrigger = null;
	let activeMedia = null;
	let activeType = '';
	let youtubePlaying = false;
	let detachKeydownHandler = null;

	/**
	 * Returns whether URL points to a direct playable video file.
	 *
	 * @param {string} url Media URL.
	 * @return {boolean} True when URL looks like a direct video file.
	 */
	const isDirectVideoUrl = ( url ) => {
		return /\.(mp4|webm|ogg|m4v|mov)(?:[?#].*)?$/i.test( String( url || '' ) );
	};

	/**
	 * Returns YouTube video ID when URL is a supported YouTube link.
	 *
	 * @param {string} url Video URL.
	 * @return {string} YouTube video ID or empty string.
	 */
	const getYouTubeId = ( url ) => {
		const source = String( url || '' ).trim();

		if ( ! source ) {
			return '';
		}

		const patterns = [
			/youtu\.be\/([A-Za-z0-9_-]{11})/i,
			/youtube\.com\/watch\?v=([A-Za-z0-9_-]{11})/i,
			/youtube\.com\/embed\/([A-Za-z0-9_-]{11})/i,
			/youtube\.com\/shorts\/([A-Za-z0-9_-]{11})/i,
			/youtube\.com\/.*[?&]v=([A-Za-z0-9_-]{11})/i,
		];

		for ( let i = 0; i < patterns.length; i += 1 ) {
			const match = source.match( patterns[ i ] );
			if ( match && match[ 1 ] ) {
				return match[ 1 ];
			}
		}

		return '';
	};

	/**
	 * Updates modal play/pause button label based on media state.
	 *
	 * @return {void} No return value.
	 */
	const syncToggleLabel = () => {
		if ( ! toggleButton ) {
			return;
		}

		if ( 'youtube' === activeType ) {
			toggleButton.textContent = youtubePlaying ? 'Pause' : 'Play';
			return;
		}

		if ( 'video' === activeType && activeMedia instanceof HTMLVideoElement ) {
			toggleButton.textContent = activeMedia.paused ? 'Play' : 'Pause';
			return;
		}

		toggleButton.textContent = 'Play';
	};

	/**
	 * Sends a command to YouTube iframe player.
	 *
	 * @param {string} command YouTube player command name.
	 * @return {void} No return value.
	 */
	const sendYouTubeCommand = ( command ) => {
		if ( ! ( activeMedia instanceof HTMLIFrameElement ) ) {
			return;
		}

		const payload = JSON.stringify( {
			event: 'command',
			func: command,
			args: [],
		} );

		activeMedia.contentWindow?.postMessage( payload, '*' );
	};

	/**
	 * Generates a thumbnail at 15.5s for direct video URLs when no poster exists.
	 *
	 * @param {Element} card Video card element.
	 * @return {void} No return value.
	 */
	const ensureDirectVideoThumbnail = ( card ) => {
		const existingImage = card.querySelector( 'img' );
		if ( existingImage ) {
			return;
		}

		const videoUrl = String( card.getAttribute( 'data-video-url' ) || '' ).trim();
		if ( ! isDirectVideoUrl( videoUrl ) ) {
			return;
		}

		const probe = document.createElement( 'video' );
		probe.preload = 'metadata';
		probe.muted = true;
		probe.playsInline = true;
		probe.crossOrigin = 'anonymous';
		probe.src = videoUrl;

		const cleanup = () => {
			probe.removeAttribute( 'src' );
			probe.load();
		};

		probe.addEventListener( 'loadedmetadata', () => {
			const safeDuration = Number.isFinite( probe.duration ) ? probe.duration : 0;
			const configuredTime = Number.parseFloat(
				String( card.getAttribute( 'data-thumbnail-time' ) || '15.5' )
			);
			let targetTime = Number.isFinite( configuredTime ) && configuredTime >= 0
				? configuredTime
				: 15.5;

			if ( safeDuration > 0 ) {
				targetTime = Math.min( targetTime, Math.max( safeDuration - 0.1, 0 ) );
			} else {
				targetTime = 0;
			}

			try {
				probe.currentTime = targetTime;
			} catch ( error ) {
				cleanup();
			}
		} );

		probe.addEventListener( 'seeked', () => {
			try {
				const canvas = document.createElement( 'canvas' );
				canvas.width = probe.videoWidth || 384;
				canvas.height = probe.videoHeight || 246;
				const context = canvas.getContext( '2d' );

				if ( ! context ) {
					cleanup();
					return;
				}

				context.drawImage( probe, 0, 0, canvas.width, canvas.height );

				const img = document.createElement( 'img' );
				img.src = canvas.toDataURL( 'image/jpeg', 0.82 );
				img.alt = '';
				img.width = 384;
				img.height = 246;

				const playIcon = card.querySelector(
					'.movie-trailer-card__play, .person-video-card__play'
				);

				if ( playIcon ) {
					card.insertBefore( img, playIcon );
				} else {
					card.appendChild( img );
				}
			} catch ( error ) {
				// Ignore CORS/canvas failures and keep card without poster.
			}

			cleanup();
		} );

		probe.addEventListener( 'error', cleanup );
	};

	/**
	 * Sends play/pause commands to the active media.
	 *
	 * @param {boolean} shouldPlay Whether media should play.
	 * @return {void} No return value.
	 */
	const setPlayback = ( shouldPlay ) => {
		if ( 'youtube' === activeType && activeMedia instanceof HTMLIFrameElement ) {
			sendYouTubeCommand( shouldPlay ? 'playVideo' : 'pauseVideo' );
			youtubePlaying = shouldPlay;
			syncToggleLabel();
			return;
		}

		if ( 'video' === activeType && activeMedia instanceof HTMLVideoElement ) {
			if ( shouldPlay ) {
				void activeMedia.play();
			} else {
				activeMedia.pause();
			}
			syncToggleLabel();
		}
	};

	/**
	 * Toggles play/pause on the active media.
	 *
	 * @return {void} No return value.
	 */
	const togglePlayback = () => {
		if ( 'youtube' === activeType ) {
			setPlayback( ! youtubePlaying );
			return;
		}

		if ( 'video' === activeType && activeMedia instanceof HTMLVideoElement ) {
			setPlayback( activeMedia.paused );
		}
	};

	/**
	 * Closes media modal and restores focus to trigger card.
	 *
	 * @return {void} No return value.
	 */
	const closeModal = () => {
		if ( ! modal || ! modalBody ) {
			return;
		}

		if ( 'youtube' === activeType && activeMedia instanceof HTMLIFrameElement ) {
			sendYouTubeCommand( 'stopVideo' );
			activeMedia.src = 'about:blank';
		}

		if ( 'video' === activeType && activeMedia instanceof HTMLVideoElement ) {
			activeMedia.pause();
			activeMedia.removeAttribute( 'src' );
			activeMedia.load();
		}

		modalBody.textContent = '';
		modal.hidden = true;
		document.body.classList.remove( 'st-video-modal-open' );

		const focusTarget = activeTrigger;
		activeMedia = null;
		activeType = '';
		youtubePlaying = false;

		if ( focusTarget ) {
			focusTarget.focus();
		}

		if ( detachKeydownHandler ) {
			detachKeydownHandler();
			detachKeydownHandler = null;
		}
	};

	/**
	 * Creates modal markup once and wires interaction handlers.
	 *
	 * @return {void} No return value.
	 */
	const ensureModal = () => {
		if ( modal ) {
			return;
		}

		modal = document.createElement( 'div' );
		modal.className = 'st-video-modal';
		modal.setAttribute( 'role', 'dialog' );
		modal.setAttribute( 'aria-modal', 'true' );
		modal.setAttribute( 'aria-label', 'Video player' );
		modal.hidden = true;

		const dialog = document.createElement( 'div' );
		dialog.className = 'st-video-modal__dialog';

		const actions = document.createElement( 'div' );
		actions.className = 'st-video-modal__actions';

		toggleButton = document.createElement( 'button' );
		toggleButton.type = 'button';
		toggleButton.className = 'st-video-modal__button';
		toggleButton.textContent = 'Pause';

		closeButton = document.createElement( 'button' );
		closeButton.type = 'button';
		closeButton.className = 'st-video-modal__button st-video-modal__button--close';
		closeButton.textContent = 'Close';

		modalBody = document.createElement( 'div' );
		modalBody.className = 'st-video-modal__body';

		actions.appendChild( toggleButton );
		actions.appendChild( closeButton );
		dialog.appendChild( actions );
		dialog.appendChild( modalBody );
		modal.appendChild( dialog );
		document.body.appendChild( modal );

		toggleButton.addEventListener( 'click', () => {
			togglePlayback();
		} );

		closeButton.addEventListener( 'click', () => {
			closeModal();
		} );

		modal.addEventListener( 'click', ( event ) => {
			if ( event.target === modal ) {
				closeModal();
			}
		} );

		modal.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key ) {
				event.preventDefault();
				closeModal();
				return;
			}

			if ( ' ' === event.key || 'Spacebar' === event.key ) {
				const activeElement = closeButton.ownerDocument.activeElement;
				if ( activeElement === closeButton ) {
					return;
				}
				event.preventDefault();
				togglePlayback();
			}
		} );
	};

	/**
	 * Registers global keyboard handling while modal is open.
	 *
	 * @return {void} No return value.
	 */
	const attachGlobalKeys = () => {
		if ( detachKeydownHandler ) {
			return;
		}

		const handler = ( event ) => {
			if ( ! modal || modal.hidden ) {
				return;
			}

			if ( 'Escape' === event.key ) {
				event.preventDefault();
				closeModal();
				return;
			}

			if ( ' ' !== event.key && 'Spacebar' !== event.key ) {
				return;
			}

			const target = event.target;
			const isEditable =
				target instanceof HTMLInputElement ||
				target instanceof HTMLTextAreaElement ||
				( target instanceof HTMLElement && target.isContentEditable );

			if ( isEditable ) {
				return;
			}

			event.preventDefault();
			togglePlayback();
		};

		document.addEventListener( 'keydown', handler );
		detachKeydownHandler = () => {
			document.removeEventListener( 'keydown', handler );
		};
	};

	/**
	 * Opens selected card in modal with matching media renderer.
	 *
	 * @param {Element} card Trigger media card.
	 * @return {void} No return value.
	 */
	const openModal = ( card ) => {
		ensureModal();

		if ( ! modal || ! modalBody || ! closeButton || ! toggleButton ) {
			return;
		}

		const videoUrl = String( card.getAttribute( 'data-video-url' ) || '' ).trim();
		if ( ! videoUrl ) {
			return;
		}

		modalBody.textContent = '';
		activeMedia = null;
		activeType = '';
		youtubePlaying = false;
		activeTrigger = card;

		const youtubeId = getYouTubeId( videoUrl );

		if ( youtubeId ) {
			const iframe = document.createElement( 'iframe' );
			iframe.className = 'st-video-modal__frame';
			iframe.src =
				'https://www.youtube.com/embed/' +
				encodeURIComponent( youtubeId ) +
				'?autoplay=1&rel=0&modestbranding=1&playsinline=1&controls=1&enablejsapi=1&origin=' +
				encodeURIComponent( window.location.origin );
			iframe.title = card.getAttribute( 'aria-label' ) || 'Video player';
			iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
			iframe.allowFullscreen = true;

			modalBody.appendChild( iframe );
			activeMedia = iframe;
			activeType = 'youtube';
			youtubePlaying = true;
			syncToggleLabel();
		} else {
			const video = document.createElement( 'video' );
			video.className = 'st-video-modal__video';
			video.src = videoUrl;
			video.controls = true;
			video.autoplay = true;
			video.preload = 'metadata';
			video.playsInline = true;
			video.setAttribute( 'aria-label', card.getAttribute( 'aria-label' ) || 'Video player' );

			video.addEventListener( 'play', syncToggleLabel );
			video.addEventListener( 'pause', syncToggleLabel );
			video.addEventListener( 'ended', syncToggleLabel );

			modalBody.appendChild( video );
			activeMedia = video;
			activeType = 'video';
			syncToggleLabel();
		}

		modal.hidden = false;
		document.body.classList.add( 'st-video-modal-open' );
		attachGlobalKeys();
		closeButton.focus();
	};

	trailerCards.forEach( ( card ) => {
		ensureDirectVideoThumbnail( card );

		card.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			openModal( card );
		} );

		card.addEventListener( 'keydown', ( event ) => {
			if ( 'Enter' === event.key ) {
				event.preventDefault();
				openModal( card );
			}
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
	const config =
		typeof window.screenTimePersonArchive === 'object' &&
		window.screenTimePersonArchive
			? window.screenTimePersonArchive
			: null;

	if ( ! loadMoreButton || ! listContainer || ! config ) {
		return;
	}
	const uiConfig =
		typeof window.screenTimeUi === 'object' && window.screenTimeUi
			? window.screenTimeUi
			: {};
	const uiI18n =
		typeof uiConfig.i18n === 'object' && uiConfig.i18n ? uiConfig.i18n : {};
	const portraitPattern = String( uiConfig.portraitPattern || '%s portrait' );
	const statusNode = document.querySelector( '[data-person-load-more-status]' );
	const loadMoreWrap = loadMoreButton.closest( '.load-more-wrap' );

	const endpoint = String( config.endpoint || '' );
	const nonce = String( config.nonce || '' );
	const perPage = Number.parseInt( String( config.perPage || '12' ), 10 ) || 12;
	const archiveI18n =
		typeof config.i18n === 'object' && config.i18n ? config.i18n : {};
	const maxPages = Number.parseInt( loadMoreButton.dataset.maxPages || '1', 10 ) || 1;
	let nextPage = Number.parseInt( loadMoreButton.dataset.nextPage || '2', 10 ) || 2;
	let loading = false;

	/**
	 * Returns a non-empty text value with fallback support.
	 *
	 * @param {unknown} value    Candidate text value.
	 * @param {string}  fallback Fallback text.
	 * @return {string} Normalized text or the fallback string.
	 */
	const textOrFallback = ( value, fallback ) => {
		const normalized = String( value || '' ).trim();
		return normalized || fallback;
	};
		/**
		 * Resolves a localized string from archive-specific and global UI dictionaries.
		 *
		 * @param {string} key           Localization key.
		 * @param {string} [fallback=''] Fallback string.
		 * @return {string} Localized text for the key.
		 */
	const getI18nString = ( key, fallback = '' ) => {
		return textOrFallback( archiveI18n[ key ], textOrFallback( uiI18n[ key ], fallback ) );
	};
	const defaultButtonText = getI18nString(
		'loadMore',
		textOrFallback( loadMoreButton.textContent, '' )
	);

	/**
	 * Writes polite announcement text for assistive technology.
	 *
	 * @param {string} message Status message.
	 * @return {void}
	 */
	const setStatus = ( message ) => {
		if ( statusNode ) {
			statusNode.textContent = message;
		}
	};

	const parser = new window.DOMParser();

	/**
	 * Converts an HTML string into plain text.
	 *
	 * @param {unknown} value HTML value.
	 * @return {string} Decoded plain-text content.
	 */
	const htmlToText = ( value ) => {
		const parsed = parser.parseFromString( String( value || '' ), 'text/html' );
		return ( parsed.documentElement.textContent || '' ).trim();
	};

	/**
	 * Extracts a plain-text excerpt from REST item payload.
	 *
	 * @param {Object} item REST post object.
	 * @return {string} Plain-text excerpt.
	 */
	const getPlainExcerpt = ( item ) => {
		const rendered = item?.excerpt?.rendered ? String( item.excerpt.rendered ) : '';
		if ( ! rendered ) {
			return '';
		}

		return htmlToText( rendered );
	};

	/**
	 * Returns image source and alt text for a REST person item.
	 *
	 * @param {Object} item REST post object.
	 * @return {{src:string, alt:string}} Image URL and alt text object.
	 */
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

	/**
	 * Creates an element and assigns className when provided.
	 *
	 * @param {string} tagName   HTML tag name.
	 * @param {string} className Optional class name.
	 * @return {HTMLElement} Created DOM node.
	 */
	const createElementWithClass = ( tagName, className ) => {
		const node = document.createElement( tagName );
		if ( className ) {
			node.className = className;
		}
		return node;
	};

	/**
	 * Builds a person archive card DOM node from REST payload.
	 *
	 * @param {Object} item REST post object.
	 * @return {HTMLElement} Rendered person card element.
	 */
	const createPersonCard = ( item ) => {
		const card = createElementWithClass( 'article', 'person-card' );
		const imageData = getImageData( item );
		const content = createElementWithClass( 'div', 'person-card__content' );
		const name = createElementWithClass( 'h3', 'person-card__name' );
		const dob = createElementWithClass( 'p', 'person-card__dob' );
		const excerpt = createElementWithClass( 'p', 'person-card__excerpt' );
		const link = createElementWithClass( 'a', 'person-card__link' );
		const arrow = createElementWithClass( 'span', '' );

		name.textContent = htmlToText( String( item.title?.rendered || '' ) );

		const birthdate = textOrFallback( item.birthdate, '' );
		if ( birthdate ) {
			const bornPrefix = getI18nString( 'bornPrefix' );
			dob.textContent = bornPrefix ? bornPrefix + ' ' + birthdate : birthdate;
		} else {
			dob.textContent = '';
		}

		excerpt.textContent = getPlainExcerpt( item );

		link.href = textOrFallback( item.link, '#' );
		link.textContent = getI18nString( 'learnMore' ) + ' ';
		arrow.setAttribute( 'aria-hidden', 'true' );
		arrow.textContent = '→';
		link.appendChild( arrow );

		content.appendChild( name );
		content.appendChild( dob );
		content.appendChild( excerpt );
		content.appendChild( link );
		if ( imageData.src ) {
			const image = createElementWithClass( 'img', 'person-card__image' );
			image.src = imageData.src;
			image.alt = portraitPattern.replace(
				'%s',
				htmlToText( String( item.title?.rendered || '' ) )
			);
			image.width = 153;
			image.height = 224;
			card.appendChild( image );
		}
		card.appendChild( content );

		return card;
	};

	/**
	 * Hides load-more control when the next page exceeds max pages.
	 *
	 * @return {void}
	 */
	const disableIfFinished = () => {
		if ( nextPage > maxPages ) {
			loadMoreButton.disabled = true;
			loadMoreButton.hidden = true;
			setStatus( getI18nString( 'noMore' ) );
		}
	};

	/**
	 * Fetches a paginated list of people from REST API.
	 *
	 * @param {number} pageNumber Archive page to request.
	 * @return {Promise<Array>} REST payload list for the requested page.
	 */
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

	/**
	 * Loads remaining person pages and appends cards to the archive list.
	 *
	 * @return {Promise<void>}
	 */
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
		setStatus( getI18nString( 'loading' ) );

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
			setStatus( getI18nString( 'error' ) );
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

	disableIfFinished();
} )();

/**
 * Movie archive REST pagination controller.
 *
 * Replaces full-page archive pagination reloads with REST-powered updates
 * while preserving the existing grid and pagination markup.
 */
( () => {
	const archiveContainer = document.querySelector( '[data-movie-archive]' );
	const grid = document.querySelector( '[data-movie-archive-grid]' );
	const config =
		typeof window.screenTimeMovieArchive === 'object' &&
		window.screenTimeMovieArchive
			? window.screenTimeMovieArchive
			: null;

	if ( ! archiveContainer || ! grid || ! config ) {
		return;
	}

	const endpoint = String( config.endpoint || '' );

	if ( ! endpoint ) {
		return;
	}

	const context = String( config.context || 'post-type' );
	const term = String( config.term || '' );

	let loading = false;

	/**
	 * Resolves archive page number from a pagination link.
	 *
	 * @param {HTMLAnchorElement} link Pagination link.
	 * @return {number} Parsed page number, defaults to 1.
	 */
	const parsePageFromLink = ( link ) => {
		const dataPage = Number.parseInt( String( link.dataset.page || '' ), 10 );
		if ( Number.isFinite( dataPage ) && dataPage > 0 ) {
			return dataPage;
		}

		const href = link.getAttribute( 'href' );
		const fallbackText = link.textContent;
		const source = String( href || '' );
		const hashMatch = source.match( /#page-(\d+)/i );
		if ( hashMatch && hashMatch[ 1 ] ) {
			return Number.parseInt( hashMatch[ 1 ], 10 );
		}

		try {
			const url = new URL( source, window.location.origin );
			const pagedParam = url.searchParams.get( 'paged' );
			if ( pagedParam ) {
				const parsedPaged = Number.parseInt( pagedParam, 10 );
				if ( Number.isFinite( parsedPaged ) && parsedPaged > 0 ) {
					return parsedPaged;
				}
			}

			const pathMatch = url.pathname.match( /\/page\/(\d+)\/?$/i );
			if ( pathMatch && pathMatch[ 1 ] ) {
				return Number.parseInt( pathMatch[ 1 ], 10 );
			}
		} catch ( error ) {
			if ( window.console && typeof window.console.warn === 'function' ) {
				window.console.warn( 'Failed to parse pagination URL.', error );
			}
		}

		const textPage = Number.parseInt( String( fallbackText || '' ), 10 );
		return Number.isFinite( textPage ) && textPage > 0 ? textPage : 1;
	};

	/**
	 * Builds the browser URL for a given archive page.
	 *
	 * @param {number} page Page number.
	 * @return {string} Visible URL for browser history.
	 */
	const buildVisibleUrl = ( page ) => {
		const nextUrl = new URL( window.location.href );

		if ( page <= 1 ) {
			nextUrl.searchParams.delete( 'paged' );
		} else {
			nextUrl.searchParams.set( 'paged', String( page ) );
		}

		return nextUrl.toString();
	};

	/**
	 * Fetches archive cards/pagination for a specific movie archive page.
	 *
	 * @param {number} page Page number to request.
	 * @return {Promise<Object>} REST response payload.
	 */
	const fetchPage = async ( page ) => {
		const url = new URL( endpoint, window.location.origin );
		url.searchParams.set( 'page', String( page ) );
		url.searchParams.set( 'context', context );

		if ( term ) {
			url.searchParams.set( 'term', term );
		}

		const response = await window.fetch( url.toString() );

		if ( ! response.ok ) {
			throw new Error( 'Request failed with status ' + response.status );
		}

		return response.json();
	};

	/**
	 * Applies fetched archive markup and optionally updates history state.
	 *
	 * @param {Object}  data        REST response payload.
	 * @param {number}  page        Active page number.
	 * @param {boolean} pushHistory Whether to push a browser history state.
	 * @return {void} No return value.
	 */
	const applyPageData = ( data, page, pushHistory ) => {
		const cardsHtml = String( data.cards_html || '' );
		const paginationHtml = String( data.pagination_html || '' );
		const paginationNode = archiveContainer.querySelector(
			'[data-movie-archive-pagination]'
		);

		grid.innerHTML = cardsHtml;

		if ( paginationNode ) {
			if ( paginationHtml ) {
				paginationNode.outerHTML = paginationHtml;
			} else {
				paginationNode.remove();
			}
		} else if ( paginationHtml ) {
			grid.insertAdjacentHTML( 'afterend', paginationHtml );
		}

		if ( pushHistory ) {
			window.history.pushState(
				{ movieArchivePage: page },
				'',
				buildVisibleUrl( page )
			);
		}
	};

	/**
	 * Loads and applies a movie archive page response.
	 *
	 * @param {number}  page               Page number to load.
	 * @param {boolean} [pushHistory=true] Whether to push browser history state.
	 * @return {Promise<void>} Resolves after page content is applied.
	 */
	const loadPage = async ( page, pushHistory = true ) => {
		if ( loading ) {
			return;
		}

		loading = true;
		archiveContainer.classList.add( 'is-loading' );

		try {
			const data = await fetchPage( page );
			applyPageData( data, page, pushHistory );
			grid.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		} catch ( error ) {
			window.location.href = buildVisibleUrl( page );
		} finally {
			loading = false;
			archiveContainer.classList.remove( 'is-loading' );
		}
	};

	archiveContainer.addEventListener( 'click', ( event ) => {
		const rawTarget = event.target;
		let target = null;

		if ( rawTarget instanceof Element ) {
			target = rawTarget;
		} else if ( rawTarget && rawTarget.parentElement ) {
			target = rawTarget.parentElement;
		}

		if ( ! target ) {
			return;
		}

		const link = target.closest( '[data-movie-archive-pagination] a' );
		if ( ! link ) {
			return;
		}

		event.preventDefault();

		const page = parsePageFromLink( link );

		loadPage( page, true );
	} );

	window.addEventListener( 'popstate', () => {
		const currentPage = Number.parseInt(
			new URL( window.location.href ).searchParams.get( 'paged' ) || '1',
			10
		);

		loadPage( Number.isFinite( currentPage ) && currentPage > 0 ? currentPage : 1, false );
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
