# 0002 — Docker-first development, MySQL in CI, SQLite for fast local tests

- **Status:** Accepted
- **Date:** 2026-10-17

## Context
Reviewers should be able to clone the repo and see a running app without installing PHP, MySQL or Node.
Production targets MySQL 8, whose behaviour (strict mode, locking, JSON columns) differs from SQLite.

## Decision
- `docker compose up` builds a PHP-FPM 8.4 image, starts Nginx, MySQL 8.4, Mailpit, a queue worker and a
  one-shot Node container that compiles assets. The app container migrates and seeds demo data on first boot.
- The dev image runs PHP-FPM as a user with the host UID/GID so bind-mounted files stay writable.
- `phpunit.xml` defaults to in-memory SQLite for a fast inner loop; CI overrides the env and runs the full suite
  against a real MySQL 8.4 service. Code must therefore stay database-agnostic (no raw vendor-specific SQL).
- The queue uses the `database` driver — no Redis container needed for a project of this size.

## Consequences
- One command to a working demo. First boot is slower (composer install + seed).
- Pessimistic locks (`lockForUpdate`) are no-ops on SQLite; concurrency behaviour is only truly exercised on MySQL.
