# 0007 — Snapshot routing and BOM when a production order is released

- **Status:** Accepted
- **Date:** 2026-10-17

## Context
Engineers change products over time. A production order already on the shop floor must keep the
routing and material list it was released with, or time and stock calculations would change under it.

## Decision
- Confirming a sales order calls `GenerateProductionOrders` in the same transaction: one production order
  per order line, copying routing steps into `production_order_steps` and BOM lines into
  `production_order_materials` (with required quantities for the ordered amount).
- Exactly one routing step is flagged to consume the BOM (default: the first). On completion it consumes
  `quantity_per_unit × units that entered the step`, so scrapped units still consume material.
- The last step books its good output into the finished-good stock item; scrap is accumulated per order.
- A step can only start when the previous one is completed; its input is the previous step's good output.

## Consequences
- Editing a product only affects production orders released afterwards.
- Partial consumption across several steps is not modelled; one consuming step per routing keeps
  backflushing simple and predictable.
