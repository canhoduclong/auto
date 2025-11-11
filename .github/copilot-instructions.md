## Quick orientation for AI coding agents

This repository is a Laravel application (Laravel 12, PHP 8.2). Use the files below to quickly understand structure, data flow, and conventions before making changes.

Keep guidance short, actionable, and concrete. Prefer editing existing files (follow PSR-12 / repo style) and reference the exact file paths shown below.

### Must-read files
- `composer.json` — PHP requirements, major dependencies (laravel/framework ^12, maatwebsite/excel, simple-qrcode), and composer scripts (notably `composer run dev` / `composer test`).
- `README.md` — general project description (standard Laravel skeleton).
- `routes/web.php` — primary application routes. Many resource controllers are registered here and protected by a `permission` middleware. Examples: `Route::resource('products', ProductController::class)->middleware('permission');` and AI route: `POST /ai/generate-description` -> `App\\Http\\Controllers\\AIController`.
- `phpunit.xml` — tests run with an in-memory SQLite DB (DB_CONNECTION=sqlite, DB_DATABASE=:memory:). Use `composer test` or `vendor/bin/phpunit`.
- `package.json` and `vite.config.js` — frontend uses Vite + Tailwind; `npm run dev` builds assets. The composer `dev` script uses `concurrently` to run `php artisan serve`, queue listener, pail and `npm run dev` together.

### Architecture & conventions (concrete)
- Standard Laravel MVC. Key folders: `app/Http/Controllers`, `app/Models`, `app/Services` (business logic), `app/Imports` & `app/Exports` (Excel import/export using maatwebsite), `resources/views`, `resources/js`.
- Routes use route-model binding and slug binding in many places: e.g. `Route::get('/product/{product:slug}', ...)` and `Route::get('/variant/{variant:slug}', ...)`.
- Authorization: many resource routes use `->middleware('permission')`. Search `->middleware('permission')` to find protected controllers.
- Background processing: queues are used; composer `dev` starts a queue listener. Tests run with `QUEUE_CONNECTION=sync` (see `phpunit.xml`).
- Factories & seeds: `database/factories` and `database/seeders` are present. There are seeder arrays at repo root (e.g. `products_seeder_array.php`, `categories_seeder_array.php`). A quick test route (`/test-variant`) creates a model via factory — useful example of factory usage.

### Typical developer workflows (commands)
- Install PHP deps: `composer install` (ensure PHP 8.2). After install, copy `.env.example` to `.env` if needed and run `php artisan key:generate`.
- Frontend: `npm install` then `npm run dev` (or `vite` directly). For an integrated dev run use the composer script `composer run dev` which starts server, queue listener, pail and Vite concurrently.
- Tests: `composer test` (runs `@php artisan test`) or `vendor/bin/phpunit`. Tests expect an in-memory sqlite DB (no DB file required).

### Project-specific patterns to follow
- Use resource controllers and named routes. When adding endpoints, register them in `routes/web.php` following the existing grouping and middleware patterns.
- For imports/exports use `Maatwebsite\\Excel` classes in `app/Imports` and `app/Exports` (examples present). Keep import error reporting consistent with existing `*WithErrorReport` classes.
- Media handling: `Media`, `MediaLink` models and `MediaController` manage file/gallery logic. There are popup endpoints for variant image selection (`/media/library/popup`).
- When touching pricing/inventory, look at `ProductVariant`, `Inventory*` classes and controllers under `app/Models` and `app/Http/Controllers` for existing validation and business rules.

### Integration points & external deps
- Excel import/export: `maatwebsite/excel` (see `app/Imports`, `app/Exports`, and controllers that call them).
- QR generation: `simplesoftwareio/simple-qrcode` is available.
- Background jobs / queue: configured via standard Laravel queue system — dev environment expects a listener (`php artisan queue:listen` in dev script).

### Tests and environment notes
- `phpunit.xml` configures environment variables for tests (in-memory SQLite, sync queue, array cache/session). When writing tests, don't depend on external DB or queue workers.
- Use factories in `database/factories` and sample seeder arrays for fixtures.

### How to edit safely (practical tips)
- Run `composer test` and `npm run dev` locally after changes.
- Search for `->middleware('permission')` and `Route::resource` to see existing patterns before adding new routes/controllers.
- When modifying Eloquent models, check related `*PriceLog`, `Inventory*`, and `Order*` models for existing event/observer patterns.

If any of these items are unclear or you'd like me to expand one of the examples (routes, a sample controller edit, or a test scaffold), tell me which part to expand and I'll iterate.
