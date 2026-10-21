# 0008 — Material allocation is derived from the BOM snapshot, not stored

- **Status:** Accepted
- **Date:** 2026-10-19

## Context
Planners need to see a shortage before production stalls: stock on hand says nothing about what released
production orders are about to consume. A separate reservation table would have to be kept in step with
every confirmation, cancellation and completion.

## Decision
- `Inventory\Queries\MaterialRequirements` computes the allocated quantity on demand from
  `production_order_materials` (see [0007](0007-bom-and-routing-snapshot.md)): the `required_qty` of every
  line of an active production order whose BOM has not been backflushed yet (`consumed_qty = 0`).
- Orders in which a completed step ended with no good units (`good_qty = 0`) are excluded: every following
  step receives `input_qty = 0`, so the consuming step would backflush nothing and the allocation would
  otherwise stay until the order completes. A step that did consume has `consumed_qty > 0` and is already
  excluded.
- *Available* = on hand − allocated. A material is *short* when available is negative.
- The dashboard, the materials list (Allocated / Available columns, "Short for production" filter) and the
  material page ("Open demand") all read from this one query.

## Consequences
- Nothing new to keep consistent: confirming, cancelling or completing a step changes the answer by itself.
- Drafts do not allocate; only confirmed work does.
- Because the BOM is consumed in one go, a line is either fully outstanding or done. If partial consumption
  is ever modelled, the query becomes `required_qty − consumed_qty` per line.
