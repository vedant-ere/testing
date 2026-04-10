# CSS Architecture — Screen Time FSE

## Overview

The CSS has been reorganized into **scoped, modular files** for better maintainability and clarity. Each component/section is now in its own file.

## File Structure

```
assets/css/
├── global.css              ← Design tokens, resets, utilities
├── index.css               ← Master import file (imports all components)
├── components/
│   ├── header.css          ← Header, logo, nav, search, mobile menu
│   ├── hero.css            ← Homepage hero section
│   ├── movie-card.css      ← Movie cards, grids, archive pagination
│   ├── single-movie.css    ← Single movie page (all sections)
│   └── footer.css          ← Footer, social, copyright
└── editor.css              ← Editor iFrame styles (unchanged)
```

## Component Breakdown

### `global.css` (165 lines)
- CSS custom properties for colors/spacing (from theme.json)
- Box-model reset
- Utility classes (`.container`, `.sr-only`, `.desktop-only`, etc.)
- Responsive visibility helpers
- Section title component

### `components/header.css` (350 lines)
**Covers:**
- `.site-header` — Header wrapper
- `.site-logo`, `.wp-block-site-logo` — Logo (image + text fallback)
- `.header-icon-button`, `.header-action-link` — Search, Sign In buttons
- `.site-search-form`, `.site-search-panel` — Search dropdown
- `.site-nav` — Desktop navigation bar with menu items
- `.header-language-wrap`, `.header-language-menu` — Language dropdown
- `.header-menu-toggle`, `.mobile-menu` — Mobile menu panel
- All responsive breakpoints (768px, 1024px)

### `components/hero.css` (95 lines)
**Covers:**
- `.hero-section` — Homepage featured section
- `.hero-section__overlay` — Dark gradient overlay
- `.hero-section__title`, `.hero-section__description` — Hero text
- `.hero-section__meta`, `.hero-section__tags` — Movie metadata
- Mobile-specific hiding (description hidden on mobile)

### `components/movie-card.css` (230 lines)
**Covers:**
- `.movie-grid` — 3-column grid (2 cols on tablet, 2 on mobile)
- `.movie-card` — Individual movie card component
- `.movie-card__poster`, `.movie-card__content` — Card sections
- `.movie-card__title`, `.movie-card__runtime` — Card metadata
- `.archive-heading`, `.wp-block-query-title` — Archive page title
- `.archive-pagination`, `.wp-block-query-pagination` — Pagination buttons
- Responsive grid changes at breakpoints

### `components/single-movie.css` (420 lines)
**Covers:**
- `.movie-single-hero__inner` — Hero section layout
- `.movie-single-hero__poster` — Movie poster image
- `.movie-single-hero__title`, meta items (rating, runtime, etc.)
- `.movie-single-hero__genres` — Genre pills
- `.movie-single-body__copy` — Synopsis text
- `.quick-links-widget` — Sidebar links (sticky on desktop)
- `.movie-cast-grid`, `.movie-cast-card` — Cast/crew cards (4 col → 2 col on mobile)
- `.movie-snapshot-grid` — Gallery images (3 col → 1 col on mobile)
- `.movie-trailer-grid` — Embedded trailers (3 col → 1 col on mobile)
- `.movie-review-grid`, `.movie-review-card` — Comments section (2 col → 1 col on mobile)
- `.movie-review-form` — Comment textarea and submit button
- All responsive behavior at 1023px, 767px breakpoints

### `components/footer.css` (200 lines)
**Covers:**
- `.site-footer__columns` — 3-column layout
- `.site-footer__brand-col` — Logo + social icons
- `.social-list` — Social icon links
- `.site-footer__menu` — Navigation links
- `.site-footer__divider` — Separator line
- `.site-footer__copyright`, `.site-footer__bottom-links` — Bottom row
- Mobile stacking and centering (< 767px)

## Import Order

`index.css` imports all components in this order:
1. `components/header.css`
2. `components/hero.css`
3. `components/movie-card.css`
4. `components/single-movie.css`
5. `components/footer.css`

This order ensures that shared utilities are available to all components, and component styles are loaded in logical page flow order.

## Why This Structure?

✅ **Modularity** — Each component is self-contained; easy to locate and modify styles
✅ **Maintainability** — Scoped files are smaller, easier to reason about
✅ **Organization** — Clear separation of concerns (header ≠ footer ≠ single-movie)
✅ **Scalability** — Adding new sections means creating a new component file
✅ **Performance** — Easier to minify and optimize individual components later
✅ **Responsiveness** — Each file contains its own media queries

## How to Edit

### Example: Update movie card styling
1. Open `assets/css/components/movie-card.css`
2. Find the `.movie-card` selector
3. Make changes
4. Responsive rules are directly below each component's base styles

### Example: Add a new homepage section
1. Create `assets/css/components/new-section.css` with all styles
2. Add `@import url('./components/new-section.css');` to `index.css`
3. Done! No need to touch other CSS files

## Old vs New

- **Old:** `assets/css/blocks.css` (1,228 lines)
- **New:** Split into 5 focused files + 1 index file
  - `header.css` — 350 lines
  - `hero.css` — 95 lines
  - `movie-card.css` — 230 lines
  - `single-movie.css` — 420 lines
  - `footer.css` — 200 lines

Same CSS, better organization! 🎬
