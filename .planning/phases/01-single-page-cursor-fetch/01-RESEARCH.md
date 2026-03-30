# Phase 1: Single-Page Cursor Fetch - Research

**Researched:** 2026-03-30
**Domain:** PHP SDK value objects, cursor-based pagination, Saloon HTTP client
**Confidence:** HIGH

## Summary

This phase adds a single-page cursor-fetch capability to the existing SDK facade. The codebase is small, well-structured, and internally consistent — all patterns needed for the new types already exist in the repository. There are no external research unknowns: the library conventions, API wire protocol, and validation patterns are fully visible in source code.

The key design insight from the existing `CursorPaginator` is that last-page detection uses `NbRecords < perPageLimit` (i.e. the API returned fewer records than were requested). The new `Cursor` value object must encode this same determination so callers can ask `$cursor->hasMore()` without re-computing it. The `Lastrecord` field (lowercase r) in the API response carries the integer cursor for the next page, and `LastRecord` (uppercase R) in the request body carries the cursor for the current page.

All three deliverables — `Cursor`, `DayTurnoverPage`, and `getDayTurnoverPage` — follow patterns that already exist verbatim in the codebase. This is a low-risk, medium-effort phase.

**Primary recommendation:** Follow `PerPage` as the value object template, follow `getDayTurnover` as the facade method template, and follow `CursorPaginator::isLastPage` for the `hasMore()` determination logic.

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **D-01:** `Cursor` accepts `>= 0`. Two static factories: `Cursor::start()` (backs to 0, represents "first page") and `Cursor::fromInteger(int)` (for positive values from API response).
- **D-02:** `Cursor` carries "end of data" state — exposes `hasMore()` or `isLastPage()` method so callers can check pagination status directly on the cursor.
- **D-03:** `Cursor` lives in `GetDayTurnover/Type/Parameter/` (action-specific, not shared Core).
- **D-04:** `DayTurnoverPage` always contains a non-nullable `Cursor` (not `?Cursor`). The cursor itself knows whether more pages exist via its `hasMore()`/`isLastPage()` method.
- **D-05:** `DayTurnoverPage` bundles `Transactions` collection and `Cursor` — follows `final readonly` pattern with private constructor and static factory.
- **D-06:** `getDayTurnoverPage` accepts the same optional parameters as `getDayTurnover` — `Filter`, `Date` from/to — alongside `PerPage` and `Cursor`. Consistent API surface.
- **D-07:** Method uses existing `send()` helper and `CouldNotGetDayTurnover` exception — no new exception class needed.

### Claude's Discretion

- Whether `Cursor` exposes the method as `hasMore()` or `isLastPage()` — pick the name that reads best
- Internal construction of `Cursor` from API response (how to determine last-page state from `NbRecords` vs `RequestCount`)
- Whether `DayTurnoverPage` needs a static factory name like `::of()` or `::from()`

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| PAG-01 | SDK provides a `Cursor` value object that wraps an integer for type-safe cursor parameter handling | `PerPage` pattern directly cloneable; `Assert::greaterThanEq($value, 0)` for >= 0 validation; `hasMore()` powered by `NbRecords < perPageLimit` logic from `CursorPaginator` |
| PAG-02 | SDK provides a `DayTurnoverPage` DTO that bundles a `Transactions` collection with a next `Cursor` | `Transactions` collection already exists; `final readonly` with private constructor and static factory is the established DTO pattern |
| PAG-03 | SDK provides a `getDayTurnoverPage` method that fetches a single page of day turnover given a `PerPage` and optional `Cursor`, returning a `DayTurnoverPage` | `send()` helper + `GetDayTurnover` request + `body()->add()` for cursor/page injection is the established facade pattern |
</phase_requirements>

---

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| webmozart/assert | ^2.0 | Value object validation in constructors | Already used everywhere in SDK; `Assert::greaterThanEq` covers >= 0 check |
| saloonphp/saloon | ^4.0 | HTTP client; `send()` and `body()->add()` for request construction | SDK is built on Saloon; all request/response handling goes through it |
| phpunit/phpunit | ^12.5.14 | Integration test runner | Already configured; all tests live in `tests/` |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| laravel/pint | ^1.29.0 | Code style formatting | Run after writing new files (`composer format`) |
| phpstan/phpstan | ^2.1.44 | Static analysis at level 5 | Run to verify type safety of new classes (`composer stan`) |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `Assert::greaterThanEq($v, 0)` | Manual `if` + `throw` | Assert is already the project standard; use it |
| Private constructor + static factory | Public constructor | Project uses private constructor + named factories everywhere; match the pattern |

