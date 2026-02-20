/**
 * Basic client-side validation for Movie meta box.
 * - Prevents saving ratings outside 1-10 range.
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
				ratingInput.setCustomValidity('Rating must be between 1.0 and 10.0');
				message.textContent = 'Rating must be between 1.0 and 10.0';
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

	document.addEventListener('DOMContentLoaded', initRatingValidation);
})();
