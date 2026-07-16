# Kangoo Dokploy Deployment

This repo is prepared for Dokploy using the root `Dockerfile`.

## Dokploy App

- Build type: Dockerfile
- Dockerfile path: `Dockerfile`
- Exposed port: `8080`
- Health/path check: `/`

## Required Environment

Copy values from `.env.dokploy.example` into Dokploy and replace the placeholders:

- `APP_KEY`
- `DB_PASSWORD`
- `MYSQL_ROOT_PASSWORD`

The production domain is already set to `https://kangoo.sa` for both `APP_URL` and `ASSET_URL`.

Use `php artisan key:generate --show` locally if you need a new `APP_KEY`.

## Database

This compose file runs MySQL in the same Dokploy stack as the app. The app connects internally with:

- `DB_HOST=mysql`
- `DB_PORT=3306`

For direct external database access, set:

- `MYSQL_PUBLIC_PORT=3306`

If port `3306` is already used on the server, choose another public port such as `3307`; the app still uses internal port `3306`.

## First Deploy Restore

The bundled restore dump is the correct Kangoo database section from `kangoo.sql`:

- source database: `tywgrrte_kangoo_sa`
- expected file path: `database/dumps/kangoo_sa.sql.gz`

The dump is temporarily committed so Dokploy can restore the database during deployment. Remove `database/dumps/kangoo_sa.sql.gz` from git after the first successful live restore.

For the first deploy, use:

- `RESTORE_DB_ON_BOOT=true`
- `RESTORE_DB_IF_EMPTY=true`
- `RESTORE_DB_FRESH=false`
- `RUN_MIGRATIONS=false`
- `RUN_SEEDERS=false`

With `RESTORE_DB_IF_EMPTY=true`, the app imports the dump only when the target database has zero tables.

After the first successful restore, set this in Dokploy and redeploy:

- `RESTORE_DB_ON_BOOT=false`

Only use `RESTORE_DB_FRESH=true` when you intentionally want to drop and recreate the database. It requires `MYSQL_ROOT_PASSWORD` and will remove existing live data.

## Migrations and Seeders

Set these only when you intentionally want the container to change the database schema/data on startup:

- `RUN_MIGRATIONS=true`
- `RUN_SEEDERS=true`

For this Kangoo restore, keep both flags `false`; the restore script seeds only the dynamic `theme_settings` defaults needed by the current app.

## Local Verification

```bash
docker compose up --build
```

Open `http://localhost:8080`.