**Installation:** No new dependencies required. All libraries are already installed.

---

## Architecture Patterns

### Recommended Project Structure

New files to create:

```
src/GetDayTurnover/Type/
├── Parameter/
│   └── Cursor.php           # New value object (PAG-01)
└── DayTurnoverPage.php      # New DTO (PAG-02)
```

Modified files:

```
src/DeRidderDenHertog.php    # Add getDayTurnoverPage() method (PAG-03)
```

### Pattern 1: Value Object (Cursor)

**What:** Immutable integer wrapper with validation, named static factories, and a boolean state accessor.
**When to use:** Any typed parameter or return primitive in the SDK.

Modeled directly on `src/Core/Type/Parameter/PerPage.php`:

```php
// Source: src/Core/Type/Parameter/PerPage.php (verified)
final readonly class PerPage
{
    public function __construct(private int $count)
    {
        Assert::greaterThan($count, 0, 'The per page count must be greater than 0.');
    }

    public static function count(int $count): self
    {
        return new self($count);
    }

    public function toInteger(): int
    {
        return $this->count;
    }
}
```

`Cursor` adapts this by:
- Using `Assert::greaterThanEq($value, 0, ...)` instead (allows 0 for first page)
- Adding a second constructor parameter `bool $hasMore` (see last-page detection below)
- Adding `Cursor::start()` factory (returns cursor at 0, `$hasMore = true` as default since no data fetched yet)
- Adding `Cursor::fromInteger(int)` factory (used when constructing from API response)
- Adding `hasMore(): bool` accessor

**Discretion recommendation for method name:** Use `hasMore()` — it reads naturally as a positive check ("does the data have more pages?") and matches the `CursorPaginator::isLastPage` inversion without confusion.

### Pattern 2: Last-Page Detection

**What:** Determine whether the cursor carries more pages by comparing `NbRecords` to the `PerPage` limit.
**Source:** `src/Core/Http/Pagination/CursorPaginator.php` (verified)

```php
// Source: src/Core/Http/Pagination/CursorPaginator.php (verified)
protected function isLastPage(Response $response): bool
{
    /** @var Result $result */
    $result = $response->dto();

    $nbRecords = $result->raw['NbRecords'] ?? 0;

    return $nbRecords < $this->perPageLimit;
}
```

The inverse (`$hasMore = $nbRecords >= $perPage`) is what `getDayTurnoverPage` passes to `Cursor::fromResponse()` or equivalent internal factory.

**Note on API field naming:** The wire protocol has an intentional asymmetry:
- **Request body:** `LastRecord` (capital R) — cursor sent to API
- **Response raw:** `Lastrecord` (lowercase r) — cursor received from API
- **Response raw:** `NbRecords` — record count for last-page detection

### Pattern 3: Facade Method (getDayTurnoverPage)

**What:** Send a single request with cursor/page parameters injected into the body, map the result to a DTO.
**When to use:** Any single-page, single-shot API call.

Modeled on `getDayTurnover` in `src/DeRidderDenHertog.php` (verified):

```php
// Source: src/DeRidderDenHertog.php (verified)
public function getDayTurnover(?Filter $filter = null, ?Date $from = null, ?Date $till = null): Transactions
{
    $result = $this->send(
        request: new GetDayTurnover($filter, $from, $till)->setGuid($this->guid),
        onFailure: CouldNotGetDayTurnover::class,
    );

    return Transactions::of(
        array_map(new TransactionMapper(), $result->records['Kassabonnen'] ?? [])
    );
}
```

`getDayTurnoverPage` adapts this by:
- Accepting `PerPage $perPage` and `?Cursor $cursor = null` as leading parameters
- Injecting `RequestCount` and (when cursor is non-null) `LastRecord` via `$request->body()->add()` after construction — same approach as `CursorPaginator::applyPagination`
- Reading `Lastrecord` and `NbRecords` from `$result->raw` to construct the `Cursor` for the returned `DayTurnoverPage`

Body injection pattern (verified from `CursorPaginator`):

```php
// Source: src/Core/Http/Pagination/CursorPaginator.php (verified)
$request->body()->add('RequestCount', $this->perPageLimit);

if ($this->currentResponse instanceof Response) {
    $request->body()->add('LastRecord', $this->getNextCursor($this->currentResponse));
}
```

### Pattern 4: DTO (DayTurnoverPage)

