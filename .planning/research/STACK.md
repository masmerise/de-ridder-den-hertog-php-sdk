# Stack Research

**Domain:** PHP SDK — cursor-based single-page day turnover fetch
**Researched:** 2026-03-30
**Confidence:** HIGH

## Summary

No new library dependencies are required for this milestone. The three new constructs (`Cursor` value object, `DayTurnoverPage` DTO, and `getDayTurnoverPage` method) are pure PHP implementations following patterns already present in the codebase. All existing locked versions are current as of the research date.

---

## Current Stack (unchanged)

These are locked and verified current. No version changes needed.

### Core Technologies

| Technology | Locked Version | Purpose | Status |
|------------|---------------|---------|--------|
| PHP | ~8.5 | Runtime | Current — pipe operator (`\|>`) already in use |
| saloonphp/saloon | v4.0.0 | HTTP client / request lifecycle | Current (latest stable) |
| saloonphp/pagination-plugin | v2.3.0 | `Paginator` base for `CursorPaginator` | Current (latest stable) |
| webmozart/assert | 2.1.6 | Validated value object construction | Current (latest stable) |

### Development Tools

| Tool | Locked Version | Purpose | Status |
|------|---------------|---------|--------|
| phpunit/phpunit | 12.5.14 | Unit and integration tests | Current |
| phpstan/phpstan | 2.1.44 | Static analysis | Current |
| laravel/pint | v1.29.0 | Code style enforcement | Current |

---

## New Additions Required

**None.** The milestone requires only new PHP classes following existing patterns.

| New Class | Type | Pattern to Follow | Location |
|-----------|------|-------------------|----------|
| `Cursor` | Value object | `PerPage` / `CustomerId` — `final readonly`, private constructor, named static factory, `toMessageString()` or `toInteger()` | `src/GetDayTurnover/Type/Parameter/` |
| `DayTurnoverPage` | DTO | `final readonly`, public constructor, holds `Transactions` + nullable next cursor | `src/GetDayTurnover/Type/` |
| `getDayTurnoverPage` | Method on `DeRidderDenHertog` | `getDayTurnover` send pattern but returns `DayTurnoverPage` instead of `Transactions` | `src/DeRidderDenHertog.php` |

---

## Integration Points

### `Cursor` value object

Wraps the integer `LastRecord` cursor value the API uses for pagination. Mirrors `PerPage`:

- `Assert::greaterThanEq($value, 0)` — cursor 0 means "from beginning"
- Named factory: `Cursor::at(int $position): self`
- Serialisation: `toInteger(): int` for use in request body (`'LastRecord' => $cursor->toInteger()`)

### `DayTurnoverPage` DTO

Bundles the page result:

- `public readonly Transactions $transactions` — the mapped page contents
- `public readonly ?Cursor $nextCursor` — `null` when `isLastPage` is true (i.e., `NbRecords < RequestCount`)
- Named factory: `DayTurnoverPage::of(Transactions $transactions, ?Cursor $nextCursor): self`
- The `nextCursor` value comes from `Result::$raw['Lastrecord']` (integer), same field `CursorPaginator::getNextCursor()` already reads

### `getDayTurnoverPage` method

Calls `send()` (existing protected method) with a modified `GetDayTurnover` request that sets `LastRecord` and `RequestCount` before dispatch. Does not modify `GetDayTurnover` request class — injects via `body()->add()` after construction, the same way `CursorPaginator::applyPagination()` does. Returns `DayTurnoverPage`.

The last-page determination reuses the `NbRecords < RequestCount` logic already proven in `CursorPaginator::isLastPage()`.

---

## What NOT to Add

| Avoid | Why | Correct Approach |
|-------|-----|-----------------|
| New Composer dependency | No capability gap — webmozart/assert already covers validation, Saloon already covers HTTP | Use existing libraries |
| Modifying `GetDayTurnover` request class | Would couple single-page and full-iteration concerns; `CursorPaginator` injects `LastRecord` externally | Inject cursor via `body()->add()` in the facade method, same as paginator does |
| Saloon `PagedResponse` or plugin types | Pagination plugin is for automatic multi-page iteration; single-page fetch is a plain `send()` call | Use existing `send()` + manual `Result` inspection |
| Nullable `Cursor` default parameter making cursor optional on the DTO | `DayTurnoverPage::$nextCursor` should be `?Cursor` (null = last page), not absent — callers need to distinguish "more pages exist" from "this was the last page" | Keep it nullable, not omitted |

---

## Version Compatibility

| Package | Compatible With | Verified |
|---------|-----------------|---------|
| saloonphp/saloon v4.0.0 | saloonphp/pagination-plugin v2.3.0 | Yes — plugin requires `^3.0 \|\| ^4.0` |
| webmozart/assert 2.1.6 | PHP ~8.5 | Yes — assert requires ^8.2 |
| phpunit/phpunit 12.5.14 | PHP ~8.5 | Yes |

---

## Sources

- https://packagist.org/packages/saloonphp/saloon — v4.0.0 confirmed latest stable
- https://packagist.org/packages/saloonphp/pagination-plugin — v2.3.0 confirmed latest stable, compatibility `^3.0 || ^4.0`
- https://packagist.org/packages/webmozart/assert — v2.1.6 confirmed latest stable
- Codebase inspection: `src/Core/Http/Pagination/CursorPaginator.php`, `src/Core/Type/Parameter/PerPage.php`, `src/Core/Type/Primitive/CustomerId.php`, `src/DeRidderDenHertog.php` — patterns verified directly

---

*Stack research for: PHP SDK single-page cursor-based day turnover fetch*
*Researched: 2026-03-30*
