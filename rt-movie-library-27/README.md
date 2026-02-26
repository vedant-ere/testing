# RT Movie Library - Custom REST API Endpoints (Issue #21)

## Overview
This plugin implements custom WordPress REST API endpoints for both CPTs:
- `rt-movie`
- `rt-person`

The implementation uses a **controller architecture based on `WP_REST_Controller`**:
- base class for shared behavior
- concrete classes for Movie and Person
- helper class for sanitization/validation utilities

## Architecture

### REST classes
- `includes/classes/rest/class-cpt-endpoints.php`
  - Hooks into `rest_api_init` and registers both resource controllers.
- `includes/classes/rest/class-base-cpt-controller.php`
  - Base controller (extends `WP_REST_Controller`) that contains shared CRUD route registration, permissions, request validation args, schema, and callbacks.
- `includes/classes/rest/class-movie-controller.php`
  - Extends base class with Movie-specific meta/taxonomy fields and field schema.
- `includes/classes/rest/class-person-controller.php`
  - Extends base class with Person-specific meta/taxonomy fields and field schema.
- `includes/classes/rest/class-cpt-helper.php`
  - Shared helper methods for validation/sanitization and response conversion helpers.

### Bootstrap integration
- `includes/classes/class-plugin.php`
  - Loads REST endpoints through `Cpt_Endpoints::get_instance()`.
  - Applies performance guard to skip non-essential heavy operations during REST and AJAX requests.

## API Namespace
- Namespace: `rt-movie-library/v1`

Base URL:
- `https://<site>/wp-json/rt-movie-library/v1`

## Endpoints

### Movies (`rt-movie`)
- `GET /movies` - Get all movies (supports pagination/search/status).
- `GET /movies/{id}` - Get movie by ID.
- `GET /movies/slug/{slug}` - Get movie by slug.
- `POST /movies` - Create movie.
- `PUT|PATCH /movies/{id}` - Update movie (**PATCH-style behavior supported**).
- `DELETE /movies/{id}` - Delete movie (moves to trash by default; permanent when `force=true`).

### Persons (`rt-person`)
- `GET /persons` - Get all persons.
- `GET /persons/{id}` - Get person by ID.
- `GET /persons/slug/{slug}` - Get person by slug.
- `POST /persons` - Create person.
- `PUT|PATCH /persons/{id}` - Update person (**PATCH-style behavior supported**).
- `DELETE /persons/{id}` - Delete person (trash by default).

## Authentication and Authorization

### Authentication
- **Write operations** (`POST`, `PUT`, `PATCH`, `DELETE`) require **Application Password authentication**.
- Checked via:
  - authenticated user session
  - `did_action( 'application_password_did_authenticate' )`

### Authorization
- Default editor/admin-level write restriction:
  - requires capability: `edit_others_posts`
- Per-item permission checks:
  - update requires `edit_post`
  - delete requires `delete_post`

## Request Fields

### Shared core fields (create/update)
- `title`
- `content`
- `excerpt`
- `status` (`draft|publish|pending|private`)
- `author` (optional user ID, editor/admin only)
- `featured_media` (attachment ID)
- `meta` (object of CPT meta keys/aliases)
- `taxonomies` (object with taxonomy slugs as keys)

### Movie-specific meta (top-level aliases or `meta` object keys)
- `rating` / `rt-movie-basic-rating` / `rt-movie-meta-basic-rating` (1.0-10.0)
- `runtime` / `rt-movie-basic-runtime` / `rt-movie-meta-basic-runtime` (1-300)
- `release_date` / `rt-movie-meta-basic-release-date` (`Y-m-d`)
- `content_rating` / `rt-movie-meta-basic-content-rating` (`U|U/A|PG|PG-13|R|NC-17`)
- `image_gallery` / `image_urls` / `rt-media-meta-img` (array of image URLs or attachment IDs)

### Movie taxonomy fields
- Preferred nested format:
  - `taxonomies.rt-movie-genre`
  - `taxonomies.rt-movie-label`
  - `taxonomies.rt-movie-language`
  - `taxonomies.rt-movie-production-company`
  - `taxonomies.rt-movie-tag`
- Also supported top-level aliases:
  - `genres`, `labels`, `languages`, `production_companies`, `tags`

### Movie crew field
- `crew` (array)
- Each item:
  - `role`: `director|producer|writer|actor|star`
  - `person`: person reference by ID, slug, or name
  - `character`: optional (only for actor/star)

