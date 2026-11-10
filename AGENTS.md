# AGENTS.md — working in this repo

Read this before changing anything. The user of this repo is a
developer deploying a minimal Laravel starter with the Laravel AI
SDK on Railway; keep it runnable, minimal, and honest.

## Stack (pinned — do not bump majors casually)

PHP **8.5.12** (exact, via `php:8.5.12-fpm-alpine` in the Dockerfile)
+ Laravel **13.40.0** + Laravel AI SDK **1.3.0** + Postgres 18
(Railway service; SQLite only for local parity testing) + nginx
(Alpine). Versions verified 2026-11-10 — Packagist for
Laravel/AI SDK, endoflife.date for PHP; see README "Stack &
versions". No Sanctum, no auth scaffolding, no billing, no
frontend build: this is an API-only starter.

## The one rule that matters

**Never fake a capability.** `/api/health` reports the REAL
database status — a 200 with `db: "error"` is a failure, not a
pass. The AI demo reports `configured: false` when no provider
key is set and NEVER returns a canned AI answer — a missing key
is a configuration state, not a success. The Postgres path is
implemented but NOT live-tested locally (tests run on SQLite);
the README says so plainly and that note stays until a live
Postgres round-trip actually passes. A live provider call has
NOT been tested here (no real key exists in this repo); the
README says so plainly. No APP_KEY means the container refuses
to boot — it never silently runs unencrypted. Anything not
configured reports clearly instead of crashing.

## Why this template exists

No Railway template covers Laravel's first-party AI SDK
(`laravel/ai`). Checked live 2026-11-10: the nearest Laravel+AI
templates are clanker-laravel (Laravel 12 + a third-party
Clanker support widget, not the SDK), Relaticle (a CRM with an
AI assistant, not a starter), and OpnForm (optional OpenAI form
generation, not the SDK). This template is the minimal starting
point for the SDK: one demo agent, one demo endpoint, Postgres,
healthcheck, zero required env vars at deploy.

## Layout

- `Dockerfile` — multi-stage, copied from the sibling
  security-hardened Laravel starter. Build stage:
  `php:8.5.12-cli-alpine` + Composer, installs deps. Runtime
  stage: `php:8.5.12-fpm-alpine` + nginx, no build tools, runs
  as `www-data`. php-fpm + nginx on purpose: mod_php runs the
  interpreter inside the web server, so the php2shell exploit
  runs before application code — never use it.
- `docker/nginx.conf` — template (`${PORT}` substituted at
  start). Only `public/index.php` is ever executed; any other
  `.php` path returns 404; hidden files are never served.
- `docker/php/security.ini` — `session.upload_progress.enabled=0`
  (CVE-2026-115560), `expose_php=0`, strict session cookies,
  `display_errors=0`.
- `docker/php/php-fpm.conf` — TCP-loopback pool (127.0.0.1:9000),
  non-root, dynamic PM.
- `docker/entrypoint.sh` — renders nginx config, clears caches,
  REFUSES to boot without `APP_KEY`, runs migrations, starts
  php-fpm + nginx. No seeding (this template has no seed data).
- `railway.toml` — `builder = "DOCKERFILE"` (Railpack resolves PHP
  fuzzily; only a Dockerfile pins the exact patch), healthcheck
  `/api/health`, pre-deploy `php artisan migrate --force`.
- `app/Ai/Agents/DemoAssistant.php` — the one demo agent: plain
  text, no tools, no conversation storage. Declares no provider
  on purpose; the controller picks the first provider with a key.
- `app/Http/Controllers/Api/AiDemoController.php` — GET
  `/api/ai/demo` (configuration status) and POST `/api/ai/demo`
  (one prompt through the agent). Provider order: OpenAI,
  Anthropic, Gemini, DeepSeek, Groq, xAI, Mistral, OpenRouter.
  No key → `configured: false` + which variable to set, HTTP
  200, no agent call. Provider error → HTTP 502 with the real
  error message, never a canned answer.
- `app/Http/Controllers/Api/HealthController.php` — `GET
  /api/health`: 200 with real DB status (`ok` / `error`),
  Laravel + PHP versions, AI SDK status. Degraded DB → 503.
