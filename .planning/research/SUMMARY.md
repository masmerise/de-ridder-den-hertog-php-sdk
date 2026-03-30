# Project Research Summary

**Project:** De Ridder & Den Hertog PHP SDK — Single-Page Cursor-Based Day Turnover Fetch
**Domain:** PHP SDK extension — cursor-based pagination primitive
**Researched:** 2026-03-30
**Confidence:** HIGH

## Executive Summary

This milestone adds a single-page cursor-based pagination fetch capability to an existing, well-structured PHP SDK. The SDK already has full automatic cursor iteration via `CursorPaginator` and `getDayTurnoverPaginated`. The new capability is a lower-level primitive: `getDayTurnoverPage` gives callers one page of `Transactions` plus the cursor to the next page, enabling callers to drive pagination themselves. All required patterns (value object, DTO, facade method) are already established in the codebase — this milestone is purely additive with no new dependencies and no modifications to existing request or pagination classes.

The recommended approach is a strict three-component build in dependency order: `Cursor` value object first (standalone, no dependencies), `DayTurnoverPage` DTO second (depends on `Cursor` and the existing `Transactions`), and finally the `getDayTurnoverPage` facade method (integrates both new components with existing infrastructure). All three components follow well-established in-codebase patterns, making implementation mechanical rather than exploratory. The RENH API's wire-protocol quirks — asymmetric field casing (`LastRecord` in request, `Lastrecord` in response) and record-count-based last-page detection — are already fully documented by the existing `CursorPaginator` source, which must be treated as the authoritative reference throughout implementation.

The primary risk is subtle protocol mistakes in the response-parsing phase: reading the wrong field name for the cursor (`LastRecord` vs `Lastrecord`) or using the wrong sentinel for last-page detection (raw cursor value vs. `NbRecords < RequestCount`). Both are mitigated by treating `CursorPaginator::getNextCursor()` and `CursorPaginator::isLastPage()` as templates. A secondary risk is structural: placing new files in the wrong namespace, or mutating the existing `GetDayTurnover` request class, which would break `getDayTurnoverPaginated`. Neither pitfall requires exotic recovery — both are avoided by scaffolding files correctly before writing logic and by not touching `GetDayTurnover.php` at all.

---

## Key Findings

### Recommended Stack

No new dependencies are required. The existing stack — PHP ~8.5, saloonphp/saloon v4.0.0, saloonphp/pagination-plugin v2.3.0, webmozart/assert 2.1.6, PHPUnit 12.5.14, PHPStan 2.1.44, Laravel Pint v1.29.0 — is current and sufficient. All versions have been verified as the latest stable releases. The three new components are pure PHP classes with no external surface beyond the already-imported packages.

**Core technologies:**
- PHP ~8.5: Runtime — pipe operator already in use; no version change required
- saloonphp/saloon v4.0.0: HTTP client and request lifecycle — `send()` and `body()->add()` used directly
- saloonphp/pagination-plugin v2.3.0: Referenced only by the existing `CursorPaginator`; new method uses `send()` not `paginate()`
- webmozart/assert 2.1.6: Validated value object construction — used in `Cursor` constructor

### Expected Features

The milestone scope is narrow and fully defined. All three items are P1 with no lower-priority items in scope.

**Must have (table stakes):**
- `Cursor` value object — SDK consumers expect a typed wrapper for every parameter; a raw `?int` would be inconsistent with every other SDK parameter type
- `DayTurnoverPage` DTO — callers fetching a single page need both `Transactions` and the next cursor; returning bare `Transactions` forces callers to re-parse raw response internals
- `getDayTurnoverPage` on `DeRidderDenHertog` — the entry point that completes the trio alongside `getDayTurnover` and `getDayTurnoverPaginated`

**Should have (ergonomics):**
- `null` cursor parameter on `getDayTurnoverPage` signals first page — cleaner than a magic `Cursor::fromInteger(0)` that would force consumers to know the sentinel value
- `null` nextCursor on `DayTurnoverPage` signals end-of-results — simple null-check instead of requiring callers to re-implement the `NbRecords < RequestCount` comparison

**Defer (v2+):**
- Single-page fetch for other endpoints (e.g. GetCustomers) — RENH API cursor pagination is only documented for GetDayTurnover; defer until API confirms support elsewhere
- Generic `Page<T>` DTO — premature with only one paginated endpoint; extract the pattern only if more endpoints gain pagination

### Architecture Approach

The implementation follows a strict additive pattern: two new files, one modified file, and no changes anywhere else. `Cursor` lives in `src/GetDayTurnover/Type/Parameter/` (alongside `PerPage`, `Date`, `Filter`), `DayTurnoverPage` lives in `src/GetDayTurnover/Type/` (alongside `Transactions`, `Transaction`), and `getDayTurnoverPage` is added to `src/DeRidderDenHertog.php`. The new facade method reproduces the `getDayTurnover` send pattern exactly — constructing a `GetDayTurnover` request, injecting `RequestCount` and optionally `LastRecord` via `body()->add()`, calling `$this->send()`, and mapping the `Result` to the new DTO. The data flow is structurally identical to the existing paginator's single-step logic, just without the iteration wrapper.

