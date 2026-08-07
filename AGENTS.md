# AGENTS.md

Laravel 12 REST API backend for **Rehlati**, a bilingual (Arabic/English) Syrian travel app: hotels, rooms, cities, regions, car agencies, ratings/reviews. API-only — the sole web page is the default welcome view. No CI, no app frontend beyond Vite scaffolding.

## Commands

- Tests: `php artisan test` (or `composer test`, which also clears config). phpunit.xml uses in-memory SQLite, so no DB setup needed. Only the default example tests exist.
- Code style: `vendor/bin/pint` (Laravel Pint).
- Dev loop: `composer dev` (serve + queue:listen + pail + vite concurrently).
- Seed demo data: `php artisan db:seed`. `AdminSeeder` creates `hamza@example.com` / `Password123` with the `admin` role.
- Regenerate the committed `api.json` (OpenAPI): `php artisan scramble:export`. Scramble only documents `api/admin/*` routes (configured in `AppServiceProvider`).
- Postman collection: `php artisan postman:generate` (writes to `storage/postman/`).

## API request requirements (critical)

Every `api/*` request must send these headers:

- `Our-Great-Password`: the raw API password. `CheckApiPasswordMiddleware` compares via `Hash::check` against `API_PASSWORD` (a **bcrypt hash** in `.env`). Changing it breaks all requests.
- `lang`: `ar` or `en` (defaults to `ar`). Sets the locale for every `__()` response; a user's choice is cached as `lang_for_user: {id}`.
- `device`: device identifier. On authenticated requests it must match the token's device — a mismatch deletes the device and forces re-login (401).

Despite "public" comments in `routes/api.php`, **all content routes (hotels, cities, regions, rooms, amenities, ratings) require `auth:sanctum` + `check_access_token_device` + `check_email_verified`** (confirmed via `php artisan route:list`). There is no anonymous data access; testing these requires a logged-in, email-verified user.

Admin routes (`api/admin/*`) are loaded via `require __DIR__.'/admin.php'` inside `routes/api.php` and additionally declare their own middleware group, so they run double-wrapped. They use session `web` + `auth:sanctum` + the custom `admin` middleware (user must have the spatie `admin` role).

Rate limits are per-action custom limiters (e.g. login 5/min, register 3/min) defined in `app/Http/RateLimiters/`, registered from `config/rate_limiters.php` by `RateLimitingServiceProvider`, and applied as `throttle:<name>`.

## Response envelope

- Success: `{"message": "...", "data": ...}` via `succeed()`/`failed()` from `app/Traits/JsonResponseTrait.php`.
- Errors: flat `{"message": "..."}` only. Custom renderers in `bootstrap/app.php`: 401/403/404/429/422 (422 returns only the first validation message), and **any unexpected exception returns HTTP 222** with a generic message.
- API resources are unwrapped (`JsonResource::withoutWrapping()`).

## Architecture conventions

- Controllers shared by user + admin APIs live in `app/Http/Controllers/Mutual/`; admin CRUD in `app/Http/Controllers/Admin/`; user-facing in `app/Http/Controllers/Api/`. Business logic in `app/Services/`, validation in FormRequests under `app/Http/Requests/`, responses in `app/Http/Resources/`.
- `Model::preventLazyLoading()` is enabled outside production — always eager-load relations.
- Ratings/descriptions/locations are polymorphic; a morph map (user, hotel, room, city, region, rating) is registered in `AppServiceProvider`.
- Bilingual models use `name_en` / `name_ar` columns; localized accessors pick the column by the user's cached language.
- Media (spatie medialibrary): uploads are re-encoded to WebP via `app/Services/ImageUploadService.php`; thumbnails are flagged with the custom property `is_thumbnail`; collections are named `<model>_pictures`.
- Price/season/exchange-rate logic in `app/Services/Price*` caches heavily with specific keys and exposes admin "clear-caches" endpoints — invalidate cache keys when touching these features.

## Gotchas

- `app/RehlatiFirebaseCredentials.json` (FCM) is gitignored but required for anything Firebase-related. Never commit it.
- `API_PASSWORD` in `.env.example` is a committed bcrypt hash; the `.env` value must match it or every request fails the `Our-Great-Password` check.
