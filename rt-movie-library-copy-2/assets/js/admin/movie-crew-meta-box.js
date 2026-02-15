/* global rtMovieCrewL10n */
/**
 * Crew meta box interactions (vanilla JS).
 *
 * - Add/remove crew selections per role.
 * - Prevent duplicates by disabling chosen options.
 * - Uses DOM APIs (no jQuery, no innerHTML for nodes).
 */
(() => {
	/**
	 * Disable options already selected for a crew type.
	 */
	function disableSelectedOptions() {
		const lists = document.querySelectorAll('.rt-crew-selected-list');
		lists.forEach((list) => {
			const crewType = list.dataset.crewType;
			const dropdown = document.querySelector(`.rt-crew-dropdown[data-crew-type="${crewType}"]`);
			if (!dropdown) return;
			list.querySelectorAll('[data-person-id]').forEach((item) => {
				dropdown.querySelectorAll('option').forEach((opt) => {
					if (opt.value === String(item.dataset.personId)) {
						opt.disabled = true;
					}
				});
			});
		});
	}

	function createTextNode(text) {
		return document.createTextNode(text);
	}

	function makeCrewItem({ id, name, crewType }) {
		const item = document.createElement('div');
		item.className = crewType === 'actor' ? 'rt-crew-item rt-crew-actor-item' : 'rt-crew-item';
		item.dataset.personId = String(id);

		const info = document.createElement('div');
		info.className = crewType === 'actor' ? 'rt-crew-actor-info' : '';

		const label = document.createElement('span');
		label.className = 'rt-crew-name';
		label.textContent = name;
		info.appendChild(label);

		if (crewType === 'actor') {
			const input = document.createElement('input');
			input.type = 'text';
			input.name = `rt_movie_actor_character[${id}]`;
			input.placeholder = (window.rtMovieCrewL10n && rtMovieCrewL10n.characterPlaceholder) || 'Character name (optional)';
			input.className = 'rt-character-input';
			info.appendChild(input);
		}

		const hidden = document.createElement('input');
		hidden.type = 'hidden';
		hidden.name = `rt_movie_${crewType}[]`;
		hidden.value = String(id);

		const removeBtn = document.createElement('button');
		removeBtn.type = 'button';
		removeBtn.className = 'button-link rt-crew-remove';
		removeBtn.setAttribute('aria-label', (window.rtMovieCrewL10n && rtMovieCrewL10n.removeLabel) || 'Remove');
		removeBtn.innerHTML = '<span class="dashicons dashicons-no-alt"></span>';

		item.appendChild(info);
		item.appendChild(hidden);
		item.appendChild(removeBtn);

		return item;
	}

	function addMessage(list, crewType) {
		const p = document.createElement('p');
		p.className = 'rt-crew-empty';
		const defaults = {
			director: 'No directors added yet.',
			producer: 'No producers added yet.',
			writer: 'No writers added yet.',
			actor: 'No actors added yet.',
		};
		const messageMap = (window.rtMovieCrewL10n && rtMovieCrewL10n.emptyMessages) || defaults;
		p.textContent = messageMap[crewType] || '';
		list.innerHTML = '';
		list.appendChild(p);
	}

	function init() {
		disableSelectedOptions();

		document.querySelectorAll('.rt-crew-add-btn').forEach((button) => {
			button.addEventListener('click', () => {
				const crewType = button.dataset.crewType;
				const dropdown = document.querySelector(`.rt-crew-dropdown[data-crew-type="${crewType}"]`);
				const list = document.querySelector(`.rt-crew-selected-list[data-crew-type="${crewType}"]`);
				if (!dropdown || !list) return;

				const selectedId = dropdown.value;
				if (!selectedId) return;

				if (list.querySelector(`[data-person-id="${selectedId}"]`)) {
					dropdown.value = '';
					return;
				}

				const selectedOption = dropdown.options[dropdown.selectedIndex];
				const selectedName = selectedOption ? selectedOption.dataset.name || selectedOption.textContent : '';

				list.querySelectorAll('.rt-crew-empty').forEach((node) => node.remove());

				const item = makeCrewItem({ id: selectedId, name: selectedName, crewType });
				list.appendChild(item);

				selectedOption.disabled = true;
				dropdown.value = '';
			});
		});

		document.addEventListener('click', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) return;
			const removeBtn = target.closest('.rt-crew-remove');
			if (!removeBtn) return;

			const item = removeBtn.closest('.rt-crew-item');
			if (!item) return;
			const list = item.closest('.rt-crew-selected-list');
			if (!list) return;
			const crewType = list.dataset.crewType;
			const personId = item.dataset.personId;

			item.remove();

			const dropdown = document.querySelector(`.rt-crew-dropdown[data-crew-type="${crewType}"]`);
			if (dropdown) {
				const option = dropdown.querySelector(`option[value="${personId}"]`);
				if (option) option.disabled = false;
			}

			if (!list.querySelector('.rt-crew-item')) {
				addMessage(list, crewType);
			}
		});
	}

	document.addEventListener('DOMContentLoaded', init);
})();
