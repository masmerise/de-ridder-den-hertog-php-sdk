# Feature Research

**Domain:** PHP SDK — Single-Page Cursor-Based Pagination Fetch (subsequent milestone)
**Researched:** 2026-03-30
**Confidence:** HIGH

## Context

This is a subsequent milestone research. The existing SDK already has:
- `CursorPaginator` — full iteration across all pages automatically
- `getDayTurnoverPaginated` — yields `Transactions` per page via the paginator
- All value objects (`PerPage`, `CustomerId`, `Date`, `Filter`, `ApiGuid`) following `final readonly`, private constructor, named static factory
- The `Result` DTO exposes `raw['Lastrecord']` (next cursor integer) and `raw['NbRecords']` (records returned this page)

The new milestone adds **single-page fetch**: caller supplies a cursor, gets back one page of `Transactions` plus the next cursor value, without the full iteration abstraction.

---

## Feature Landscape

### Table Stakes (Users Expect These)

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| `Cursor` value object | Consistent with every other SDK parameter type (`PerPage`, `CustomerId`, `Date`, `Filter`) — SDK consumers expect a typed wrapper, not a raw int | LOW | Wraps `int`, validates > 0 (or >= 0 if cursor 0 means "start from beginning" — must confirm API semantics). Named factory: `Cursor::fromInteger(int)`. Exposes `toInteger(): int` and `toMessageString(): string` for use in the request body `LastRecord` field. |
| `DayTurnoverPage` DTO | SDK consumers fetching a single page need both the `Transactions` for that page and the cursor to fetch the next page — returning just `Transactions` would force callers to re-parse raw response internals | LOW | `final readonly` class with public constructor or named static factory. Properties: `public Transactions $transactions` and `public ?Cursor $nextCursor` (null when last page, i.e. `NbRecords < RequestCount`). |
| `getDayTurnoverPage` method on `DeRidderDenHertog` | The facade/entry-point already has `getDayTurnover` and `getDayTurnoverPaginated` — a single-page method completes the trio and gives callers control over pagination flow | LOW | Signature: `getDayTurnoverPage(?Cursor $cursor, PerPage $perPage, ?Filter $filter, ?Date $from, ?Date $till): DayTurnoverPage`. Sends one request, maps result to `DayTurnoverPage`. |
| `CouldNotGetDayTurnover` reused for failure | Error handling is already defined; a new method on the same endpoint reuses the same failure exception | NONE | Already exists — no new exception class needed. |

### Differentiators (Competitive Advantage)

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| `null` cursor means "first page" | Callers starting fresh pass `null` rather than a magic `0` value — cleaner ergonomics and avoids confusion about what "zero cursor" means | LOW | The `GetDayTurnover` request simply omits `LastRecord` from the body when cursor is null, matching existing `CursorPaginator` behaviour which only adds `LastRecord` when a previous response exists. |
| `null` nextCursor signals end-of-results | When `NbRecords < RequestCount` (last page detection already used in `CursorPaginator::isLastPage`), `DayTurnoverPage::nextCursor` is `null` — callers can use a simple null-check to know when to stop | LOW | Reuses the same `isLastPage` logic already proven in the paginator. No extra API calls or sentinel values. |

### Anti-Features (Commonly Requested, Often Problematic)

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| Modifying `getDayTurnoverPaginated` to accept an initial cursor | Seems like adding cursor control to existing method is DRY | Would change the existing method's contract, breaking callers who rely on full-iteration behaviour; mixes two concerns (auto-iterate vs single-page) | Keep both methods separate — `getDayTurnoverPaginated` stays as full-iteration, `getDayTurnoverPage` is the single-page primitive. |
| Raw `int` cursor parameter instead of `Cursor` value object | Simpler to just take an `int` | Inconsistent with every other parameter in the SDK; no validation; no semantic clarity about what the integer represents | `Cursor` value object — same pattern as `PerPage`, `CustomerId`, keeps the API surface uniform. |
| `DayTurnoverPage` implementing `Countable` or `IteratorAggregate` | Make it act like a collection | The `Transactions` collection inside it already implements those interfaces; wrapping again doubles the API surface and creates ambiguity (count of what — transactions or pages?) | Callers access `$page->transactions` directly and iterate or count the collection from there. |
| Separate `LastPage` / `HasMore` boolean flag on `DayTurnoverPage` | Explicit flag is readable | Redundant with `nextCursor === null`; two sources of truth that could get out of sync | `null` nextCursor is the canonical last-page signal, consistent and non-redundant. |
| Single-page fetch for other endpoints (GetCustomers) | Customers could also benefit from pagination | Out of scope per PROJECT.md; RENH API cursor pagination only documented for GetDayTurnover currently | Defer until API confirms cursor support on other endpoints. |

---

## Feature Dependencies