### Person-specific meta (top-level aliases or `meta` object keys)
- `full_name` / `rt-person-meta-full-name`
- `birth_date` / `rt-person-meta-basic-birth-date` (`Y-m-d`)
- `birth_place` / `rt-person-meta-basic-birth-place`
- `twitter` / `rt-person-meta-social-twitter` (must be `twitter.com` or `x.com`)
- `facebook` / `rt-person-meta-social-facebook` (must be `facebook.com`)
- `instagram` / `rt-person-meta-social-instagram` (must be `instagram.com`)
- `website` / `rt-person-meta-social-web` (valid URL)
- `image_gallery` / `image_urls` / `rt-media-meta-img` (array of image URLs or attachment IDs)

### Person taxonomy fields
- Preferred nested format: `taxonomies.rt-person-career`
- Also supported top-level alias: `careers`

## Response Shape
Each item response includes:
- `id`, `type`, `status`, `title`, `content`, `excerpt`, `slug`
- `author`, `featured_media`, `date_gmt`, `modified_gmt`, `link`
- `meta` object (stored meta keys)
- `taxonomies` object (taxonomy slug -> term IDs)
- `crew` array (movies only)
- `meta.rt-media-meta-img` returns image **URLs** resolved from stored attachment IDs

Collection responses include headers:
- `X-WP-Total`
- `X-WP-TotalPages`

## PATCH-style Behavior
Updates are effectively PATCH-style:
- If a field is **omitted**, it is **left unchanged**.
- If a mapped meta field is provided as empty (`""`/`null`), it is removed.
- If a taxonomy field is provided as `[]`, assigned terms are cleared.

## Validation and Sanitization

Implemented across route args and helpers:
- numeric ID validation
- slug validation
- strict `Y-m-d` date validation
- featured media validation (must be attachment)
- status list sanitization
- taxonomy reference resolution by ID/slug/name
- crew person reference resolution by ID/slug/name
- rating normalization to one decimal place

## Assignment Requirements Mapping

### Functional requirements
- GET all CPT posts: ✅ (`GET /movies`, `GET /persons`)
- GET CPT post by ID: ✅ (`GET /movies/{id}`, `GET /persons/{id}`)
- Add CPT post: ✅ (`POST /movies`, `POST /persons`)
- Update CPT post: ✅ (`PUT|PATCH /movies/{id}`, `PUT|PATCH /persons/{id}`)
- Remove CPT post: ✅ (`DELETE /movies/{id}`, `DELETE /persons/{id}`)
- For both Person and Movie CPT: ✅

### Non-functional requirements
- Write operations restricted to authenticated users: ✅
- Application Password only for write: ✅
- Proper status codes (`200/201/400/401/403/404`): ✅
- Custom namespace (not `wp/v2`): ✅ (`rt-movie-library/v1`)

### Development guidelines
- `register_rest_route()` inside `rest_api_init`: ✅
- permission callbacks used: ✅
- sanitization/validation applied: ✅
- no hardcoded credentials or IDs: ✅
- schema defined for endpoints: ✅ (`schema` callbacks)

### Additional requested constraints
- Yoda condition style: ✅ used where applicable in plugin patterns.
- i18n: ✅ all API messages/descriptions wrapped with translation functions.
- escaping: ✅ response data is structured JSON (not direct HTML output); output escaping is handled where HTML rendering occurs in plugin UI.
- nonce: ✅ not required for Application Password REST writes; nonce is used elsewhere where form submissions occur.

## Heavy Operations Guard
Implemented in plugin bootstrap (`includes/classes/class-plugin.php`):
- REST/AJAX requests skip non-essential heavy operations.
- CPT/taxonomy and REST route registration are kept active so API remains functional.

## Example Requests

### Create Movie
`POST /wp-json/rt-movie-library/v1/movies`
```json
{
  "title": "The Last Horizon",
  "content": "<p>An epic sci-fi thriller set on a distant colony where one crew must choose between survival and salvation.</p>",
  "status": "draft",
  "author": 3,
  "taxonomies": {
    "rt-movie-genre": ["thriller"],
    "rt-movie-language": ["english"]
  },
  "meta": {
    "rt-movie-meta-basic-rating": 8.4,
    "rt-movie-meta-basic-runtime": 124,
    "rt-movie-meta-basic-release-date": "2025-12-20",
    "rt-movie-meta-basic-content-rating": "PG-13",
    "rt-media-meta-img": [
      "http://rt-movie-plugin-assignment.local/wp-content/uploads/2026/02/poster-1.jpg",
      "http://rt-movie-plugin-assignment.local/wp-content/uploads/2026/02/poster-2.jpg"
    ]
  },
  "crew": [
    { "role": "writer", "person": "Chris Evans" },
    { "role": "actor", "person": 5, "character": "Captain Mara" },
    { "role": "actor", "person": "chris-hemsworth", "character": "Rogue Pilot" }
  ]
}
```

