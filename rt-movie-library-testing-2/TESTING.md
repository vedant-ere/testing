# Testing Guide 

---

## 1. Test Suite Overview

The test suite covers three critical domains of the plugin:

- **Dashboard Widgets**: Registration, CRUD rendering, rating-based sorting, and TMDB API error handling.
- **Gutenberg Blocks**: Registration and dynamic server-side rendering for Movies, Single Movie, and Person blocks.
- **Roles & Permissions**: Custom "Movie Manager" role lifecycle, capability inheritance, and deactivation cleanup.

### Current Status
- **Total Tests**: 18
- **Total Assertions**: 79
- **Status**: 100% Pass Rate

---

## 2. Environment Setup

Tests require a local WordPress environment with `phpunit` dependencies.

```bash
# 1. Install dependencies
composer install
npm install

# 2. Run the test suite
npm run test:local
```

> [!IMPORTANT]
> The suite uses a dedicated `wp-tests-config.php`. Ensure your local `WP_PHPUNIT__TESTS_CONFIG` environment variable is correctly set if running PHPUnit directly.

---

## 3. Stabilization Notes

Significant improvements were made to ensure tests remain deterministic:

- **Decoupled Rendering**: Dashboard widgets now render content even if an admin edit-link cannot be generated in the CLI environment.
- **Dynamic Cap Mapping**: Used `map_meta_cap` filters to reliably simulate permission levels during unit tests.
- **Hook Lifecycle Management**: Implemented precise attachment/detachment of the `rt-movie` genre-validation hooks to prevent "Force to Draft" side effects during test data setup.

---

## 4. Execution Commands

### Run Full Suite
`npm run test:local`

### Targeted Runs (examples)
- **Widgets**: `npm run test:local -- --filter Test_Dashboard_Widgets`
- **Blocks**: `npm run test:local -- --filter Test_Blocks`

---

## 5. Troubleshooting

If movie-related tests fail, ensure the `assign_genre()` helper is used. The plugin enforces valid genres for "Published" visibility; movies without a genre will be force-drafted and will not appear in queries.

---