- `app/Http/Middleware/SecurityHeaders.php` — CSP, HSTS,
  X-Content-Type-Options, X-Frame-Options, Referrer-Policy,
  Permissions-Policy on every response.
- `config/ai.php` — published SDK config (verbatim from
  laravel/ai 1.x, 2026-11-10). All provider keys are env vars;
  none has a value in code.
- `database/migrations/2026_01_11_000001_create_agent_conversations_table.php`
  — published SDK migration (verbatim from laravel/ai 1.x):
  creates `agent_conversations` and
  `agent_conversation_messages` for conversation storage.
  The demo agent does not use them; they exist so the SDK's
  conversational agents work out of the box.
- `routes/api.php` — public health, public AI demo (POST
  throttled 10/min).
- `tests/Feature/` — health (real DB status + 503 case),
  AI demo (unconfigured status, unconfigured prompt, faked
  agent end to end, validation), security headers. Run against
  the REAL app with SQLite; no mocks of the HTTP layer.
- `railway/railway-template.json` — transcription of the exact
  Template Composer settings. Railway does NOT read it
  automatically.

## Commands

```bash
cp .env.example .env   # then set APP_KEY (php artisan key:generate)
composer install
php artisan migrate
php artisan serve --port=8000
php artisan test       # full suite (SQLite, real HTTP)
composer audit         # must be clean before any release
```

## Do / Don't

- DO re-verify a version on Packagist / Docker Hub before pinning
  it, and update the README version table in the same change.
- DO run `php artisan test` + `composer audit` after changes and
  fix real errors. Do not claim a test passes if it did not. Do
  not claim a flow works from a status code alone — check the
  response body, including the DB status in `/api/health` and the
  `configured` flag on the AI demo.
- DO keep `.env.example` placeholders-only. There are no required
  secrets in this repo beyond `APP_KEY` and `DATABASE_URL`, and
  neither has a value in code — missing `APP_KEY` is a fatal boot
  error, by design. One documented exception: `phpunit.xml`
  carries a test-only `APP_KEY` (commented "never use it in
  production") — the suite requires it; it is a throwaway test
  key, not the production key, and must never be copied into
  `.env` or a deployment. The AI demo test sets a test-only
  fake provider key (`test-key`) in config only, never a real key.
- DON'T add the `token` auth guard anywhere. There is no auth in
  this starter at all.
- DON'T return a canned AI answer when no provider key is set.
  The demo must report `configured: false`.
- DON'T claim the Postgres path is tested on Railway without a
  real Railway Postgres round-trip. Local tests run on SQLite
  only — the README says so plainly.
- DON'T claim a live provider call works. No real provider call
  has been made from this template; the only end-to-end AI test
  uses the SDK's fake agent.
- DON'T enable `session.upload_progress` or mod_php. Both are
  disabled on purpose (CVE-2026-115560 / php2shell).
- DON'T set `APP_DEBUG=true` in production, and don't add a
  feature that needs a new env var without documenting it in
  `.env.example` and README.
- DON'T push, deploy, or publish from here. This repo is prepared
  locally; pushing and Railway template creation are separate,
  human-approved steps.

## Railway notes

- Add a Postgres service; set `DB_CONNECTION=pgsql` and
  `DATABASE_URL` as a reference variable,
  `${{Postgres.DATABASE_URL}}`, on the app service.
- Set `APP_KEY` (generate with `php artisan key:generate --show`)
  and `APP_URL` (the public domain) as service variables.
- Set ONE provider key (`OPENAI_API_KEY`, `ANTHROPIC_API_KEY` or
  `GEMINI_API_KEY`) to make the demo call a real model. All are
  optional: without one, the demo reports `configured: false`.
- The Dockerfile is the PHP pin: Railway builds the exact
  `php:8.5.12-fpm-alpine` image. If that tag ever disappears from
  Docker Hub, the build fails loudly instead of silently using a
  different PHP — that is the intended behaviour.
- Live deploy and Railway's Postgres service are NOT yet tested —
  see README "What is still unproven".
