# 0009 — Timestamps are stored in UTC and rendered in a configurable display timezone

- **Status:** Accepted
- **Date:** 2026-10-21

## Context
The workshop is in Europe/Rome but timestamps were shown in UTC.

## Decision
- Storage stays UTC (`app.timezone`).
- `app.display_timezone` (env `APP_DISPLAY_TIMEZONE`, default `UTC`, so nothing changes unless set) is applied
  by `Format::dateTime()`, which every date-time rendering (audit log, stock ledger, step time logs, order
  creation date, PDF footer, stock CSV) goes through.
- Date-only attributes (`due_date`, `order_date`), document-number years and "late" logic are left alone.

## Consequences
- Remaining places that format a timestamp directly (for example the status timeline on a sales order, which
  mixes date-only and date-time values) should move to `Format::dateTime()` when touched.
