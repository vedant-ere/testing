/**
 * Search Overlay Logic
 * Handles the logic for toggling the search overlay, properly managing aria states
 * and trapping focus within the overlay when it is open for accessibility (WCAG).
 */
document.addEventListener('DOMContentLoaded', () => {
    const searchTriggers = document.querySelectorAll('.header-search-trigger');
    const overlay = document.getElementById('header-search-overlay');
    
    if (!overlay || searchTriggers.length === 0) return;

    const closeBtn = overlay.querySelector('.search-overlay-close');
    const input = overlay.querySelector('.search-overlay-input');
    const focusableElements = overlay.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements[focusableElements.length - 1];

    let lastFocusedElement = null;

    const openOverlay = () => {
        lastFocusedElement = document.activeElement;
        
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        
        searchTriggers.forEach(t => t.setAttribute('aria-expanded', 'true'));
        
        // Wait for CSS transition before focusing input
        setTimeout(() => {
            if (input) input.focus();
        }, 50);
        
        document.body.style.overflow = 'hidden';
    };

    const closeOverlay = () => {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        
        searchTriggers.forEach(t => t.setAttribute('aria-expanded', 'false'));
        document.body.style.overflow = '';
        
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
    };

    // Event Listeners
    searchTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            openOverlay();
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeOverlay);
    }

    // Close on Escape or clicking outside
    overlay.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeOverlay();
            return;
        }

        // Focus trap
        if (e.key === 'Tab') {
            if (e.shiftKey) { // Shift + Tab
                if (document.activeElement === firstFocusable) {
                    lastFocusable.focus();
                    e.preventDefault();
                }
            } else { // Tab
                if (document.activeElement === lastFocusable) {
                    firstFocusable.focus();
                    e.preventDefault();
                }
            }
        }
    });

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closeOverlay();
        }
    });
});
