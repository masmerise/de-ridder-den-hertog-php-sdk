# Roadmap: De Ridder & Den Hertog PHP SDK

## Overview

Milestone v1.0 adds a single-page cursor-based pagination fetch primitive to the existing SDK. The three required components — `Cursor` value object, `DayTurnoverPage` DTO, and `getDayTurnoverPage` facade method — form one coherent capability with a strict dependency chain. They are built in order and delivered as a complete unit. No existing classes are modified.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Single-Page Cursor Fetch** - Implement `Cursor`, `DayTurnoverPage`, and `getDayTurnoverPage` as a complete, tested addition to the SDK

## Phase Details

### Phase 1: Single-Page Cursor Fetch
**Goal**: SDK consumers can fetch one page of day turnover at a time by providing a `PerPage` and an optional `Cursor`, receiving a typed `DayTurnoverPage` that includes the transactions and the cursor to the next page
**Depends on**: Nothing (first phase)
**Requirements**: PAG-01, PAG-02, PAG-03
**Success Criteria** (what must be TRUE):
  1. A `Cursor` value object exists, rejects negative integers, and is accepted anywhere a cursor parameter is expected
  2. A `DayTurnoverPage` value exists holding a `Transactions` collection and a nullable next `Cursor` that is `null` when no further pages remain
  3. `getDayTurnoverPage(PerPage, ?Cursor, ...)` can be called with `null` cursor to fetch the first page and returns a `DayTurnoverPage`
  4. `getDayTurnoverPage` called with the `nextCursor` from a previous page returns the subsequent page's transactions
  5. All existing tests remain green and `getDayTurnoverPaginated` is unmodified
**Plans:** 2 plans

Plans:
- [ ] 01-01-PLAN.md — Cursor value object with unit tests
- [ ] 01-02-PLAN.md — DayTurnoverPage DTO, getDayTurnoverPage facade method, integration test

## Progress

**Execution Order:**
Phases execute in numeric order: 1

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Single-Page Cursor Fetch | 0/2 | Not started | - |