**Major components:**
1. `Cursor` — value object; wraps non-negative `int`, exposes `fromInteger(int): self` and `toInteger(): int`; lives in `GetDayTurnover\Type\Parameter`
2. `DayTurnoverPage` — result DTO; `final readonly`, holds `Transactions $transactions`, `?Cursor $nextCursor`, and `bool $hasMore`; lives in `GetDayTurnover\Type`
3. `getDayTurnoverPage` — facade method on `DeRidderDenHertog`; signature `(PerPage, ?Cursor, ?Filter, ?Date, ?Date): DayTurnoverPage`; uses existing `protected send()` helper

### Critical Pitfalls

1. **`Lastrecord` vs `LastRecord` field name asymmetry** — The RENH API uses `LastRecord` (capital R) in the request body and `Lastrecord` (lowercase r) in the response. PHP silently returns `null` for missing array keys. Use exactly `$result->raw['Lastrecord']` — confirmed in `CursorPaginator::getNextCursor()` — never `$result->raw['LastRecord']`.

2. **Wrong last-page sentinel** — Do not treat the cursor value itself (e.g. `Lastrecord === 0`) as an end-of-data signal. The correct sentinel is `NbRecords < RequestCount`, exactly as `CursorPaginator::isLastPage()` computes it. Always derive `nextCursor = null` from the record count comparison, never from the cursor integer.

3. **Mutating `GetDayTurnover` request class** — Adding optional `?Cursor` / `?PerPage` parameters to `GetDayTurnover::__construct()` or `message()` would break `getDayTurnoverPaginated`, which relies on the paginator injecting those fields. The existing request class must not be modified. Inject `RequestCount` and `LastRecord` via `$request->body()->add()` in the facade method, or create a separate `GetDayTurnoverPage` request class.

4. **Cursor nullable contract confusion** — `Cursor` must validate `>= 0` (not `> 0`) but `null` is the canonical first-page signal. Do not add a `Cursor::none()` factory. The `getDayTurnoverPage` method signature is `?Cursor $cursor = null`; `null` causes `LastRecord` to be omitted from the request body entirely.

5. **Bypassing `protected send()`** — Calling `$this->client->send()` directly skips auth-failure detection, `UnknownException` wrapping, and `Result` extraction. `getDayTurnoverPage` must call `$this->send(request: ..., onFailure: CouldNotGetDayTurnover::class)` and declare the same `@throws` set as `getDayTurnover`.

---

## Implications for Roadmap

Based on research, the build has a clear three-phase dependency order with tests as a fourth closing phase. All phases are well-understood — no phase requires exploratory research during planning.

### Phase 1: Cursor Value Object

**Rationale:** `Cursor` is the gating dependency for both `DayTurnoverPage` and `getDayTurnoverPage`. It has no new dependencies of its own and can be built and unit-tested in complete isolation. Doing this first lets subsequent phases import a stable, tested component.

**Delivers:** `src/GetDayTurnover/Type/Parameter/Cursor.php` — a validated, typed wrapper for the `LastRecord` cursor integer, with `fromInteger(int): self` and `toInteger(): int`.

**Addresses:** Table stakes feature — typed cursor parameter consistent with every other SDK parameter type.

**Avoids:** Cursor nullable inconsistency pitfall (constructor validates `>= 0`; null is a method-parameter concept, not encoded in the value object).

### Phase 2: DayTurnoverPage DTO

**Rationale:** Depends only on `Cursor` (Phase 1) and the existing `Transactions` class. Has no side effects on existing code. Building it before the facade method allows the return type to be fully specified before the method is written.

**Delivers:** `src/GetDayTurnover/Type/DayTurnoverPage.php` — `final readonly` DTO with `Transactions $transactions`, `?Cursor $nextCursor`, and `bool $hasMore`; named factory `DayTurnoverPage::from(Transactions, ?Cursor, bool): self`.

**Addresses:** Table stakes feature — single return type bundling all page data callers need.

**Avoids:** Wrong-namespace placement pitfall (file goes in `GetDayTurnover\Type`, not `Core`); last-page detection pitfall (DTO is constructed with the already-computed nullable cursor, keeping the comparison logic in the facade).

### Phase 3: getDayTurnoverPage Facade Method

**Rationale:** Integration point for all new and existing components. Should be the last component written because it depends on `Cursor` (Phase 1), `DayTurnoverPage` (Phase 2), `GetDayTurnover` (existing), `Result` (existing), and `send()` (existing). Building it last means all dependencies are stable.

**Delivers:** New method on `src/DeRidderDenHertog.php` — `getDayTurnoverPage(PerPage, ?Cursor, ?Filter, ?Date, ?Date): DayTurnoverPage` with full `@throws` docblock.

