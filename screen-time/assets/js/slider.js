/**
 * Home hero slider controller.
 *
 * Powers slide transitions, dot navigation, arrow controls, and autoplay
 * lifecycle behavior (hover/focus pause plus visibility-state handling).
 */
( () => {
	const slider = document.querySelector( '[data-slider]' );

	if ( ! slider ) {
		return;
	}

	const slides = Array.from( slider.querySelectorAll( '[data-slide]' ) );
	const dots = Array.from( slider.querySelectorAll( '[data-slider-dot]' ) );
	const prevButton = slider.querySelector( '[data-slider-prev]' );
	const nextButton = slider.querySelector( '[data-slider-next]' );
	const autoplayDelay = 4500;
	let index = 0;
	let autoplayTimer = null;

	// Circularly wraps indexes so prev/next can loop infinitely.
	const render = ( newIndex ) => {
		index = ( newIndex + slides.length ) % slides.length;

		slides.forEach( ( slide, slideIndex ) => {
			const isActive = slideIndex === index;
			slide.classList.toggle( 'is-active', isActive );
			slide.setAttribute( 'aria-hidden', isActive ? 'false' : 'true' );
		} );

		dots.forEach( ( dot, dotIndex ) => {
			dot.setAttribute( 'aria-current', dotIndex === index ? 'true' : 'false' );
		} );
	};

	const next = () => render( index + 1 );
	const prev = () => render( index - 1 );

	// Start autoplay only when useful and only once.
	const startAutoplay = () => {
		if ( autoplayTimer || slides.length < 2 ) {
			return;
		}

		autoplayTimer = setInterval( next, autoplayDelay );
	};

	const stopAutoplay = () => {
		if ( ! autoplayTimer ) {
			return;
		}

		clearInterval( autoplayTimer );
		autoplayTimer = null;
	};

	if ( prevButton ) {
		prevButton.addEventListener( 'click', prev );
	}

	if ( nextButton ) {
		nextButton.addEventListener( 'click', next );
	}

	dots.forEach( ( dot, dotIndex ) => {
		dot.addEventListener( 'click', () => render( dotIndex ) );
	} );

	slider.addEventListener( 'mouseenter', stopAutoplay );
	slider.addEventListener( 'mouseleave', startAutoplay );
	slider.addEventListener( 'focusin', stopAutoplay );
	slider.addEventListener( 'focusout', startAutoplay );

	document.addEventListener( 'visibilitychange', () => {
		if ( document.hidden ) {
			stopAutoplay();
			return;
		}

		startAutoplay();
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'ArrowRight' ) {
			next();
		}

		if ( event.key === 'ArrowLeft' ) {
			prev();
		}
	} );

	render( 0 );
	startAutoplay();
} )();
