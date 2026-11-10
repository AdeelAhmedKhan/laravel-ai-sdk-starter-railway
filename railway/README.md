# Laravel AI SDK Starter — Railway template notes

Minimal Laravel 13 starter with the Laravel AI SDK (`laravel/ai`)
wired in. One demo agent, one demo endpoint, Postgres, healthcheck.
Zero required env vars at deploy.

## What it deploys

- **app** — Laravel 13.40.0 + AI SDK 1.3.0 on PHP 8.5.12
  (Dockerfile pin), php-fpm + nginx. Public.
- **Postgres** — Railway Postgres service, Postgres 18, private
  network, persistent volume.

## Variables

- `APP_KEY` — generated at deploy time. The app refuses to boot
  without it.
- `APP_URL` — the public domain, once generated.
- `DB_CONNECTION=pgsql`, `DATABASE_URL=${{Postgres.DATABASE_URL}}`.
- `OPENAI_API_KEY` / `ANTHROPIC_API_KEY` / `GEMINI_API_KEY` — all
  OPTIONAL. Set one to make the demo call a real model. Without
  one, `GET /api/ai/demo` and `POST /api/ai/demo` report
  `configured: false` and never return a fake answer.

## Endpoints

- `GET /` — JSON pointer to the endpoints.
- `GET /api/health` — real DB status (200 ok / 503 degraded),
  versions, AI SDK status. This is the deploy healthcheck.
- `GET /api/ai/demo` — configuration status, no AI call.
- `POST /api/ai/demo` — `{"prompt": "..."}` through the demo
  agent. Throttled 10/min. No key → `configured: false`;
  provider error → 502 with the real error message.

## What is NOT in this template

Auth scaffolding, billing, a frontend build, a vector store, or
any AI provider key.

## Verification status

Verified locally 2026-11-10 (PHP 8.5.12, Laravel 13.40.0, AI SDK
1.3.0): `composer install` + `composer audit` clean, migrations
run on SQLite (including the SDK's agent conversation tables),
9 tests pass (28 assertions), live server smoke test of `/`,
`/api/health`, `/api/ai/demo` (GET + POST, no-key + validation
cases) — see `docs/local-test-2026-11-10.md`.

Still untested: live Railway deploy, Railway's Postgres service
round-trip, Docker build on Railway's side, a live provider call
(no real key exists in this repo), first-day cost.