**What:** `final readonly` class with private constructor and named static factory bundling `Transactions` and `Cursor`.
**Discretion recommendation for factory name:** Use `::of()` — matches `Transactions::of()`, `ApiFunctions::of()`, and `Customers::of()` already in the codebase.

```php
// Inferred from established pattern (src/GetApiFunctions/Type/ApiFunctions.php family)
final readonly class DayTurnoverPage
{
    private function __construct(
        public Transactions $transactions,
        public Cursor $cursor,
    ) {}

    public static function of(Transactions $transactions, Cursor $cursor): self
    {
        return new self($transactions, $cursor);
    }
}
```

### Anti-Patterns to Avoid

- **Making `DayTurnoverPage::cursor` nullable:** The cursor is never null — last-page state is on the cursor object itself. A null here would break the design and add null-checks at call sites.
- **Using `Cursor::start()` as the null-cursor substitute:** `Cursor::start()` semantically means "fetch from the beginning". The `?Cursor $cursor` in `getDayTurnoverPage` distinguishes "no cursor given" (first page) from "cursor given". Internally, when `$cursor` is null, simply omit the `LastRecord` body parameter.
- **Hardcoding `NbRecords` key without default:** The `CursorPaginator` defensively uses `$result->raw['NbRecords'] ?? 0`. Match this — the key may be absent on empty results.
- **Modifying `getDayTurnoverPaginated`:** Explicitly out of scope. Both methods call `GetDayTurnover` independently.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Validation in value object | Manual `if ($v < 0) throw` | `Assert::greaterThanEq($v, 0, ...)` | Already the project standard; consistent exception type |
| HTTP error handling | Try/catch in new method | `$this->send()` helper | Handles `UnknownException`, auth check, and DTO extraction |
| Page cursor extraction | Custom response parsing | Read `$result->raw['Lastrecord']` directly | Same field already used in `CursorPaginator::getNextCursor` |

**Key insight:** Every individual piece already exists. This phase is assembly, not invention.

---

## Common Pitfalls

### Pitfall 1: Asymmetric casing on `LastRecord` / `Lastrecord`

**What goes wrong:** Sending `Lastrecord` (lowercase r) in the request body, or reading `LastRecord` (uppercase R) from the response — both silently fail (wrong cursor or null).
**Why it happens:** The API uses different casing for the same conceptual field in request vs response.
**How to avoid:** Request body key is `LastRecord` (capital R). Response raw key is `Lastrecord` (lowercase r). Follow `CursorPaginator` verbatim.
**Warning signs:** `$result->raw['Lastrecord']` returns `null`; cursor always resets to page 1.

### Pitfall 2: Forgetting `RequestCount` in the body

**What goes wrong:** API ignores `PerPage` and returns its default page size, making `NbRecords` comparison unreliable.
**Why it happens:** `PerPage` is not a `GetDayTurnover` constructor parameter — it must be injected via `body()->add('RequestCount', ...)` after construction.
**How to avoid:** Always inject `RequestCount` before calling `send()`.
**Warning signs:** Always getting the same number of records regardless of `PerPage` value.

### Pitfall 3: `Assert::greaterThan` vs `Assert::greaterThanEq` for Cursor

**What goes wrong:** Using `Assert::greaterThan($value, 0)` (strict greater than) would reject `0`, breaking `Cursor::start()` which backs `0`.
**Why it happens:** `PerPage` uses `greaterThan` (count must be at least 1), but Cursor allows 0 (start position).
**How to avoid:** Use `Assert::greaterThanEq($value, 0, ...)` for Cursor validation.
**Warning signs:** `Cursor::start()` throws a validation exception.

### Pitfall 4: `Cursor::start()` hasMore state

**What goes wrong:** `Cursor::start()` constructed with `$hasMore = false` would make callers think there is no data before even making a request.
**Why it happens:** Confusing "no more pages after this" with "not yet known".
**How to avoid:** `Cursor::start()` should set `$hasMore = true` (assume there is data until proven otherwise). The returned cursor from `getDayTurnoverPage` carries the accurate state.
**Warning signs:** `Cursor::start()->hasMore()` returns `false`, causing premature loop termination.

---

## Code Examples

### Cursor construction from API response

```php
// Source: derived from CursorPaginator (src/Core/Http/Pagination/CursorPaginator.php, verified)

// After calling send():
$nextCursorValue = $result->raw['Lastrecord'];        // lowercase r in response
$nbRecords       = $result->raw['NbRecords'] ?? 0;
$hasMore         = $nbRecords >= $perPage->toInteger();

$cursor = Cursor::fromInteger($nextCursorValue, $hasMore);
```

