# Architecture Decision Records

Short, dated notes explaining *why* the project is built the way it is. Format: context → decision → consequences.

| # | Title | Status |
|---|---|---|
| [0001](0001-domain-layer-with-actions.md) | Domain layer organised by bounded context, logic in Actions | Accepted |
| [0002](0002-docker-first-development.md) | Docker-first development, MySQL in CI, SQLite for fast local tests | Accepted |
| [0003](0003-quality-gates.md) | Quality gates: Pint, PHPStan level 6, PHPUnit on every push | Accepted |
| [0004](0004-roles-and-audit-without-packages.md) | Roles as an enum, audit log built in-house | Accepted |
| [0005](0005-state-machines-in-enums.md) | Status workflows are enum state machines | Accepted |
| [0006](0006-production-events-inside-the-transaction.md) | Production events run inside the step transaction | Accepted |
| [0007](0007-bom-and-routing-snapshot.md) | Snapshot routing and BOM when a production order is released | Accepted |
| [0008](0008-material-allocation-from-the-bom-snapshot.md) | Material allocation is derived from the BOM snapshot, not stored | Accepted |
| [0009](0009-display-timezone.md) | Timestamps stored in UTC, rendered in a configurable display timezone | Accepted |
