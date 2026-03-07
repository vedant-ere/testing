/**
 * Crew meta box interactions.
 *
 * - Add/remove crew selections per role.
 * - Prevent duplicates by disabling chosen options.
 */
(() => {
	const crewI18n = window.rtMovieCrewL10n || {};

	/**
	 * Returns translated text if available.
	 *
	 * @param {string} key      Translation key in localized object.
	 * @param {string} fallback Fallback text.
	 * @return {string} Translated label.
	 */
	function t(key, fallback = '') {
		const value = crewI18n[key];
		return typeof value === 'string' && value.length ? value : fallback;
	}

	/**
	 * Disables dropdown options that are already selected in the crew list.
	 *
	 * Prevents duplicate person selection per crew role.
	 *
	 * @return {void}
	 */
	function disableSelectedOptions() {
		const lists = document.querySelectorAll('.rt-crew-selected-list');
		lists.forEach((list) => {
			const crewType = list.dataset.crewType;
			const dropdown = document.querySelector(
				`.rt-crew-dropdown[data-crew-type="${crewType}"]`
			);
			if (!dropdown) {
				return;
			}
			list.querySelectorAll('[data-person-id]').forEach((item) => {
				dropdown.querySelectorAll('option').forEach((opt) => {
					if (opt.value === String(item.dataset.personId)) {
						opt.disabled = true;
					}
				});
			});
		});
	}

	/**
	 * Creates a crew list item row with hidden input and remove action.
	 *
	 * @param {Object}        params          Crew item payload.
	 * @param {string|number} params.id       Person post ID.
	 * @param {string}        params.name     Person display name.
	 * @param {string}        params.crewType Crew role key.
	 * @return {HTMLDivElement} Rendered crew item element.
	 */
	function makeCrewItem({ id, name, crewType }) {
		const item = document.createElement('div');
		item.className =
			crewType === 'actor'
				? 'rt-crew-item rt-crew-actor-item'
				: 'rt-crew-item';
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
			input.placeholder = t('characterPlaceholder');
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
		removeBtn.setAttribute('aria-label', t('removeLabel'));
		removeBtn.innerHTML =
			'<span class="dashicons dashicons-no-alt"></span>';

		item.appendChild(info);
		item.appendChild(hidden);
		item.appendChild(removeBtn);

		return item;
	}

	/**
	 * Renders empty-state message for a crew list.
	 *
	 * @param {HTMLElement} list     Crew list container.
	 * @param {string}      crewType Crew role key.
	 * @return {void}
	 */
	function addMessage(list, crewType) {
		const p = document.createElement('p');
		p.className = 'rt-crew-empty';
		const messageMap =
			crewI18n.emptyMessages && typeof crewI18n.emptyMessages === 'object'
				? crewI18n.emptyMessages
				: {};
		p.textContent = messageMap[crewType] || '';
		list.innerHTML = '';
		list.appendChild(p);
	}

	/**
	 * Binds add/remove behavior for all crew controls in the current screen.
	 *
	 * @return {void}
	 */
	function init() {
		disableSelectedOptions();

		document.querySelectorAll('.rt-crew-add-btn').forEach((button) => {
			button.addEventListener('click', () => {
				const crewType = button.dataset.crewType;
				const dropdown = document.querySelector(
					`.rt-crew-dropdown[data-crew-type="${crewType}"]`
				);
				const list = document.querySelector(
					`.rt-crew-selected-list[data-crew-type="${crewType}"]`
				);
				if (!dropdown || !list) {
					return;
				}

				const selectedId = dropdown.value;
				if (!selectedId) {
					return;
				}

				if (list.querySelector(`[data-person-id="${selectedId}"]`)) {
					dropdown.value = '';
					return;
				}

				const selectedOption = dropdown.options[dropdown.selectedIndex];
				const selectedName = selectedOption
					? selectedOption.dataset.name || selectedOption.textContent
					: '';

				list.querySelectorAll('.rt-crew-empty').forEach((node) =>
					node.remove()
				);

				const item = makeCrewItem({
					id: selectedId,
					name: selectedName,
					crewType,
				});
				list.appendChild(item);

				selectedOption.disabled = true;
				dropdown.value = '';
			});
		});

		document.addEventListener('click', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) {
				return;
			}
			const removeBtn = target.closest('.rt-crew-remove');
			if (!removeBtn) {
				return;
			}

			const item = removeBtn.closest('.rt-crew-item');
			if (!item) {
				return;
			}
			const list = item.closest('.rt-crew-selected-list');
			if (!list) {
				return;
			}
			const crewType = list.dataset.crewType;
			const personId = item.dataset.personId;

			item.remove();

			const dropdown = document.querySelector(
				`.rt-crew-dropdown[data-crew-type="${crewType}"]`
			);
			if (dropdown) {
				const option = dropdown.querySelector(
					`option[value="${personId}"]`
				);
				if (option) {
					option.disabled = false;
				}
			}

			if (!list.querySelector('.rt-crew-item')) {
				addMessage(list, crewType);
			}
		});
	}

	document.addEventListener('DOMContentLoaded', init);
})();
