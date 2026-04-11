/**
 * Mobile Header & Drawer JS
 */
document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    const menuTriggers = document.querySelectorAll('.mobile-menu-trigger');
    const closeTriggers = document.querySelectorAll('.drawer-close-btn');
    const drawer = document.querySelector('.mobile-menu-drawer');

    if (!drawer) return;

    function openMenu() {
        body.classList.add('is-mobile-menu-open');
    }

    function closeMenu() {
        body.classList.remove('is-mobile-menu-open');
    }

    menuTriggers.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openMenu();
        });
    });

    closeTriggers.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            closeMenu();
        });
    });

    // Accordion toggle
    const accordions = document.querySelectorAll('.drawer-accordion');
    accordions.forEach(acc => {
        const header = acc.querySelector('.drawer-accordion-header');
        header.addEventListener('click', () => {
            acc.classList.toggle('is-open');
        });
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && body.classList.contains('is-mobile-menu-open')) {
            closeMenu();
        }
    });

    // Close on backdrop click
    drawer.addEventListener('click', (e) => {
        if (e.target === drawer) {
            closeMenu();
        }
    });
});