```
Cursor (value object)
    └──required by──> getDayTurnoverPage (method)
                          └──produces──> DayTurnoverPage (DTO)
                                            └──composes──> Transactions (existing)
                                            └──composes──> Cursor (next cursor, nullable)

CursorPaginator (existing)
    └──informs──> DayTurnoverPage last-page logic (NbRecords < RequestCount pattern)

GetDayTurnover request (existing)
    └──reused by──> getDayTurnoverPage (sends same request, no new Request class needed)

CouldNotGetDayTurnover (existing)
    └──reused by──> getDayTurnoverPage (no new exception needed)
```

### Dependency Notes

- **`getDayTurnoverPage` requires `Cursor`:** The method signature accepts `?Cursor` — Cursor must exist first.
- **`DayTurnoverPage` requires `Transactions` and `Cursor`:** Both must be defined before `DayTurnoverPage` can compose them.
- **`DayTurnoverPage` requires no new request class:** `GetDayTurnover` is reused; only the method on `DeRidderDenHertog` needs updating to set `LastRecord` from the cursor and `RequestCount` from `PerPage` directly (without going through `CursorPaginator`).
- **`Cursor` is independent:** It has no dependencies on other new features and can be built first.

---

## MVP Definition

### Launch With (v1 — this milestone)

- [x] `Cursor` value object — gating dependency for everything else
- [x] `DayTurnoverPage` DTO with `Transactions $transactions` and `?Cursor $nextCursor`
- [x] `getDayTurnoverPage(?Cursor $cursor, PerPage $perPage, ...)` method on `DeRidderDenHertog`

### Add After Validation (v1.x)

- [ ] Single-page fetch for other endpoints — only if RENH API adds cursor pagination to GetCustomers or others

### Future Consideration (v2+)

- [ ] Generic `Page<T>` DTO — if more endpoints gain pagination, extract the pattern; premature now with only one paginated endpoint

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| `Cursor` value object | HIGH (type-safety, consistency) | LOW (trivial wrap of int) | P1 |
| `DayTurnoverPage` DTO | HIGH (single return type with all needed data) | LOW (two properties, no logic) | P1 |
| `getDayTurnoverPage` method | HIGH (the actual capability consumers need) | LOW (reuses existing request + mapper) | P1 |

All three are P1. There are no P2 or P3 items for this milestone — the scope is deliberately narrow.

---

## Implementation Notes (for Roadmap)

These observations come directly from reading the existing codebase:

**`Cursor` construction:** The RENH API sends `Lastrecord` (note lowercase 'r') as an integer in the response `raw` array (`$result->raw['Lastrecord']`). The `CursorPaginator` reads this as the next cursor. The `Cursor` value object wraps this integer. Factory: `Cursor::fromInteger(int $value)`. Must decide whether `0` is valid (start-of-dataset sentinel) or invalid — the existing paginator never constructs a cursor for page 1 (it just omits `LastRecord`), so cursor `0` likely never appears as a meaningful value. Safest: validate `>= 0`, treat `null` passed to the method as "first page" (omit `LastRecord`).

**`DayTurnoverPage` last-page detection:** Reuse `NbRecords < RequestCount` (same logic as `CursorPaginator::isLastPage`). When true, `nextCursor` is `null`. When false, `nextCursor` is `Cursor::fromInteger($result->raw['Lastrecord'])`.

**No new Request class:** `GetDayTurnover` implements `Paginatable` but can still be sent directly via `$this->client->send()`. The new method sets `RequestCount` and `LastRecord` on the request body before sending — this is already what the `CursorPaginator` does via `applyPagination`. The simplest approach is to set these values in the `GetDayTurnover` constructor or add them in `getDayTurnoverPage` after construction.

**Method placement:** `getDayTurnoverPage` goes on `DeRidderDenHertog` alongside `getDayTurnover` and `getDayTurnoverPaginated`, consistent with the existing pattern where all public capabilities live on the main facade class.

---

## Sources

- Codebase analysis: `/src/Core/Http/Pagination/CursorPaginator.php` — confirmed cursor field names (`LastRecord`, `Lastrecord`, `NbRecords`)
- Codebase analysis: `/src/Core/Http/Result.php` — confirmed `raw` array structure
- Codebase analysis: `/src/Core/Type/Parameter/PerPage.php`, `/src/Core/Type/Primitive/CustomerId.php`, `/src/Authentication/ApiGuid.php` — confirmed value object pattern (final readonly, private constructor, named static factory)
- Codebase analysis: `/src/DeRidderDenHertog.php` — confirmed facade method signatures and `send`/`paginate` helper pattern
- PROJECT.md: confirmed out-of-scope items and API field semantics (`LastRecord` in request, `Lastrecord` + `NbRecords` in response)

---

*Feature research for: PHP SDK single-page cursor-based pagination fetch*
*Researched: 2026-03-30*
