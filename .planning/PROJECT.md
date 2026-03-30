# De Ridder & Den Hertog PHP SDK

## What This Is

A PHP SDK for the De Ridder & Den Hertog (RENH) POS/retail system API. It wraps a non-standard SOAP-over-HTTP protocol (JSON payloads base64-encoded inside SOAP XML envelopes) into a clean, typed PHP interface for managing customers, retrieving day turnover/transactions, and querying available API functions.

## Core Value

Enable PHP applications to interact with the RENH API through a type-safe, well-structured SDK that handles the protocol complexity transparently.

## Requirements

### Validated

- ✓ GUID-based authentication with format validation — existing
- ✓ Get customers with optional field selection, filtering, and date range — existing
- ✓ Get day turnover (all records at once) with optional filter and date range — existing
- ✓ Get day turnover with cursor-based pagination (iterates all pages) — existing
- ✓ Delete customer by ID — existing
- ✓ Create/update customer — existing
- ✓ List authorized API functions — existing
- ✓ Typed value objects for all parameters (ApiGuid, Date, Filter, PerPage, CustomerId, Fields) — existing
- ✓ Dedicated typed collections (Customers, Transactions, ApiFunctions) — existing
- ✓ Exception hierarchy with action-specific validation exceptions — existing
- ✓ SOAP envelope wrapping/unwrapping via middleware — existing

### Active

- [ ] Fetch a single page of day turnover by cursor — return a `DayTurnoverPage` containing a `Transactions` collection and the next cursor
- [ ] `Cursor` value object for type-safe cursor parameter (consistent with other value objects like `PerPage`, `CustomerId`)
- [ ] `DayTurnoverPage` DTO bundling transactions and next cursor metadata

### Out of Scope

- Offset-based (page number) pagination — the RENH API uses cursor-based pagination only
- Modifying the existing `getDayTurnoverPaginated` method — it stays as-is for full iteration use cases
- Adding single-page fetch for other endpoints — only GetDayTurnover supports pagination currently

## Current Milestone: v1.0 Single-Page Day Turnover Fetch

**Goal:** Add the ability to fetch a single page of day turnover by cursor, complementing the existing full-iteration paginator.

**Target features:**
- Fetch a single page of day turnover by cursor → returns `DayTurnoverPage`
- `Cursor` value object for type-safe cursor parameter
- `DayTurnoverPage` DTO bundling `Transactions` collection and next cursor metadata

## Context

- The RENH API uses cursor-based pagination via `LastRecord` (sent in request) and `Lastrecord` (returned in response)
- `RequestCount` controls page size, `NbRecords` in response indicates how many records were returned
- The existing `CursorPaginator` (Saloon plugin) iterates all pages automatically — the new feature allows fetching just one page at a time with a known cursor
- All classes follow `final readonly` with private constructors and named static factory methods
- PHP 8.5+ with pipe operator (`|>`) used in response processing

## Constraints

- **Tech stack**: PHP 8.5+, Saloon 4.0, must follow existing value object patterns
- **API protocol**: SOAP-over-HTTP with JSON-in-base64 encoding — cannot change the wire format
- **Consistency**: New types must follow existing patterns (private constructor, static factory, `toMessageString`/`toMessageArray`)

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| New `getDayTurnoverPage` method (not modifying existing) | Keeps `getDayTurnoverPaginated` for full-iteration use cases, adds a focused method for single-page fetch | — Pending |
| `Cursor` as value object | Consistent with `PerPage`, `CustomerId`, and other parameter types in the SDK | — Pending |
| `DayTurnoverPage` as return type | Bundles `Transactions` collection with `nextCursor` metadata in a single domain-specific DTO | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd:transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd:complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-03-30 after milestone v1.0 started*