### Patch Movie
`PATCH /wp-json/rt-movie-library/v1/movies/123`
```json
{
  "meta": {
    "rt-movie-basic-rating": 8.9
  }
}
```

### Delete Movie (Trash)
`DELETE /wp-json/rt-movie-library/v1/movies/123`

### Delete Movie (Permanent)
`DELETE /wp-json/rt-movie-library/v1/movies/123?force=true`

## Reviewer Guide (Production Verification)

Use this section on the production site to verify all required assignment behaviors.

### 1. Browser checks (no auth needed)
Open these in browser:

1. `https://<production-domain>/wp-json/`
2. `https://<production-domain>/wp-json/rt-movie-library/v1/movies`
3. `https://<production-domain>/wp-json/rt-movie-library/v1/persons`

Expected:
- JSON response
- `movies` and `persons` list endpoints return `200` with arrays

### 2. cURL setup (for authenticated write checks)
Set these shell vars first:

```bash
BASE_URL=\"https://<production-domain>/wp-json/rt-movie-library/v1\"
WP_USER=\"<username>\"
APP_PASS=\"<application-password>\"
```

### 3. Create movie (POST)

```bash
curl -i -u \"$WP_USER:$APP_PASS\" \\\n+  -H \"Content-Type: application/json\" \\\n+  -X POST \"$BASE_URL/movies\" \\\n+  -d '{\n+    \"title\": \"Reviewer Movie\",\n+    \"content\": \"<p>Reviewer content</p>\",\n+    \"status\": \"draft\",\n+    \"taxonomies\": {\n+      \"rt-movie-genre\": [\"thriller\"]\n+    },\n+    \"meta\": {\n+      \"rt-movie-meta-basic-rating\": 8.2,\n+      \"rt-movie-meta-basic-runtime\": 120,\n+      \"rt-movie-meta-basic-release-date\": \"2026-01-01\",\n+      \"rt-movie-meta-basic-content-rating\": \"PG-13\"\n+    },\n+    \"crew\": [\n+      { \"role\": \"writer\", \"person\": \"chris-evans\" }\n+    ]\n+  }'\n+```

Expected:
- `201 Created`
- Response body includes `id` and `slug`

### 4. Get movie by ID and slug (GET)

```bash
curl -i \"$BASE_URL/movies/<MOVIE_ID>\"\n+curl -i \"$BASE_URL/movies/slug/<MOVIE_SLUG>\"\n+```

Expected:
- `200 OK`
- Response includes `meta`, `taxonomies`, and `crew`

### 5. Patch movie rating via meta alias (PATCH)

```bash
curl -i -u \"$WP_USER:$APP_PASS\" \\\n+  -H \"Content-Type: application/json\" \\\n+  -X PATCH \"$BASE_URL/movies/<MOVIE_ID>\" \\\n+  -d '{\n+    \"meta\": {\n+      \"rt-movie-basic-rating\": 8.9\n+    }\n+  }'\n+```

Expected:
- `200 OK`
- Rating updated in response `meta`

### 6. Create person and validate social link rules

```bash
curl -i -u \"$WP_USER:$APP_PASS\" \\\n+  -H \"Content-Type: application/json\" \\\n+  -X POST \"$BASE_URL/persons\" \\\n+  -d '{\n+    \"title\": \"Reviewer Person\",\n+    \"status\": \"publish\",\n+    \"meta\": {\n+      \"rt-person-meta-full-name\": \"Reviewer Person\",\n+      \"rt-person-meta-social-twitter\": \"https://x.com/reviewer\"\n+    },\n+    \"taxonomies\": {\n+      \"rt-person-career\": [\"actor\"]\n+    }\n+  }'\n+```

Negative test (invalid domain for twitter):

```bash
curl -i -u \"$WP_USER:$APP_PASS\" \\\n+  -H \"Content-Type: application/json\" \\\n+  -X POST \"$BASE_URL/persons\" \\\n+  -d '{\n+    \"title\": \"Invalid Social\",\n+    \"twitter\": \"https://facebook.com/not-allowed\"\n+  }'\n+```

Expected:
- Valid request: `201`
- Invalid social URL: `400`

### 7. Delete behavior (trash by default)

```bash
curl -i -u \"$WP_USER:$APP_PASS\" -X DELETE \"$BASE_URL/movies/<MOVIE_ID>\"\n+curl -i -u \"$WP_USER:$APP_PASS\" -X DELETE \"$BASE_URL/movies/<MOVIE_ID>?force=true\"\n+```

