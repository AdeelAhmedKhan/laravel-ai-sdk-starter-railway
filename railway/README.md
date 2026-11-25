# Laravel AI SDK Starter — Railway template notes

Minimal Laravel 13 starter with the Laravel AI SDK (`laravel/ai`)
wired in. One demo agent, one demo endpoint, Postgres, healthcheck.
Zero required env vars at deploy.

## What it deploys

- **app** — Laravel 13.40.0 + AI SDK 1.3.0 on PHP 8.5.13 (digest pinned)
  (Dockerfile pin), php-fpm + nginx. Public.
- **Postgres** — custom image (official postgres:18.7 pinned by
  digest + pgvector 0.8.7 from PGDG), SSL on by default on fresh
  deploys, private network, persistent volume.

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

Live-verified on Railway 2026-11-25 (PHP 8.5.13, Laravel 13.40.0,
AI SDK 1.3.0): fresh zero-input deploy, app and Postgres Online,
`/api/health` returns db ok with pgsql, `/api/ai/demo` reports
configured false with no key. Postgres 18.7 with pgvector 0.8.7:
TLS 1.3 over a required-SSL connection, SHOW ssl on,
nearest-neighbour order correct.

Upgrade drill 2026-11-25: an existing deploy from 2026-11-21
(SSL off) updated to the latest commit and redeployed stays on
SSL off, because init scripts only run on an empty data
directory. Existing owners enable SSL with two statements as the
database superuser, then reload: `ALTER SYSTEM SET ssl = 'on';`
`SELECT pg_reload_conf();` verified to give SHOW ssl on and
TLS 1.3 with no restart. Certificates are already on the volume
from first boot.

Still untested: a live provider call (no real key exists in this
repo), first-day cost.
