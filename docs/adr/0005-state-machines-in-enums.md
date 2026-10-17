# 0005 — Status workflows are enum state machines

- **Status:** Accepted
- **Date:** 2026-10-17

## Context
Sales orders, production orders and production steps each have a status workflow. Invalid jumps
(shipping a draft, completing a paused step) must be impossible from every entry point.

## Decision
- Each status is a backed enum using the `GuardsTransitions` trait and declaring its graph in `transitions()`.
- Models expose `transitionTo()` which asserts the move and stamps the matching timestamp
  (`confirmed_at`, `shipped_at`…).
- Violations throw `InvalidStateTransition` (a `DomainException`). A single exception renderer turns any
  `DomainException` into a flash message on the web and a `422` JSON response on the API.
- Unit tests enumerate the full from × to matrix so an accidental new edge fails the build.

## Consequences
- The workflow is readable in one `match` per enum and has zero dependencies.
- No persisted transition history beyond timestamps and the audit log; acceptable at this scale.
