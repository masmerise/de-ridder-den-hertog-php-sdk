# Phase 1: Single-Page Cursor Fetch - Context

**Gathered:** 2026-03-30
**Status:** Ready for planning

<domain>
## Phase Boundary

Add a `getDayTurnoverPage` method to the SDK facade that fetches a single page of day turnover using a typed `Cursor` value object, returning a `DayTurnoverPage` DTO. The existing `getDayTurnoverPaginated` method is not modified.

</domain>

<decisions>
## Implementation Decisions

### Cursor Value Object
- **D-01:** `Cursor` accepts `>= 0`. Two static factories: `Cursor::start()` (backs to 0, represents "first page") and `Cursor::fromInteger(int)` (for positive values from API response).
- **D-02:** `Cursor` carries "end of data" state — exposes `hasMore()` or `isLastPage()` method so callers can check pagination status directly on the cursor.
- **D-03:** `Cursor` lives in `GetDayTurnover/Type/Parameter/` (action-specific, not shared Core).

### DayTurnoverPage DTO
- **D-04:** `DayTurnoverPage` always contains a non-nullable `Cursor` (not `?Cursor`). The cursor itself knows whether more pages exist via its `hasMore()`/`isLastPage()` method.
- **D-05:** `DayTurnoverPage` bundles `Transactions` collection and `Cursor` — follows `final readonly` pattern with private constructor and static factory.

### Facade Method
- **D-06:** `getDayTurnoverPage` accepts the same optional parameters as `getDayTurnover` — `Filter`, `Date` from/to — alongside `PerPage` and `Cursor`. Consistent API surface.
- **D-07:** Method uses existing `send()` helper and `CouldNotGetDayTurnover` exception — no new exception class needed.

### Claude's Discretion
- Whether `Cursor` exposes the method as `hasMore()` or `isLastPage()` — pick the name that reads best
- Internal construction of `Cursor` from API response (how to determine last-page state from `NbRecords` vs `RequestCount`)
- Whether `DayTurnoverPage` needs a static factory name like `::of()` or `::from()`

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### API Protocol
- `docs/RHAPI.pdf` — Original API documentation, defines `LastRecord`/`Lastrecord` field semantics and pagination protocol

### Existing Pagination
- `src/Core/Http/Pagination/CursorPaginator.php` — Existing full-iteration paginator, reference for `LastRecord`/`Lastrecord` casing, `NbRecords` last-page detection, and `RequestCount` page size injection

### Value Object Patterns
- `src/Core/Type/Parameter/PerPage.php` — Canonical value object pattern to follow for `Cursor` (private constructor, static factory, `toInteger()`)
- `src/Core/Type/Primitive/CustomerId.php` — Another integer-wrapping value object reference

### Facade Pattern
- `src/DeRidderDenHertog.php` — SDK facade where `getDayTurnoverPage` will be added, reference for `send()` helper pattern

### Existing GetDayTurnover
- `src/GetDayTurnover/Request/GetDayTurnover.php` — Request class to reuse (or reference for a new request class)
- `src/GetDayTurnover/Type/Transaction.php` — Domain DTO for individual transactions
- `src/GetDayTurnover/Type/Transactions.php` — Collection class used in DayTurnoverPage

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `CursorPaginator`: Contains last-page detection logic (`NbRecords < perPageLimit`) and cursor field names — reference implementation
- `PerPage`: Value object pattern to clone for `Cursor` (validation, factory, conversion)
- `Transactions`: Collection already exists, will be used directly in `DayTurnoverPage`
- `TransactionMapper`: Already maps raw API arrays to `Transaction` objects
- `GetDayTurnover` request: Can be reused or serve as reference for body parameter injection
- `CouldNotGetDayTurnover`: Exception class already exists for this API action

### Established Patterns
- `final readonly class` with private constructor and named static factories
- `toInteger()` / `toMessageString()` conversion methods on value objects
- `Assert::greaterThan()` / `Assert::greaterThanEq()` for validation in constructors
- Request body injection via `$request->body()->add()` post-construction
- `send()` helper on facade handles error wrapping with `$onFailure` class-string

### Integration Points
- `DeRidderDenHertog.php`: New `getDayTurnoverPage()` method added here
- `GetDayTurnover/Type/`: New `DayTurnoverPage.php` and `Parameter/Cursor.php` added here
- Wire protocol: `LastRecord` (request, capital R) and `Lastrecord` (response, lowercase r) — asymmetric casing is intentional

</code_context>

<specifics>
## Specific Ideas

- `Cursor::start()` is a distinct factory from `Cursor::fromInteger()` — semantically different entry points
- The cursor is never nullable in `DayTurnoverPage` — the cursor itself carries pagination state
- The user wants the cursor to feel like a first-class object that knows its own state, not just a primitive wrapper

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 01-single-page-cursor-fetch*
*Context gathered: 2026-03-30*
