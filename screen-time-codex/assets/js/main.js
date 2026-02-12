(() => {
	const menuToggle = document.querySelector('[data-mobile-menu-toggle]');
	const menuClose = document.querySelector('[data-mobile-menu-close]');
	const menuPanel = document.getElementById('mobile-menu-panel');

	if (!menuToggle || !menuPanel) {
		return;
	}

	const setState = (isOpen) => {
		menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		menuToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
		menuPanel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
	};

	const openMenu = () => {
		menuPanel.hidden = false;
		requestAnimationFrame(() => {
			menuPanel.classList.add('is-open');
		});
		document.body.style.overflow = 'hidden';
		setState(true);
	};

	const closeMenu = () => {
		menuPanel.classList.remove('is-open');
		setTimeout(() => {
			if (!menuPanel.classList.contains('is-open')) {
				menuPanel.hidden = true;
			}
		}, 220);
		document.body.style.overflow = '';
		setState(false);
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

	menuPanel.addEventListener('click', (event) => {
		if (event.target === menuPanel) {
			closeMenu();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && !menuPanel.hidden) {
			closeMenu();
		}
	});

	window.addEventListener('resize', () => {
		if (window.innerWidth >= 768 && !menuPanel.hidden) {
			closeMenu();
		}
	});

	closeMenu();
})();
