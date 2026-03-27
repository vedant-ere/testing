/**
 * Media Meta Box interactions
 *
 * - Uses WP media modal to select images/videos.
 * - Renders previews for saved IDs and newly selected files.
 * - Persists selections in hidden input for post save.
 */
(() => {
	const i18n = window.rtMediaBoxL10n || {};

	/**
	 * @param {HTMLElement} box
	 * @return {HTMLInputElement} Hidden input element inside media box.
	 */
	function getHiddenInput(box) {
		const input = box.querySelector('.rt-media-input');
		if (!input) {
			throw new Error('rt-media-input not found');
		}
		return input;
	}

	/**
	 * Parse a JSON array or single numeric string into array of unique integers.
	 * @param {string} raw
	 * @return {number[]} Unique attachment IDs.
	 */
	function parseIds(raw) {
		if (!raw) {
			return [];
		}
		let parsed = [];
		try {
			const val = JSON.parse(raw);
			parsed = Array.isArray(val) ? val : [val];
		} catch (e) {
			if (/^\d+$/.test(raw)) {
				parsed = [parseInt(raw, 10)];
			}
		}
		return Array.from(
			new Set(
				parsed
					.map((n) => parseInt(n, 10))
					.filter((n) => Number.isInteger(n) && n > 0)
			)
		);
	}

	/**
	 * Render a single attachment preview element.
	 * @param {Object} attachment
	 * @param {number} attachment.id
	 * @param {string} attachment.filename
	 * @param {string} [attachment.url]
	 * @param {string} [attachment.alt]
	 * @param {string} [attachment.mime]
	 * @return {HTMLElement} Wrapper element for preview + controls.
	 */
	function renderItem(attachment) {
		const wrapper = document.createElement('div');
		wrapper.className = 'rt-media-item';
		wrapper.dataset.id = String(attachment.id);

		let previewEl = null;
		if (
			attachment.mime &&
			attachment.mime.startsWith('image/') &&
			attachment.url
		) {
			previewEl = document.createElement('img');
			previewEl.src = attachment.url;
			previewEl.alt = attachment.alt || attachment.filename;
			previewEl.style.maxWidth = '80px';
			previewEl.style.maxHeight = '80px';
			previewEl.style.objectFit = 'cover';
			previewEl.style.marginRight = '10px';
			previewEl.style.borderRadius = '4px';
		} else if (
			attachment.mime &&
			attachment.mime.startsWith('video/') &&
			attachment.url
		) {
			previewEl = document.createElement('video');
			previewEl.src = attachment.url;
			previewEl.muted = true;
			previewEl.preload = 'metadata';
			previewEl.style.maxWidth = '120px';
			previewEl.style.maxHeight = '80px';
			previewEl.style.marginRight = '10px';
			previewEl.style.borderRadius = '4px';
		}

		const nameEl = document.createElement('span');
		nameEl.className = 'rt-media-filename';
		nameEl.textContent = attachment.filename || 'media';

		const removeBtn = document.createElement('button');
		removeBtn.type = 'button';
		removeBtn.className = 'rt-media-remove';
		const removeTemplate = i18n.removeLabel || 'Remove %s';
		const fileLabel = attachment.filename || 'media';
		removeBtn.setAttribute(
			'aria-label',
			removeTemplate.replace('%s', fileLabel)
		);
		removeBtn.textContent = '×';

		if (previewEl) {
			wrapper.appendChild(previewEl);
		}
		wrapper.appendChild(nameEl);
		wrapper.appendChild(removeBtn);
		return wrapper;
	}

	/**
	 * Fetch attachment data for IDs.
	 * @param {number[]} ids
	 * @return {Promise<Object[]>} Attachment objects with id, filename, url, alt, mime.
	 */
	function fetchAttachments(ids) {
		const { media } = window.wp || {};
		if (!media || !media.attachment) {
			return Promise.resolve([]);
		}

		const fetches = ids.map((id) => media.attachment(id).fetch());
		return Promise.allSettled(fetches).then((results) =>
			results
				.filter((r) => r.status === 'fulfilled' && r.value)
				.map((r) => ({
					id: r.value.id,
					filename: r.value.filename,
					url: r.value.url,
					alt: r.value.alt,
					mime: r.value.mime,
				}))
		);
	}

	/**
	 * Re-render list from IDs.
	 * @param {HTMLElement} list
	 * @param {number[]}    ids
	 * @return {Promise<void>} Resolves when list is rendered.
	 */
	async function renderList(list, ids) {
		list.innerHTML = '';
		if (!ids.length) {
			return;
		}
		const attachments = await fetchAttachments(ids);
		attachments.forEach((att) => {
			list.appendChild(renderItem(att));
		});
	}

	/**
	 * Initialize a single media box.
	 * @param {HTMLElement} box
	 */
	function initBox(box) {
		const button = box.querySelector('.rt-media-add');
		const list = box.querySelector('.rt-media-list');

		if (!button || !list) {
			return;
		}

		const input = getHiddenInput(box);
		const multiple = String(box.dataset.multiple) === '1';
		const type = box.dataset.type || 'image';

		let ids = parseIds(input.value);
		let frame = null;

		renderList(list, ids);

		function updateState(newIds) {
			ids = multiple ? Array.from(new Set(newIds)) : newIds.slice(-1);
			input.value = JSON.stringify(ids);
			renderList(list, ids);
		}

		button.addEventListener('click', (event) => {
			event.preventDefault();

			if (!frame) {
				frame = wp.media({
					title: i18n.selectMedia || 'Select media',
					button: { text: i18n.useMedia || 'Use selected media' },
					library: { type },
					multiple,
				});

				frame.on('select', () => {
					const selection = frame.state().get('selection');
					const chosen = selection
						.map((attachment) => attachment.toJSON())
						.map((data) => ({
							id: data.id,
							mime: data.mime,
							filename: data.filename,
							url: data.url,
						}));

					const newIds = [...ids];
					chosen.forEach((item) => {
						if (!newIds.includes(item.id)) {
							newIds.push(item.id);
						}
					});

					updateState(newIds);
				});
			}

			frame.open();
		});

		list.addEventListener('click', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) {
				return;
			}
			if (!target.classList.contains('rt-media-remove')) {
				return;
			}
			const item = target.closest('.rt-media-item');
			if (!item) {
				return;
			}
			const id = parseInt(item.dataset.id || '0', 10);
			if (!id) {
				return;
			}
			updateState(ids.filter((existing) => existing !== id));
		});
	}

	document.addEventListener('DOMContentLoaded', () => {
		document
			.querySelectorAll('.rt-media-box')
			.forEach((box) => initBox(box));
	});
})();
