# 0006 — Production events run inside the step transaction

- **Status:** Accepted
- **Date:** 2026-10-17

## Context
Completing a step has several consequences: backflush materials, add scrap to the production order,
book finished goods after the last step, complete the production order, and complete the sales order
once all its production is done. Some of these can fail (e.g. not enough stock).

## Decision
- `CompleteStep` changes the step and dispatches `StepCompleted` **inside** its `DB::transaction`.
  Listeners (`ConsumeStepMaterials`, `AdvanceProductionOrder` → `ProductionOrderCompleted` →
  `UpdateSalesOrderProgress`) run synchronously in that transaction, in registration order.
- If any listener throws (e.g. `InsufficientStock`), the whole completion is rolled back and the operator
  sees the reason. The step stays *running*, with its clock still open.
- Side effects that talk to the outside world are different: they implement `ShouldDispatchAfterCommit`
  and/or are queued (low-stock e-mails, PDF generation), so they never fire for rolled-back work.
- Rows that change concurrently (step, material, sales order) are re-read with `lockForUpdate()`.
  Step actions lock top-down — sales order → production order → step (`LocksStepForWork`) — the same order
  the sales order actions use, so a step started while its order is being cancelled, or two production orders
  of one sales order completing at once, serialise instead of overwriting each other or deadlocking.
  Materials are locked last, in `material_id` order.

## Consequences
- Listeners keep each responsibility small and testable, without giving up atomicity.
- Listener order matters and is declared explicitly in `AppServiceProvider::registerDomainListeners()`.
