# Screen Time Theme

Canonical developer README for the `screen-time` theme.

## Theme Overview

`screen-time` is a custom WordPress theme focused on movie/person presentation.

Key characteristics:
- CPT-oriented templates (`rt-movie`, `rt-person`)
- Componentized CSS and conditional asset loading
- Customizer-driven display and layout controls

## Documentation Convention

1. Keep a single root file: `themes/screen-time/README.md`.
2. Add branch/issue notes under **Branch / Issue Notes**.
3. For each section, include goal, files in scope, behavior, and verification.

## Branch / Issue Notes

### Customizer Options (`feature/customizer-options`)

#### Goal
Implement and stabilize a structured Customizer configuration for display/layout controls with clear defaults, range enforcement, and predictable preview behavior.

#### Files In Scope
- `inc/customizer.php`
- `assets/js/customizer-preview.js`
- `assets/js/customizer-controls.js`
- `template-parts/post-navigation.php`
- `assets/css/components/post-navigation.css`

#### Architecture Summary
Customizer logic is centralized in `Screen_Time_Customizer` (`inc/customizer.php`) and is responsible for:
- panel/section/control registration
- default/range constants
- sanitization and validation callbacks
- preview/control script enqueue
- runtime inline CSS output

#### Registered Sections
Inside panel `screentime_options`:
1. `screentime_global`
2. `screentime_navigation`
3. `screentime_movie_details`
4. `screentime_single_post`

Additional section:
- `screentime_footer_options`

#### Settings and Transport
`postMessage`:
- `screentime_background_color`
- `screentime_sidebar_width`
- `screentime_movie_image_width`
- `screentime_movie_image_height`
- `screentime_person_image_width`
- `screentime_person_image_height`

`refresh`:
- `screentime_display_navigation`
- `screentime_time_format`
- `screentime_separator`

Transport rationale:
- Use `postMessage` for direct live-preview visual updates.
- Use `refresh` for settings that are template/runtime-rendered and safer via full preview refresh.

#### Active Callback Rules
- Navigation section: movie/person singular only (`active_on_singular_movie_or_person`)
- Movie details: movie singular only (`active_on_singular_movie`)
- Single post layout: movie/person singular only (`active_on_singular_movie_or_person`)

#### Defaults and Validation Contract
- `DEFAULTS`: single source of truth for setting fallbacks
- `RANGES`: min/max limits for numeric controls
- Numeric controls use both sanitize and validate callbacks
- Range descriptions are exposed in control UI for all numeric image/sidebar fields

#### Related Implementation Notes
- Post navigation template uses `Screen_Time_Customizer::DEFAULTS` for toggle fallback (no hardcoded default)
- Post navigation component uses theme color variables instead of hardcoded accent hex
- Placeholder translation strings include `translators:` comments where required

#### Verification Checklist
1. Customizer sections appear only in expected preview contexts.
2. `postMessage` settings update preview without reload.
3. `refresh` settings apply correctly after preview refresh.
4. Numeric constraints reject/clamp invalid values as expected.
5. Post navigation toggle behavior matches supported singular post types.
6. PHPCS i18n checks pass for placeholder strings.

## Maintenance Checklist (For New Branch Notes)

1. Add a new heading under **Branch / Issue Notes**.
2. List exact files touched.
3. Document transport, callbacks, and fallback behavior (if Customizer-related).
4. Add a concise verification checklist.
This file is the single source of truth for implementation notes.  
Going forward, branch/issue updates should be appended here instead of creating separate markdown files.

## Theme Purpose

`screen-time` is a custom WordPress theme focused on movie/person presentation with:
- CPT-driven single pages (`rt-movie`, `rt-person`)
- archive and taxonomy views
- componentized CSS and conditional asset loading

## Documentation Policy

1. Keep one root README in `themes/screen-time/README.md`.
2. Add changes incrementally under **Branch / Issue Notes**.
3. Prefer concise implementation notes: scope, files touched, behavior, validation checklist.

## Branch / Issue Notes

### Sidebar Widgets (Current Branch Scope)

#### Goal
Render contextual recommendation widgets:
- Movie recommendations on `rt-movie` singles
- Person recommendations on `rt-person` singles

#### Scope Boundaries
Included:
- Widget rendering in single templates
- Widget-specific CSS and responsive behavior
- Conditional asset enqueueing
- UX consistency with existing theme design tokens

Out of scope:
- Plugin/business logic changes outside widget display needs
- New REST endpoints or custom admin UIs unrelated to sidebar widgets

#### Files In Scope
- `single-rt-movie.php`
- `single-rt-person.php`
- `assets/css/components/widget-recommendations.css`
- `inc/enqueue-assets.php`

#### Implementation Notes
- Widget stylesheet is conditionally enqueued for:
  - `is_singular( 'rt-movie' ) || is_singular( 'rt-person' )`
- Widget styles are scoped to `.widget-recommendations` to avoid style bleed.
- Interactive colors use theme CSS variables (no hardcoded accent hex values).
- Font sizes in widget stylesheet use `rem` for scalability.

#### Person Widget Notes
- Overlay link spans the full card for consistent click target behavior.
- Text is clamped where needed to preserve card height and layout stability.
- Responsive breakpoints preserve readability across desktop/tablet/mobile.

#### Validation Checklist
1. Widget CSS loads only on movie/person single pages.
2. Widget blocks render correctly with and without recommendation data.
3. No hardcoded interactive accent hex values remain in widget stylesheet.
4. Font sizing in widget stylesheet remains `rem`-based.
5. Responsive behavior is stable across desktop/tablet/mobile.
6. No duplicated enqueue block exists for widget stylesheet.

## Quick Maintenance Checklist

When adding a new branch/issue section:
1. Add a new heading under **Branch / Issue Notes**.
2. List exact files touched.
3. Summarize behavior changes and constraints.
4. Add a short verification checklist.
