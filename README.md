# Laravel AI SDK Starter

Minimal Laravel starter with the Laravel AI SDK wired in — the
starting point for building with `laravel/ai` on Railway. One demo
agent, one demo endpoint, Postgres, healthcheck. Zero required env
vars at deploy.

No Railway template covers Laravel's first-party AI SDK. Checked
live 2026-11-10: the nearest Laravel+AI templates are
clanker-laravel (Laravel 12 + a third-party Clanker support widget,
not the SDK), Relaticle (a CRM with an AI assistant, not a
starter), and OpnForm (optional OpenAI form generation, not the
SDK).

## Stack & versions

| Piece | Version | Source |
|---|---|---|
| PHP | **8.5.12** (exact, Dockerfile pin) | endoflife.date, 2026-11-10 |
| Laravel | **13.40.0** (exact) | Packagist, 2026-11-10 |
| Laravel AI SDK | **1.3.0** (exact) | Packagist, 2026-11-10 |
| Postgres | 18 (Railway service) | Railway Postgres |
| Web server | nginx + php-fpm (Alpine) | — |

Why a Dockerfile and not Railpack/Nixpacks: only a Dockerfile
pins the exact PHP patch. Railpack resolves PHP fuzzily and
documents no exact-patch pin. The pin matters because PHP 8.5.12
is the release that patches the October 2026 php2shell RCEs
(CVE-2026-109618 / CVE-2026-109619, CVSS 9.2). If the tag ever
disappears from Docker Hub, the build fails loudly instead of
silently using a different PHP — that is the intended behaviour.

## What it deploys

- **app** — Laravel + AI SDK, public, healthcheck `/api/health`.
- **Postgres** — private network, persistent volume
  (`/var/lib/postgresql/data`).

## Endpoints

| Route | What it does |
|---|---|
| `GET /` | JSON pointer to the endpoints |
| `GET /api/health` | Real DB status (200 ok / 503 degraded), Laravel + PHP versions, AI SDK status |
| `GET /api/ai/demo` | AI configuration status — no AI call |
| `POST /api/ai/demo` | One prompt through the demo agent (`{"prompt": "..."}`), throttled 10/min |

## The demo agent

`App\Ai\Agents\DemoAssistant` is a plain text agent: short
instructions, no tools, no conversation storage. The controller
(`App\Http\Controllers\Api\AiDemoController`) picks the first
provider with a key configured, in this order: OpenAI, Anthropic,
Gemini, DeepSeek, Groq, xAI, Mistral, OpenRouter.

**When no provider key is configured, the demo says so plainly
(`configured: false` + which variable to set) and never returns a
fake AI answer.** A missing key is a configuration state, not a
success. A provider error returns HTTP 502 with the real error
message, never a canned answer.

## Deploy on Railway

1. Deploy this template (adds the app + Postgres services).
2. Set `APP_KEY` — generate with `php artisan key:generate
   --show` and paste the value. The app refuses to boot without
   it.
3. Set `APP_URL` to the public domain once generated.
4. Set `DB_CONNECTION=pgsql` and
   `DATABASE_URL=${{Postgres.DATABASE_URL}}` on the app service.
5. Optional: set ONE provider key (`OPENAI_API_KEY`,
   `ANTHROPIC_API_KEY` or `GEMINI_API_KEY`) to make the demo call
   a real model. All are optional.

Try it:

```bash
curl https://your-app.up.railway.app/api/ai/demo
curl -X POST https://your-app.up.railway.app/api/ai/demo \
  -H "Content-Type: application/json" \
  -d '{"prompt": "What is Laravel?"}'
```

## Run locally

```bash
cp .env.example .env   # then set APP_KEY (php artisan key:generate)
composer install
php artisan migrate
php artisan serve --port=8000
php artisan test       # full suite (SQLite, real HTTP)
composer audit         # must be clean before any release
```

## What is NOT in this template

- Auth scaffolding — no users, no login, no tokens.
- Billing or subscriptions.
- A frontend build (no Vite, no Tailwind, no Blade views).
- A vector store, embeddings pipeline, or RAG — the demo is one
  text agent call. Copy the agent and add tools, structured
  output, or conversation storage from the SDK docs
  (laravel.com/docs/13.x/ai-sdk).

## Security notes

- php-fpm + nginx, never mod_php (php2shell runs before app code
  under mod_php).
- `session.upload_progress.enabled=0` in `docker/php/security.ini`
  (CVE-2026-115560), `expose_php=0`, strict session cookies,
  `display_errors=0`.
- Security headers (CSP, HSTS, nosniff, SAMEORIGIN,
  Referrer-Policy, Permissions-Policy) on every response.
- Only `public/index.php` is ever executed; any other `.php` path
  returns 404; hidden files are never served.
- `APP_DEBUG=false` by default in production.

## Verified locally 2026-11-10

Run on PHP 8.5.12 (the exact pinned version), Laravel 13.40.0,
AI SDK 1.3.0. Full evidence:
`docs/local-test-2026-11-10.md`.

- `composer install` + `composer audit` clean (no advisories).
- Migrations run on SQLite, including the SDK's
  `agent_conversations` / `agent_conversation_messages` tables.
- **9 tests pass (28 assertions), 0 failures.**
- Live server smoke test: `/`, `/api/health`, `/api/ai/demo`
  (GET + POST, no-key + validation cases) — all 200/422 as
  designed, bodies read in full.
- Secret scan clean (no real keys, tokens, or passwords anywhere
  in the repo outside `vendor/`).

Two bugs were found and fixed by the parity test itself: a missing
`Request` import in `bootstrap/app.php` (one test failed with a
TypeError) and a missing `sessions` table (GET / 500'd — the
database session driver needs the table; the API routes are
stateless and never touched it).

## What is still unproven

- **Live Railway deploy** — no paid Railway plan / trial active.
- **Railway's own Postgres service round-trip** — no local
  Postgres server exists in this VM; migrations + tests ran on
  SQLite only. (pdo_pgsql loads; the connection itself is
  unproven.)
- **Docker build on Railway's side** — no Docker in this VM.
- **Live provider call** — no real OpenAI/Anthropic/Gemini key
  exists anywhere in this repo, by design. The only end-to-end AI
  response ever produced here came from the SDK's fake agent in
  the test suite.
- **First-day cost on Railway** — unknown until a real deploy.

## Template spec

`railway/railway-template.json` is a transcription of the exact
Template Composer settings (name, description, category, services,
healthcheck, variables). Railway does not read it automatically —
enter the settings in the Template Composer at publish time and
check the published page against the file. `sourceRepo` /
`sourceBranch` are set at publish time, once the GitHub repo
exists.
