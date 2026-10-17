# 0001 — Domain layer organised by bounded context, logic in Actions

- **Status:** Accepted
- **Date:** 2026-10-17

## Context
ERP/MES code tends to rot when business rules leak into controllers, Blade views and model events.
The same rule (e.g. "completing a step consumes stock") must be reachable from the web UI, the shop-floor
tablet screen, the REST API and queued jobs.

## Decision
- Business logic lives in `app/Domain/<Context>` (`Sales`, `Production`, `Inventory`, `Catalog`, `Import`, `Audit`).
- Each use case is a single-purpose **Action** class with one public `execute()` / `handle()` method,
  resolved from the container. Cross-cutting calculations live in **Services**.
- Eloquent models stay in `app/Models` (Laravel convention, factories and tooling work unchanged) and hold
  relations, casts and scopes only.
- Controllers (web and API) are thin: authorise → validate via Form Request → call an Action → return a view/resource.
- Status fields are backed **enums** that own their transition rules.

## Consequences
- Web and API share exactly the same code path, so a single set of domain tests covers both.
- More classes than a "fat controller" app, but each one is small and unit-testable.
- Coverage targets are measured against `app/Domain`.
