# Phase 1: Single-Page Cursor Fetch - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-03-30
**Phase:** 01-single-page-cursor-fetch
**Areas discussed:** Cursor bounds, Page result shape, Method parameters, Namespace placement

---

## Cursor Bounds

| Option | Description | Selected |
|--------|-------------|----------|
| > 0 only (Recommended) | Strictly positive integers. Null cursor means 'first page' — 0 has no meaning in the API protocol. | |
| >= 0 | Allow zero. Would let callers use Cursor::fromInteger(0) as a 'start from beginning' sentinel. | |

**User's choice:** Custom — Allow zero, but with a dedicated `Cursor::start()` static constructor that sets 0 as its backing value. `Cursor::fromInteger(int)` for positive values from API responses.
**Notes:** User wants two distinct factory methods with different semantics rather than a single factory that accepts 0.

---

## Page Result Shape

| Option | Description | Selected |
|--------|-------------|----------|
| Just ?Cursor (Recommended) | nextCursor is null when no more pages. Simple, no redundant state. | |
| Both ?Cursor and bool | Add hasMore for convenience. Slightly redundant but more explicit. | |

**User's choice:** Custom — There should always be a non-nullable Cursor object. The cursor itself should expose `hasMore()` or `isLastPage()` to indicate pagination state.
**Notes:** User wants the cursor to be a first-class object carrying its own state, never null in the page result.

---

## Method Parameters

| Option | Description | Selected |
|--------|-------------|----------|
| Same params (Recommended) | Accept Filter, Date from/to alongside PerPage and Cursor — matches getDayTurnover signature. | ✓ |
| Minimal: PerPage + Cursor | Only pagination params. | |

**User's choice:** Same params (Recommended)
**Notes:** None — straightforward selection.

---

## Namespace Placement

| Option | Description | Selected |
|--------|-------------|----------|
| Core/Type/Parameter/ | Shared parameter namespace alongside PerPage, Filter, Date. | |
| GetDayTurnover/Type/Parameter/ | Action-specific. Only GetDayTurnover uses cursors currently. | ✓ |

**User's choice:** GetDayTurnover/Type/Parameter/
**Notes:** Action-specific placement. Can be moved to Core later if other endpoints need it.

---

## Claude's Discretion

- Method name on Cursor: `hasMore()` vs `isLastPage()` — Claude picks what reads best
- Internal cursor construction from API response
- Static factory naming for DayTurnoverPage

## Deferred Ideas

None
