# 0004 — Roles as an enum, audit log built in-house

- **Status:** Accepted
- **Date:** 2026-10-17

## Context
The app has four fixed roles (admin, planner, operator, sales). Packages such as `spatie/laravel-permission`
and `spatie/laravel-activitylog` are excellent, but they add tables and concepts (dynamic permissions,
teams, causers) this domain does not need.

## Decision
- `users.role` is a string column cast to the `UserRole` enum.
- Screen-level access uses **gates** (`view-dashboard`, `use-shop-floor`, `manage-stock`…); record-level access
  uses **policies**. `Gate::before` grants admins everything.
- Policies check *roles only*. Whether a status allows an action (e.g. editing a confirmed order) is a business
  rule enforced by the domain action, so it also applies to admins and to the API.
- Auditing is an `Auditable` trait hooked into Eloquent `created/updated/deleted` events that writes changed
  attributes (before/after), user and IP to `audit_logs`. Passwords and timestamps are never logged;
  models can exclude noisy columns (e.g. `stock_qty`, already traced by the stock ledger).

## Consequences
- Adding a role is a code change (enum case + gates), which is acceptable for a closed set.
- Bulk query-builder updates bypass the audit trait; domain actions always go through models.