Expected:
- First delete uses trash (`200`, `deleted: true`)
- Force delete permanently removes if available

### 8. Auth/permission verification

Without auth (write should fail):

```bash
curl -i -H \"Content-Type: application/json\" -X POST \"$BASE_URL/movies\" -d '{\"title\":\"No Auth\"}'\n+```

Expected:
- `401` (or `403` depending on auth state)

## cURL Test Pack

An executable end-to-end script is included:

- `scripts/curl-rest-e2e.sh`

Run:

```bash
BASE_URL=\"https://<production-domain>/wp-json/rt-movie-library/v1\" \\\n+WP_USER=\"<wp-username>\" \\\n+APP_PASS=\"<application-password>\" \\\n+bash scripts/curl-rest-e2e.sh
```

What it verifies:
- Discovery (movies/persons list)
- Movie create/read-by-id/read-by-slug/update/delete
- Person create/read-by-id/read-by-slug/delete
- One negative validation case (invalid social URL)

## Detailed Implementation Notes

### 1) Core architecture
- `Cpt_Endpoints` initializes endpoint registration on `rest_api_init`.
- `Base_Cpt_Controller` centralizes common CRUD and permission logic.
- `Movie_Controller` and `Person_Controller` define CPT-specific fields and custom logic.
- `Cpt_Helper` handles reusable validation/sanitization/reference resolution.

### 2) Write payload support
- Core fields: `title`, `content`, `excerpt`, `status`, optional `author`.
- Nested `meta` object:
  - accepts real stored meta keys and configured aliases.
- Nested `taxonomies` object:
  - taxonomy keys use actual taxonomy slugs.
  - term refs can be IDs, slugs, or names (names are created if missing).
- Movie-specific `crew`:
  - `role` + `person` + optional `character`.
  - person can be ID, slug, or name.

### 3) Movie crew persistence details
- Crew is persisted to plugin-native meta keys:
  - `rt-movie-meta-crew-director`
  - `rt-movie-meta-crew-producer`
  - `rt-movie-meta-crew-writer`
  - `rt-movie-meta-crew-actor`
  - `rt-movie-meta-crew-actor-characters`
- Internal shadow taxonomy `_rt-movie-person` is synchronized with resolved crew members.

### 4) Media image support details
- `rt-media-meta-img` accepts image URLs and/or attachment IDs.
- URLs are converted to attachment IDs via `attachment_url_to_postid`.
- Only valid image attachments are persisted.
- Response returns `meta.rt-media-meta-img` as resolved image URLs.

### 5) Auth and authorization model
- Write operations require:
  - authenticated user
  - successful Application Password auth
  - editor/admin capability (`edit_others_posts`)
- Per-record checks:
  - update: `edit_post`
  - delete: `delete_post`

### 6) Read behavior
- Public reads are allowed for published content.
- Draft/private content is restricted to users with edit permissions.
- ID and slug routes are both supported.

### 7) Status code behavior
- `200` for successful GET/PATCH/DELETE.
- `201` for successful POST.
- `400` for validation/format issues.
- `401` for unauthenticated or missing app-password-auth context.
- `403` for permission failures.
- `404` for missing resources.

## Best Practices Checklist (Assignment-Relevant)

### Implemented
- `register_rest_route()` used under `rest_api_init`.
- Custom namespace (`rt-movie-library/v1`) used.
- CRUD for both CPTs implemented.
- Dedicated permission callbacks implemented.
- Application Password auth enforced for writes.
- Input sanitization and validation implemented.
- Schema callbacks implemented for routes.
- JSON responses with appropriate status codes.
- PATCH-style updates (only provided fields are updated).
- Slug lookup support added.
- i18n strings used for API errors/messages.

### Intentionally not implemented (out of assignment scope)
- ETag / `If-Match` concurrency preconditions.
- Explicit `Cache-Control`/ETag response headers.
- 409 duplicate prevention rules (e.g., `title+year` uniqueness).
- Soft-delete custom columns (`isDeleted`, `deletedAt`) beyond WordPress trash.
- `X-Confirm-Delete` custom header requirement.
- DB-level custom indexes outside WP standard schema.

### Optional improvements if required later
1. Add response envelope metadata (`page`, `limit`, `hasNext`, `hasPrev`) in body in addition to headers.
2. Add explicit max payload size guard and `413` handling.
3. Add duplicate detection policy for movie title/year (if business rule is finalized).
4. Add ETag generation and conditional requests (`If-None-Match`, `If-Match`).
5. Add integration tests via WP test suite and automated CI checks.