**Uses:** saloonphp/saloon `body()->add()` for `RequestCount` / `LastRecord` injection (same pattern as `CursorPaginator::applyPagination`); existing `protected send()` helper.

**Implements:** Facade method architecture component.

**Avoids:** `send()` bypass pitfall; request mutation pitfall (`GetDayTurnover.php` left unchanged); `Lastrecord` casing pitfall (cross-checked against `CursorPaginator::getNextCursor` before writing response extraction).

### Phase 4: Tests

**Rationale:** Written last, against the completed public interface. Integration tests require all three components to be present. Unit tests for `Cursor` and `DayTurnoverPage` can be written incrementally but are most efficiently grouped here.

**Delivers:** Unit tests for `Cursor` and `DayTurnoverPage`; integration tests for `getDayTurnoverPage` covering first-page (null cursor), mid-pagination, and last-page (null nextCursor) cases; confirmation that `getDayTurnoverPaginated` tests remain green.

**Avoids:** "Looks done but isn't" failures — in particular, the integration test that fetches with `PerPage` larger than the dataset and asserts `nextCursor === null`.

### Phase Ordering Rationale

- Dependency order is strict: `Cursor` → `DayTurnoverPage` → facade method → tests. No phase can be parallelized without forward references to unbuilt types.
- Grouping mirrors the existing codebase's layering: value objects and DTOs are built before the facade that uses them.
- Keeping `GetDayTurnover.php` untouched across all phases eliminates the risk of breaking the existing paginator — enforced by checking its diff is empty before marking any phase done.

### Research Flags

Phases with standard patterns (skip research-phase — patterns fully documented by existing codebase):
- **Phase 1 (Cursor):** Direct pattern match with `PerPage` and `CustomerId`; no unknowns.
- **Phase 2 (DayTurnoverPage):** Pure data class following existing `final readonly` DTO pattern; no unknowns.
- **Phase 3 (getDayTurnoverPage):** Template is `getDayTurnover`; wire-protocol details confirmed by `CursorPaginator`; no unknowns.
- **Phase 4 (Tests):** Existing `tests/DeRidderDenHertogTest.php` provides the integration test structure to follow.

No phase requires `/gsd:research-phase` — all implementation decisions are resolved by reading existing source files.

---

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | All versions verified on Packagist; no new dependencies; existing stack confirmed current |
| Features | HIGH | Scope derived from PROJECT.md and direct codebase analysis; no ambiguity about what ships in this milestone |
| Architecture | HIGH | All patterns verified against existing source files; component boundaries, namespaces, and method signatures confirmed |
| Pitfalls | HIGH | All pitfalls derived from reading actual implementation in `CursorPaginator.php`, `GetDayTurnover.php`, and `DeRidderDenHertog.php` |

**Overall confidence:** HIGH

### Gaps to Address

- **`Cursor` lower bound (`>= 0` vs `> 0`):** PITFALLS.md recommends `>= 0` (cursor 0 is a valid API sentinel for "start from beginning") while ARCHITECTURE.md agrees. FEATURES.md raises the question but defers to codebase inspection. The resolution is `>= 0` — validate during implementation by confirming what value `CursorPaginator` would use on its very first request; if it omits `LastRecord` entirely (null cursor path), then cursor `0` would never appear as a meaningful return value, but `>= 0` is still the safe bound.

- **`hasMore` vs nullable `nextCursor` as the API surface:** ARCHITECTURE.md adds a `bool $hasMore` field to `DayTurnoverPage` in addition to `?Cursor $nextCursor`. FEATURES.md and PITFALLS.md treat `null` nextCursor as the sole last-page signal. These are not contradictory — `hasMore` can be derived from whether `nextCursor === null` — but the exact DTO fields should be decided definitively before Phase 2 begins to avoid a breaking change to the public API surface post-release.

---

## Sources

### Primary (HIGH confidence)

- Codebase: `src/Core/Http/Pagination/CursorPaginator.php` — authoritative source for `Lastrecord` casing, `NbRecords` last-page logic, `body()->add()` injection pattern
- Codebase: `src/DeRidderDenHertog.php` — `send()` / `paginate()` / `getResult()` pattern; existing method signatures
- Codebase: `src/Core/Type/Parameter/PerPage.php`, `src/Core/Type/Primitive/CustomerId.php`, `src/Authentication/ApiGuid.php` — value object pattern templates
- Codebase: `src/GetDayTurnover/Request/GetDayTurnover.php` — confirms request does not include cursor/page-size fields
- Codebase: `src/Core/Http/Result.php` — confirms `raw` and `records` array structure
- https://packagist.org/packages/saloonphp/saloon — v4.0.0 confirmed latest stable
- https://packagist.org/packages/saloonphp/pagination-plugin — v2.3.0 confirmed latest stable
- https://packagist.org/packages/webmozart/assert — v2.1.6 confirmed latest stable

### Secondary (MEDIUM confidence)

- PROJECT.md — confirmed out-of-scope items, API field semantics, and milestone boundaries

---

*Research completed: 2026-03-30*
*Ready for roadmap: yes*
