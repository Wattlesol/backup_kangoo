# Antigravity Handoff: Finish Crawl4AI Local + Docker Compose

## Workspace

`/Users/xain/Documents/kangoo/backup_kangoo`

## Current Goal

Finish making Crawl4AI workable locally, then update `docker-compose.yml` so Crawl4AI deploys alongside the Laravel app and the app calls it internally by service name.

## Current State

- Knowledge-base UI and backend integration are already implemented.
- `config/sanad.php` has:
  - `CRAWL4AI_BASE_URL`
  - `CRAWL4AI_API_TOKEN`
  - `CRAWL4AI_TIMEOUT`
  - `CRAWL4AI_MAX_PAGES`
- `.env` has:
  - `CRAWL4AI_BASE_URL=http://127.0.0.1:11235`
  - `CRAWL4AI_API_TOKEN=`
  - `CRAWL4AI_TIMEOUT=60`
  - `CRAWL4AI_MAX_PAGES=50`
- New service:
  - `app/Services/SanadCrawlerIngestionService.php`
- `SanadWebController@storeAiKnowledge` supports:
  - `website_url`
  - `crawl_mode`
  - `crawl_page_limit`
  - audit action `sanad.ai.knowledge_scraped`
- `resources/views/sanad/ai-console.blade.php` was redesigned into the Sanad Knowledge Base workspace with AI test chat and Website Scraper tab.
- PHP syntax checks previously passed for the changed PHP/Blade files.
- Config/view cache were cleared earlier.

## Docker/Crawl4AI State

- Official image was pulled successfully:
  - `unclecode/crawl4ai:latest`
  - image id `c2c33ff7920d`
  - size `7.12GB`
- No Crawl4AI container is currently running.
- `docker-compose.yml` has not yet been updated with Crawl4AI.
- Existing Compose services: `chroma`, `app`, `mysql`.
- Compose app already uses `CHROMA_URL=http://chroma:8000` internally.
- Local browser page is currently:
  - `http://localhost:8000/sanad/knowledge-base`

## Next Steps

1. Start Crawl4AI locally on host port `11235`.
2. Inspect reachable endpoints:
   - `/health`
   - `/docs`
   - `/openapi.json`
   - actual crawl endpoint shape.
3. If Laravel endpoint assumptions are wrong, update `SanadCrawlerIngestionService` to match the actual Crawl4AI Docker API.
4. Update `docker-compose.yml`:
   - Add `crawl4ai` service using `unclecode/crawl4ai:latest`.
   - Expose stable host port: `${CRAWL4AI_PUBLIC_PORT:-11235}:11235`.
   - Set app env `CRAWL4AI_BASE_URL=http://crawl4ai:11235` for internal Docker calls.
   - Add app `depends_on` for `crawl4ai` if a practical healthcheck is available.
5. Update `.env`:
   - Add `CRAWL4AI_PUBLIC_PORT=11235`.
   - Keep `CRAWL4AI_BASE_URL=http://127.0.0.1:11235` for local non-Docker Laravel testing.
6. Test scraper from `/sanad/knowledge-base`:
   - Single URL scrape.
   - Same-domain crawl with page limit.
   - Confirm created `SanadAiKnowledgeItem` is active.
   - Confirm chunks are indexed.
   - Confirm metadata stores crawled URLs, source URL, crawl mode, and page count.
7. Run:

```bash
php -l app/Services/SanadCrawlerIngestionService.php
php -l app/Http/Controllers/SanadWebController.php
php -l resources/views/sanad/ai-console.blade.php
php artisan config:clear
php artisan view:clear
```

## Important Constraints

- Do not revert unrelated work.
- Keep Crawl4AI backend-only.
- Do not expose unsafe internal URLs to the scraper.
- Keep localhost/private/reserved URL blocking.
