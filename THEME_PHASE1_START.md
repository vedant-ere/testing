# Screen Time Theme — Phase 1 Start Guide

This guide documents the initial file setup and first implementation pass for a production-grade static WordPress theme (Phase 1) aligned to the plugin architecture in this repository.

## 1) Create the theme skeleton

Create this directory:

- `wp-content/themes/screen-time/`

Required top-level files:

- `style.css`
- `functions.php`
- `index.php`
- `header.php`
- `footer.php`
- `front-page.php`
- `single-rt-movie.php`
- `archive-rt-movie.php`
- `single-rt-person.php`
- `archive-rt-person.php`

Component directories:

- `template-parts/`
  - `movie-card.php`
  - `person-card.php`
  - `slider.php`
  - `navigation.php`
- `assets/css/`
  - `main.css`
  - `components.css`
  - `layouts.css`
  - `responsive.css`
- `assets/js/`
  - `main.js`
  - `slider.js`
- `assets/images/`
- `inc/`
  - `theme-setup.php`
  - `enqueue-assets.php`
  - `template-functions.php`

## 2) Build the two mandatory bootstrap files first

### `style.css`

Add a valid WordPress theme header only (metadata block), then keep CSS in assets files.

### `functions.php`

Keep this thin and only:

- define constants (`SCREENTIME_VERSION`, path, URI)
- include files from `inc/`
- prevent direct access with `defined('ABSPATH') || exit;`

## 3) Enforce Phase 1 rule: static rendering only

In Phase 1 templates:

- Do **not** use `the_post()`, `have_posts()`, `the_title()`, `get_post_meta()`, etc.
- Do use static text and static image references via `get_template_directory_uri()`.

## 4) Add design tokens before components

In `assets/css/main.css`, add `:root` variables for:

- color palette
- typography scale (`rem`)
- spacing scale
- breakpoints (`640`, `768`, `1024`, `1280`, `1536`)

Then add reset + base styles + `.container` utility.

## 5) Enqueue assets in deterministic order

In `inc/enqueue-assets.php`:

1. `main.css`
2. `components.css`
3. `layouts.css`
4. `responsive.css`

Load scripts in footer and conditionally load slider script only on front page.

## 6) Build reusable template parts before page templates

Create components first:

- `template-parts/navigation.php`
- `template-parts/slider.php`
- `template-parts/movie-card.php`
- `template-parts/person-card.php`

Then assemble in:

- `front-page.php`
- archive/single templates

## 7) Accessibility and review gates from day one

- semantic landmarks (`header`, `main`, `section`, `article`, `footer`)
- alt text on all images
- `aria-label` for icon-only controls
- visible keyboard focus states

## 8) First-pass validation checklist

- no PHP warnings
- no inline CSS/JS
- no hardcoded theme URLs
- no plugin-domain logic in theme
- no dynamic WP loop APIs in Phase 1

## 9) Suggested implementation order

1. `style.css`
2. `functions.php`
3. `inc/theme-setup.php`
4. `inc/enqueue-assets.php`
5. `assets/css/main.css`
6. `header.php` + `footer.php`
7. `template-parts/*`
8. page templates
9. responsive tuning + visual QA

## 10) Done definition for this start phase

You are ready to move to the next step when:

- all initial files exist
- homepage renders static components
- CSS token system is in place
- slider shell exists (even before final animation polish)
- responsive breakpoints are wired