### Request body injection for pagination

```php
// Source: derived from CursorPaginator::applyPagination (verified)
$request = new GetDayTurnover($filter, $from, $till)->setGuid($this->guid);
$request->body()->add('RequestCount', $perPage->toInteger());

if ($cursor !== null) {
    $request->body()->add('LastRecord', $cursor->toInteger()); // capital R in request
}

$result = $this->send(request: $request, onFailure: CouldNotGetDayTurnover::class);
```

### Static factory pattern with state

```php
// Source: pattern extrapolated from PerPage + CustomerId (both verified)

final readonly class Cursor
{
    private function __construct(
        private int $position,
        private bool $hasMore,
    ) {
        Assert::greaterThanEq($position, 0, 'The cursor position must be >= 0.');
    }

    public static function start(): self
    {
        return new self(0, true);
    }

    public static function fromInteger(int $position, bool $hasMore): self
    {
        return new self($position, $hasMore);
    }

    public function toInteger(): int
    {
        return $this->position;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }
}
```

---

## Environment Availability

Step 2.6: SKIPPED — this phase is purely code changes with no external service dependencies beyond the already-running RENH API (which existing tests already reach).

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Full-iteration via `getDayTurnoverPaginated` | Single-page via `getDayTurnoverPage` (new) | Phase 1 | Consumers can now paginate manually, store cursors, and resume |

**Nothing deprecated in this phase.** Existing `getDayTurnoverPaginated` is untouched.

---

## Open Questions

1. **`Cursor::fromInteger` — should the caller always supply `$hasMore` explicitly?**
   - What we know: The hasMore state is only computable inside `getDayTurnoverPage` after the response arrives (requires `NbRecords` and `PerPage`). External callers constructing a cursor from a stored integer don't know `hasMore`.
   - What's unclear: If a consumer serializes a cursor integer and reconstructs it later, they'll pass `fromInteger($n, ???)`. There's no way to know `hasMore` without making a request.
   - Recommendation: Make `$hasMore` a required second parameter of `fromInteger`. Document that `hasMore()` is only meaningful on cursors returned from `getDayTurnoverPage`, not on manually constructed ones. Consumers who reconstruct a cursor can pass `true` as a safe default (it just means "try fetching — the API will return empty if there's nothing left").

2. **`DayTurnoverPage` static factory name**
   - What we know: The codebase uses `::of()` on `Transactions`, `Customers`, and `ApiFunctions`.
   - Recommendation: Use `DayTurnoverPage::of(Transactions, Cursor)` for consistency.

---

## Sources

### Primary (HIGH confidence)

- `src/Core/Http/Pagination/CursorPaginator.php` — last-page detection logic, `LastRecord`/`Lastrecord` field names, `NbRecords` usage
- `src/Core/Type/Parameter/PerPage.php` — canonical value object pattern
- `src/Core/Type/Primitive/CustomerId.php` — secondary integer value object reference
- `src/DeRidderDenHertog.php` — `send()` helper pattern, `getDayTurnover` method as facade method template
- `src/GetDayTurnover/Request/GetDayTurnover.php` — request class to reuse
- `src/GetDayTurnover/Type/Transactions.php` — collection class used in DayTurnoverPage
- `src/GetDayTurnover/Failure/CouldNotGetDayTurnover.php` — exception reuse (D-07)
- `src/Core/Http/Request.php` — `body()->add()` injection pattern
- `src/Core/Http/Result.php` — `$result->raw` and `$result->records` access
- `composer.json` — library versions, test runner command
- `phpunit.xml.dist` — test configuration; `effect` group excluded by default
- `phpstan.neon.dist` — PHPStan level 5, analyses `src/` only

### Secondary (MEDIUM confidence)

- `tests/DeRidderDenHertogTest.php` — test structure for adding `get_day_turnover_page` integration test
- `tests/Authentication/ApiGuidTest.php` — value object unit test pattern using `#[TestWith]`

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all libraries verified in `composer.json`
- Architecture: HIGH — all patterns taken directly from existing source files
- Pitfalls: HIGH — field name asymmetry and validation edge cases extracted from reading `CursorPaginator` and `PerPage` source

**Research date:** 2026-03-30
**Valid until:** 2026-05-30 (stable internal codebase; no external API dependencies to expire)
