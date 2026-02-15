/**
 * Basic client-side validation for Movie meta box.
 * - Prevents saving ratings outside 1-10 range.
 * - Prevents runtime outside 1-300 minutes.
 * - Provides inline feedback using HTML5 validity APIs.
 */
(() => {
	function initRatingValidation() {
		const ratingInput = document.getElementById('rt-movie-rating');
		if (!ratingInput) return;

		ratingInput.setAttribute('min', '1');
		ratingInput.setAttribute('max', '10');
		ratingInput.setAttribute('step', '0.1');

		const form = document.getElementById('post');
		const message = document.createElement('p');
		message.className = 'description';
		message.style.color = '#d63638';
		message.style.display = 'none';
		ratingInput.insertAdjacentElement('afterend', message);

		const updateValidity = () => {
			const value = ratingInput.value.trim();
			if (value === '') {
				ratingInput.setCustomValidity('');
				message.style.display = 'none';
				return true;
			}

			const numberValue = Number(value);
			const valid = Number.isFinite(numberValue) && numberValue >= 1 && numberValue <= 10;
			if (!valid) {
				const text = (window.rtMovieMetaL10n && window.rtMovieMetaL10n.ratingMessage) || 'Rating must be between 1.0 and 10.0';
				ratingInput.setCustomValidity(text);
				message.textContent = text;
				message.style.display = 'block';
			} else {
				ratingInput.setCustomValidity('');
				message.style.display = 'none';
			}
			return valid;
		};

		ratingInput.addEventListener('input', updateValidity);
		ratingInput.addEventListener('blur', updateValidity);

		if (form) {
			form.addEventListener('submit', (event) => {
				if (!updateValidity()) {
					event.preventDefault();
					ratingInput.reportValidity();
				}
			});
		}
	}

	function initRuntimeValidation() {
		const runtimeInput = document.getElementById('rt-movie-runtime');
		if (!runtimeInput) return;

		runtimeInput.setAttribute('min', '1');
		runtimeInput.setAttribute('max', '300');
		runtimeInput.setAttribute('step', '1');

		const form = document.getElementById('post');
		const message = document.createElement('p');
		message.className = 'description';
		message.style.color = '#d63638';
		message.style.display = 'none';
		runtimeInput.insertAdjacentElement('afterend', message);

		const updateValidity = () => {
			const value = runtimeInput.value.trim();
			if (value === '') {
				runtimeInput.setCustomValidity('');
				message.style.display = 'none';
				return true;
			}

			const numberValue = Number(value);
			const valid = Number.isInteger(numberValue) && numberValue >= 1 && numberValue <= 300;
			if (!valid) {
				const text = (window.rtMovieMetaL10n && window.rtMovieMetaL10n.runtimeMessage) || 'Runtime must be between 1 and 300 minutes';
				runtimeInput.setCustomValidity(text);
				message.textContent = text;
				message.style.display = 'block';
			} else {
				runtimeInput.setCustomValidity('');
				message.style.display = 'none';
			}
			return valid;
		};

		runtimeInput.addEventListener('input', updateValidity);
		runtimeInput.addEventListener('blur', updateValidity);

		if (form) {
			form.addEventListener('submit', (event) => {
				if (!updateValidity()) {
					event.preventDefault();
					runtimeInput.reportValidity();
				}
			});
		}
	}

	document.addEventListener('DOMContentLoaded', () => {
		initRatingValidation();
		initRuntimeValidation();
	});
})();
