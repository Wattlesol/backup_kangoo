# Repository Guidelines

## Project Structure & Module Organization
This is a Laravel application with Blade views and Laravel Mix frontend assets.
Core PHP code lives in `app/`, with controllers in `app/Http/Controllers`, models in `app/Models`, services in `app/Services`, and console commands in `app/Console/Commands`. Routes are split across `routes/web.php`, `routes/api.php`, and related route files. Database migrations, factories, seeders, and dumps are under `database/`. Frontend source assets live in `resources/js`, `resources/scss`, `resources/sass`, and `resources/views`; compiled assets are published under `public/`. Deployment support is in `Dockerfile`, `docker/`, `docker-compose.yml`, `deploy/`, and `scripts/`.

## Build, Test, and Development Commands
Run backend dependencies with:

```bash
composer install
```

Run frontend dependencies and builds with:

```bash
npm install
npm run dev
npm run watch
npm run prod
```

Use Laravel commands for local app work:

```bash
php artisan migrate
php artisan db:seed
php artisan optimize:clear
```

Sanad-specific operational scripts include `scripts/sanad_local_server.sh`, `scripts/sanad_pull_live_db_to_local.sh`, and `scripts/sanad_run_live_migrations.sh`.

## Coding Style & Naming Conventions
Follow existing Laravel conventions: PSR-style PHP, 4-space indentation, StudlyCase classes, camelCase methods, and snake_case database columns. Keep controllers thin when possible and place Sanad workflow logic in `app/Services` or `app/Support`. Blade files should stay in the existing feature folders under `resources/views`. JavaScript and Vue components should follow the local patterns in `resources/js/components` and `resources/js/sections`. Use `.prettierrc.json` and `.eslintrc.cjs` for frontend formatting/lint expectations.

## Testing Guidelines
`phpunit.xml` is present, but this checkout may not include a populated `tests/` directory. For PHP changes, run syntax checks with:

```bash
php -l path/to/file.php
```

For database or Sanad flows, prefer the focused QA scripts in `scripts/`, such as `sanad_migration_qa.sh`, `sanad_integrated_qa.sh`, and `sanad_web_role_uat.sh`. Always verify migrations are idempotent with `php artisan migrate --force` in a safe environment before production.

## Commit & Pull Request Guidelines
Recent commit history uses short imperative messages, for example `Fix Crawl4AI internal service fallback` and `Enhance Sanad AI workflow controls`. Keep commits focused and avoid including `.env`, dumps, or local OS files. Pull requests should describe the user-facing change, list migrations or seeders, mention deployment implications, and include screenshots for UI changes.

## Security & Configuration Tips
Never commit secrets from `.env` or Dokploy. Use `.env.dokploy.example` as a shape reference only. Before live migrations, use `scripts/sanad_run_live_migrations.sh`; it expects explicit `LIVE_DB_*` variables and creates a database backup first.
