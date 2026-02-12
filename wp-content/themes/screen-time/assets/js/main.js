(() => {
	const menuToggle = document.querySelector('[data-mobile-menu-toggle]');
	const menuClose = document.querySelector('[data-mobile-menu-close]');
	const menuPanel = document.getElementById('mobile-menu-panel');

	if (!menuToggle || !menuPanel) {
		return;
	}

	const setToggleVisual = (isOpen) => {
		menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		menuToggle.textContent = isOpen ? '✕' : '☰';
		menuToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
	};

	const openMenu = () => {
		menuPanel.hidden = false;
		document.body.style.overflow = 'hidden';
		setToggleVisual(true);
	};

	const closeMenu = () => {
		menuPanel.hidden = true;
		document.body.style.overflow = '';
		setToggleVisual(false);
	};

	menuToggle.addEventListener('click', () => {
		if (menuPanel.hidden) {
			openMenu();
			return;
		}

		closeMenu();
	});

	if (menuClose) {
		menuClose.addEventListener('click', closeMenu);
	}

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && !menuPanel.hidden) {
			closeMenu();
		}
	});

	closeMenu();
})();
