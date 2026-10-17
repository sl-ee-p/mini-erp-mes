# 0003 — Quality gates: Pint, PHPStan level 6, PHPUnit on every push

- **Status:** Accepted
- **Date:** 2026-10-17

## Context
A portfolio project should show the same discipline expected on a team codebase.

## Decision
GitHub Actions runs four parallel jobs on every push and pull request:
1. **Pint** (`--test`, Laravel preset + `declare(strict_types=1)`)
2. **PHPStan / Larastan** at level 6
3. **PHPUnit** on PHP 8.4 against MySQL 8.4, with PCOV coverage
4. **Vite build** to catch broken frontend assets

## Consequences
- Every merged commit is formatted, type-checked and tested.
- Level 6 requires array shapes / generics on public APIs, which is enforced in PHPDoc.
