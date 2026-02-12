(() => {
	const slider = document.querySelector('[data-slider]');

	if (!slider) {
		return;
	}

	const slides = Array.from(slider.querySelectorAll('[data-slide]'));
	const dots = Array.from(slider.querySelectorAll('[data-slider-dot]'));
	const prevButton = slider.querySelector('[data-slider-prev]');
	const nextButton = slider.querySelector('[data-slider-next]');
	let index = 0;

	const render = (newIndex) => {
		index = (newIndex + slides.length) % slides.length;

		slides.forEach((slide, slideIndex) => {
			const isActive = slideIndex === index;
			slide.classList.toggle('is-active', isActive);
			slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
		});

		dots.forEach((dot, dotIndex) => {
			dot.setAttribute('aria-current', dotIndex === index ? 'true' : 'false');
		});
	};

	const next = () => render(index + 1);
	const prev = () => render(index - 1);

	if (prevButton) {
		prevButton.addEventListener('click', prev);
	}

	if (nextButton) {
		nextButton.addEventListener('click', next);
	}

	dots.forEach((dot, dotIndex) => {
		dot.addEventListener('click', () => render(dotIndex));
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'ArrowRight') {
			next();
		}

		if (event.key === 'ArrowLeft') {
			prev();
		}
	});

	render(0);
})();
