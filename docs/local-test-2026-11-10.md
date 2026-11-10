# Local parity test — Laravel AI SDK starter — 2026-11-10

Run by hand on this VM. Every check below was actually executed;
nothing here is inferred from a status code alone — response bodies
were read in full.

## Environment

- PHP **8.5.12** (cli, NTS, built 21 Oct 2026) — the exact pinned
  version, installed from the ondrej/php PPA
  (`php8.5-cli 8.5.12-1+ubuntu24.04.1+deb.sury.org+1`).
  The VM shipped with NO PHP at all; getting it took an
  ondrej/php PPA add (the Ubuntu archive mirror hung on
  `apt-get update` twice, and its keyring needed chmod 644 so the
  `_apt` user could read it) plus two .debs fetched directly
  (libpq5 16.2-1ubuntu4, libzip4t64 1.7.3-1.1ubuntu2) because the
  archive lists never landed. None of that affects the template:
  the Dockerfile pins `php:8.5.12-fpm-alpine` / `cli-alpine`.
- Extensions loaded (matching the Dockerfile): pdo_sqlite,
  pdo_pgsql, pgsql, bcmath, intl, zip, pcntl, OPcache, mbstring,
  curl, xml.
- Composer **2.11.1** (installed at ~/workspace/tools/composer/).
- Laravel **13.40.0**, Laravel AI SDK **1.3.0** (both verified on
  Packagist before pinning; `composer show` confirms installed).

## Steps and results

| # | Check | Command / evidence | Result |
|---|-------|--------------------|--------|
| 1 | Gap check (live) | railway.com/deploy search "Laravel AI SDK": nearest are clanker-laravel (Laravel 12 + third-party Clanker support widget, NOT laravel/ai), Relaticle (CRM with AI assistant, not a starter), OpnForm (optional OpenAI form generation, not the SDK). No template mentions `laravel/ai`. | PASS — gap confirmed |
| 2 | Versions | Packagist p2 API: laravel/ai latest v1.3.0; laravel/framework 13.x latest v13.40.0; endoflife.date: PHP 8.5 latest 8.5.12. | PASS — all pinned exact |
| 3 | `composer update` | Resolved + installed laravel/ai 1.3.0, framework 13.40.0; removed laravel/sanctum and fakerphp/faker. `composer show` confirms. | PASS |
| 4 | `composer validate` | Only warnings: exact version constraints "should be avoided" — intentional pins per house rules, not errors. | PASS (warnings noted) |
| 5 | `composer install` (dist) | Clean reinstall from the lockfile after zip ext was added. | PASS |
| 6 | `composer audit` | "No security vulnerability advisories found." | PASS |
| 7 | `php artisan migrate` (SQLite) | cache, cache_locks, jobs, job_batches, failed_jobs, **agent_conversations, agent_conversation_messages**, sessions — all ran. Table list verified via tinker. | PASS |
| 8 | `php artisan test` | **9 passed, 28 assertions, 0 failures, 0 warnings** (after a local .env was created; before that, 8 warnings — phpdotenv warning on the missing .env file, assertions still passed). | PASS |
| 9 | GET `/` (live server) | 200, body: `{"app":"Laravel AI SDK Starter","laravel":"13.40.0","php":"8.5.12","ai_sdk":"installed","health":".../api/health","ai_demo":".../api/ai/demo","docs":"See README.md..."}`. First attempt 500'd: `no such table: sessions` — SESSION_DRIVER=database needs a sessions table and the skeleton had none (latent bug also present in the sibling starter, whose web route was never hit live). Fixed by adding the standard sessions migration; re-ran migrate + tests, then GET / returned 200. | PASS after fix |
| 10 | GET `/api/health` (live) | 200, body: `{"status":"ok","db":"ok","db_error":null,"db_connection":"sqlite","php_version":"8.5.12","laravel_version":"13.40.0","ai_sdk":"installed","timestamp":"..."}`. | PASS — real DB status |
| 11 | GET `/api/ai/demo` (live, no key) | 200, `{"configured":false,"provider":null,"message":"No AI provider key is configured. Set OPENAI_API_KEY (or ANTHROPIC_API_KEY / GEMINI_API_KEY) as a service variable and redeploy, then POST a prompt to this endpoint.",...}`. No AI call made. | PASS — honest unconfigured state |
| 12 | POST `/api/ai/demo` (live, no key) | 200, `{"configured":false,"provider":null,"message":"No AI provider key is configured. ... No AI call was made."}`. No `text` field. | PASS — no fake success |
| 13 | POST `/api/ai/demo` (live, empty prompt) | 422, `{"message":"The prompt field is required.","errors":{"prompt":["The prompt field is required."]}}`. | PASS — validation works |
| 14 | Faked agent end to end (test suite) | `DemoAssistant::fake(['Laravel is a PHP framework.'])` + test-only fake key in config → POST returns 200 with `"text":"Laravel is a PHP framework."`, `assertPrompted` passes. No network, no real key. | PASS |
| 15 | Secret scan | grep for sk- / sk-ant- / ghp_ / AKIA / private keys / `api_key = "..."` / `password = "..."` across the repo excluding vendor: **0 matches**. The only key-like values are placeholders (`OPENAI_API_KEY=` empty in .env.example, `test-key` in the test config only, the test-only APP_KEY in phpunit.xml marked "never use it in production"). | PASS — clean |

## What was fixed during the test

1. `bootstrap/app.php` — my rewrite dropped the
   `use Illuminate\Http\Request;` import, so the
   `shouldRenderJsonWhen` closure type-hinted an unresolvable
   `Request`. One test failed with a TypeError. Fixed by
   restoring the import; full suite green after.
2. Missing `sessions` table — see check 9. Added the standard
   Laravel sessions migration
   (`database/migrations/0001_01_01_000003_create_sessions_table.php`).

## Still untested (honest list)

- **Live Railway deploy** — no paid Railway plan / trial active.
- **Railway's own Postgres service round-trip** — no local
  Postgres server exists in this VM; migrations + tests ran on
  SQLite only. (pdo_pgsql loads; the connection itself is
  unproven.)
- **Docker build on Railway's side** — no Docker in this VM; the
  Dockerfile was copied from the sibling starter (whose Docker
  build is equally untested locally) and only read, not run.
- **Live provider call** — no real OpenAI/Anthropic/Gemini key
  exists anywhere in this repo, by design. The only end-to-end
  AI response ever produced here came from the SDK's fake agent
  in the test suite (check 14).
- **First-day cost on Railway** — unknown until a real deploy.

## Local files removed after the test

- `.env` (created from .env.example + `key:generate` for the
  server run; held a real local APP_KEY) and
  `database/database.sqlite` (migrated test database) were
  deleted after the run. Both are gitignored and must never be
  in a push manifest.
