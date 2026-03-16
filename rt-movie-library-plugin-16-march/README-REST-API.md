# RT Movie Library - REST API Guide

## Overview
This plugin exposes custom REST endpoints under:
- `/wp-json/rt-movie-library/v1/movies`
- `/wp-json/rt-movie-library/v1/persons`

These endpoints are built for controlled CRUD operations, structured movie/person payloads, and assignment-specific logic (duplicate detection + merge behavior for movies).

## Implemented Endpoints

### Movies
1. `GET /rt-movie-library/v1/movies`
2. `POST /rt-movie-library/v1/movies`
3. `GET /rt-movie-library/v1/movies/{id}`
4. `PATCH /rt-movie-library/v1/movies/{id}`
5. `DELETE /rt-movie-library/v1/movies/{id}?force=false`
6. `GET /rt-movie-library/v1/movies/slug/{slug}`

### Persons
1. `GET /rt-movie-library/v1/persons`
2. `POST /rt-movie-library/v1/persons`
3. `GET /rt-movie-library/v1/persons/{id}`
4. `PATCH /rt-movie-library/v1/persons/{id}`
5. `DELETE /rt-movie-library/v1/persons/{id}?force=false`
6. `GET /rt-movie-library/v1/persons/slug/{slug}`

## Key Behaviors

### Movie create duplicate logic
`POST /movies` behaves as:
1. `409 Conflict` if exact duplicate (core signature matches).
2. `200 OK` merge if title matches but details differ.
3. `201 Created` for completely new movie.

### PATCH-style updates
- `PATCH` updates only provided fields.
- Omitted fields remain unchanged.

### Delete behavior
- Default behavior uses trash (`force=false`).

### Normalized movie response
Movie response is normalized to avoid repeated data:
- `crew` for crew relationships.
- `taxonomy_terms` for taxonomy names.
- media URL fields (`featured_image_url`, `gallery_image_urls`, `gallery_video_urls`, `carousel_url`).

## Authentication
Use WordPress Application Passwords (Basic Auth):
1. WordPress Admin -> Users -> Profile -> Application Passwords.
2. Generate one password for Postman.
3. Use username + application password in Postman.


## Example Movie Payload
```json
{
  "title": "Inception",
  "content": "<p>Plot</p>",
  "excerpt": "Sci-fi thriller",
  "status": "publish",
  "meta": {
    "rt-movie-meta-basic-rating": 8.8,
    "rt-movie-meta-basic-runtime": 148,
    "rt-movie-meta-basic-release-date": "2010-07-16",
    "rt-movie-meta-basic-content-rating": "PG-13"
  },
  "taxonomies": {
    "rt-movie-genre": ["Science Fiction", "Thriller"]
  },
  "crew": [
    { "role": "director", "person": "christopher-nolan" }
  ]
}
```

## Edge Cases Covered
1. Invalid crew role -> `400`.
2. Duplicate movie -> `409`.
3. Same title, changed details -> merge `200`.
4. Trash behavior verified with follow-up read.

## Reviewer Notes
For production/demo verification:
1. Run robust collection once and capture screenshots of each folder run.
2. Re-run same collection to show repeatability.
3. Show duplicate and merge responses explicitly.
4. Show cleanup step completion.
