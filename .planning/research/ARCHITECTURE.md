# Architecture Patterns

**Domain:** Single-page cursor-based pagination fetch for a PHP SDK
**Researched:** 2026-03-30
**Confidence:** HIGH — all findings are drawn directly from the existing source code

---

## Scope

This document covers how three new components integrate with the existing architecture:

- `Cursor` — value object for the `LastRecord` cursor parameter
- `DayTurnoverPage` — DTO bundling a `Transactions` collection with the next cursor
- `getDayTurnoverPage` — facade method that executes a single-page fetch

No existing files require modification except `DeRidderDenHertog.php`.

---

## Existing Architecture (Relevant Subset)

### What the Paginator Already Does

`CursorPaginator` (at `src/Core/Http/Pagination/CursorPaginator.php`) already exposes all the wire-protocol knowledge needed:

| What it reads/writes | API field | Location in code |
|----------------------|-----------|-----------------|
| Page size sent in request | `RequestCount` | `applyPagination()` via `$request->body()->add()` |
| Cursor sent in request | `LastRecord` | `applyPagination()` via `$request->body()->add()` |
| Cursor returned in response | `Lastrecord` (lowercase r) | `getNextCursor()` reads `$result->raw['Lastrecord']` |
| Records returned count | `NbRecords` | `isLastPage()` reads `$result->raw['NbRecords']` |
| Record data | `Kassabonnen` | `getPageItems()` reads `$result->records['Kassabonnen']` |

The single-page method reproduces exactly this behaviour in a non-iterating form: one send, one result extraction.

### How `Result` Exposes Cursor Data

`Result` (at `src/Core/Http/Result.php`) is the internal DTO returned by every `send()` call. It exposes:

- `$result->raw` — the raw decoded JSON array, which contains `Lastrecord` (int) and `NbRecords` (int)
- `$result->records` — the decoded records array, which contains `Kassabonnen` (array)

Both fields are already populated for every `GetDayTurnover` response. No changes to `Result` are needed.

### How `GetDayTurnover` Request Accepts Cursor and Page Size

`GetDayTurnover::message()` returns only `Filter`, `FromDate`, and `TillDate`. The paginator injects `RequestCount` and `LastRecord` after construction via `$request->body()->add()`. The single-page method must do the same injection manually before calling `send()`, because the existing `send()` helper dispatches the request as-is.

---

## New Components

### 1. `Cursor` — Value Object

**Location:** `src/GetDayTurnover/Type/Parameter/Cursor.php`

**Pattern:** Identical to `PerPage` and `CustomerId`. Private constructor, named static factory, `toMessageString()` for serialization into the request body.

**Validation:** The RENH API uses `0` as the sentinel for "start from the beginning." Valid cursor values are non-negative integers (`>= 0`). `Assert::greaterThanEq($value, 0)` is appropriate.

**Wire format:** The API expects `LastRecord` as an integer in the JSON body. `toMessageString()` is a slight misnomer here — looking at `CursorPaginator`, the value is added via `$request->body()->add('LastRecord', $this->perPageLimit)` as an integer, not a string. `Cursor` should expose `toInteger(): int` to match how `PerPage` serializes (see `PerPage::toInteger()`), keeping the type consistent with how `body()->add()` works.

**Public API surface:** Yes — this is a parameter passed by consumers to `getDayTurnoverPage()`, so it is public (no `@internal`).

```php
final readonly class Cursor
{
    private function __construct(private int $position)
    {
        Assert::greaterThanEq($position, 0, 'The cursor position must be 0 or greater.');
    }

    public static function fromInteger(int $position): self
    {
        return new self($position);
    }

    public function toInteger(): int
    {
        return $this->position;
    }
}
```

### 2. `DayTurnoverPage` — Result DTO

**Location:** `src/GetDayTurnover/Type/DayTurnoverPage.php`

**Pattern:** `final readonly` with a private constructor and a named static factory. This is a pure data class — no `Collection` base, no inheritance. It bundles two pieces of data that are always produced together from a single API response.

**Fields:**
- `transactions` — `Transactions` collection (mapped from `Kassabonnen` records)
- `nextCursor` — `Cursor` value object (mapped from `$result->raw['Lastrecord']`)
- `hasMore` — `bool` derived from `NbRecords < perPageLimit`; baking this in avoids forcing consumers to compare cursor values or counts themselves

**Why `hasMore` belongs here:** The paginator's `isLastPage()` uses `$nbRecords < $this->perPageLimit`. The facade method receives `PerPage` and the raw `NbRecords` count. Deriving `hasMore` at construction time and storing it in the DTO keeps the consumer interface clean — they check `$page->hasMore` rather than re-implementing the comparison logic.

**Named factory:** `DayTurnoverPage::from(Transactions, Cursor, bool): self` is clean and matches the "explicit all fields" style used by other DTOs.

```php
final readonly class DayTurnoverPage
{
    private function __construct(
        public Transactions $transactions,
        public Cursor $nextCursor,
        public bool $hasMore,
    ) {}

    public static function from(
        Transactions $transactions,
        Cursor $nextCursor,
        bool $hasMore,
    ): self {
        return new self($transactions, $nextCursor, $hasMore);
    }
}
```

**Public API surface:** Yes — consumers receive this as the return type of `getDayTurnoverPage()`.

### 3. `getDayTurnoverPage` — Facade Method

**Location:** `src/DeRidderDenHertog.php` (modification to existing file)

**Pattern:** Follows `getDayTurnover()` exactly up to the point of result extraction, then diverges to build a `DayTurnoverPage` instead of a bare `Transactions`.

**Signature:**
```php
public function getDayTurnoverPage(
    PerPage $perPage,
    ?Cursor $cursor = null,
    ?Filter $filter = null,
    ?Date $from = null,
    ?Date $till = null,
): DayTurnoverPage
```

`cursor` defaults to `null` (not `Cursor::fromInteger(0)`) so the caller does not need to construct a `Cursor` for the first page. The facade handles the null case by omitting `LastRecord` from the request body, which matches how the paginator behaves on its first request (`$this->currentResponse instanceof Response` is false so `LastRecord` is not added).

**How cursor and page size are injected:** The existing `send()` helper takes a fully-constructed `Request` and sends it. It does not support injecting extra body fields. The method must inject `RequestCount` and optionally `LastRecord` into the request body between construction and dispatch:

```php
$request = new GetDayTurnover($filter, $from, $till)->setGuid($this->guid);
$request->body()->add('RequestCount', $perPage->toInteger());

if ($cursor !== null) {
    $request->body()->add('LastRecord', $cursor->toInteger());
}

$result = $this->send($request, CouldNotGetDayTurnover::class);
```

This is identical to what `CursorPaginator::applyPagination()` does and does not require any changes to the `send()` helper or `Request` base class.

**Building the return value:**
```php
$transactions = Transactions::of(
    array_map(new TransactionMapper(), $result->records['Kassabonnen'] ?? [])
);

$nextCursor = Cursor::fromInteger($result->raw['Lastrecord'] ?? 0);
$hasMore = ($result->raw['NbRecords'] ?? 0) >= $perPage->toInteger();

return DayTurnoverPage::from($transactions, $nextCursor, $hasMore);
```

`hasMore` uses `>=` (not `<`) to match the inverse of `CursorPaginator::isLastPage()`.

---

## Component Boundaries

| Component | Namespace | Location | Status | Public? |
|-----------|-----------|----------|--------|---------|
| `Cursor` | `DeRidderDenHertog\GetDayTurnover\Type\Parameter` | `src/GetDayTurnover/Type/Parameter/Cursor.php` | NEW | Yes |
| `DayTurnoverPage` | `DeRidderDenHertog\GetDayTurnover\Type` | `src/GetDayTurnover/Type/DayTurnoverPage.php` | NEW | Yes |
| `getDayTurnoverPage` | — | `src/DeRidderDenHertog.php` | MODIFIED | Yes |
| `GetDayTurnover` | `DeRidderDenHertog\GetDayTurnover\Request` | `src/GetDayTurnover/Request/GetDayTurnover.php` | UNCHANGED | No |
| `CursorPaginator` | `DeRidderDenHertog\Core\Http\Pagination` | `src/Core/Http/Pagination/CursorPaginator.php` | UNCHANGED | No |
| `Result` | `DeRidderDenHertog\Core\Http` | `src/Core/Http/Result.php` | UNCHANGED | No |
| `Transactions` | `DeRidderDenHertog\GetDayTurnover\Type` | `src/GetDayTurnover/Type/Transactions.php` | UNCHANGED | Yes |
| `CouldNotGetDayTurnover` | `DeRidderDenHertog\GetDayTurnover\Failure` | `src/GetDayTurnover/Failure/CouldNotGetDayTurnover.php` | UNCHANGED | Yes |

Only two files change: one new class in `GetDayTurnover/Type/Parameter/`, one new class in `GetDayTurnover/Type/`, and additions to `DeRidderDenHertog.php`.

---

## Data Flow

**Single-page fetch (`getDayTurnoverPage`):**

```
Consumer
  → $sdk->getDayTurnoverPage(PerPage, ?Cursor, ?Filter, ?Date, ?Date)

Facade
  → new GetDayTurnover($filter, $from, $till)->setGuid($this->guid)
  → $request->body()->add('RequestCount', $perPage->toInteger())
  → $request->body()->add('LastRecord', $cursor->toInteger())  [if cursor not null]
  → $this->send($request, CouldNotGetDayTurnover::class)

Core HTTP / Saloon
  → WrapRequest middleware: array → json_encode → base64 → SOAP XML
  → HTTP POST to https://renh.online/RHAPI_WEB/awws/RHAPI.awws
  → UnwrapResponse + ResultMapper: SOAP XML → base64_decode → json_decode → Result

Facade
  → getResult() checks auth failure and "Not Ok"
  → array_map(new TransactionMapper(), $result->records['Kassabonnen'] ?? [])
  → Transactions::of(...)
  → Cursor::fromInteger($result->raw['Lastrecord'] ?? 0)
  → hasMore = NbRecords >= perPageLimit
  → DayTurnoverPage::from($transactions, $nextCursor, $hasMore)

Consumer receives: DayTurnoverPage
```

The flow is structurally identical to `getDayTurnover()` with two additions: pre-send body injection and post-result DayTurnoverPage construction.

---

## Integration Points

### Integration Point 1: `GetDayTurnover` request body

`GetDayTurnover::message()` does not include `RequestCount` or `LastRecord`. Both are added to `$request->body()` after construction in the facade method. This is the same pattern `CursorPaginator::applyPagination()` uses and requires no changes to `GetDayTurnover`.

**Risk:** None. `body()->add()` merges into the existing body array before SOAP wrapping occurs. Verified by reading `CursorPaginator` which does this today.

### Integration Point 2: `Result::raw` for cursor extraction

`$result->raw['Lastrecord']` (note lowercase `r`) is how `CursorPaginator::getNextCursor()` reads the cursor. The facade must use the same key. `$result->raw['NbRecords']` provides the record count for `hasMore` logic.

**Risk:** Low. The key names are unusual (mixed case) but already used by `CursorPaginator` so the correct spellings are known.

### Integration Point 3: `send()` helper reuse

`getDayTurnoverPage()` reuses the existing `protected send()` method without modification. All auth checking, error handling, and DTO conversion are inherited automatically.

**Risk:** None.

### Integration Point 4: `getDayTurnoverPaginated` remains unchanged

`getDayTurnoverPaginated` uses `$this->paginate()` which uses `CursorPaginator`. The new method uses `$this->send()` directly. The two code paths are completely independent.

---

## Build Order

Dependencies flow downward. Build in this order to avoid depending on unbuilt components at each step.

| Step | Component | Reason |
|------|-----------|--------|
| 1 | `Cursor` value object | Has no dependencies on other new code; needed by step 3 and 4 |
| 2 | `DayTurnoverPage` DTO | Depends on `Cursor` (step 1) and `Transactions` (existing) |
| 3 | `getDayTurnoverPage` on facade | Depends on `Cursor` (step 1), `DayTurnoverPage` (step 2), and existing infrastructure |
| 4 | Tests | Written against the completed public interface |

Step 1 and step 2 have no external build dependencies — they can be written and tested in isolation before touching the facade. The facade method (step 3) is the integration point and should be built last.

---

## Anti-Patterns to Avoid

### Anti-Pattern 1: Adding `LastRecord` to `GetDayTurnover::message()`

**What it looks like:** Making `GetDayTurnover` accept a `?Cursor` constructor argument and include `LastRecord` in `message()`.

**Why bad:** `GetDayTurnover` already implements `Paginatable` and is used by `getDayTurnoverPaginated()`. The `CursorPaginator` injects `LastRecord` and `RequestCount` itself via `applyPagination()`. If the request also sets `LastRecord` in `message()`, the paginator's injection would overwrite it on every page — but the first page call would always start at cursor 0, breaking resumable pagination. Separation of concerns: the paginator owns cursor injection for its use case; the facade method owns it for the single-page use case.

### Anti-Pattern 2: `Cursor` implementing `toMessageString()` returning a stringified integer

**What it looks like:** `public function toMessageString(): string { return (string) $this->position; }`

**Why bad:** `$request->body()->add()` accepts mixed values. `CursorPaginator` adds `LastRecord` as an integer. The RENH API expects an integer in the JSON payload. Returning a string would produce `"LastRecord": "42"` instead of `"LastRecord": 42` in the JSON body, which may cause an API error. Use `toInteger(): int` to match how `PerPage` serializes.

### Anti-Pattern 3: `DayTurnoverPage` without `hasMore`

**What it looks like:** Exposing only `transactions` and `nextCursor`, forcing consumers to detect the last page themselves.

**Why bad:** The last-page condition (`NbRecords < perPageLimit`) requires the consumer to know the page size they originally requested and re-implement the comparison. This leaks API protocol knowledge into consumer code. The facade already holds `$perPage` and `$result->raw['NbRecords']` at construction time — deriving `hasMore` at the source is strictly better.

---

## Confidence Assessment

| Area | Confidence | Basis |
|------|------------|-------|
| Cursor value object pattern | HIGH | Direct pattern match with `PerPage` and `CustomerId` in source |
| Body injection approach | HIGH | Confirmed by reading `CursorPaginator::applyPagination()` |
| `raw['Lastrecord']` key spelling | HIGH | Confirmed in `CursorPaginator::getNextCursor()` |
| `hasMore` derivation | HIGH | Confirmed in `CursorPaginator::isLastPage()` |
| `DayTurnoverPage` DTO design | HIGH | Follows existing final readonly pattern; no external dependency |
| No modification to existing request | HIGH | Paginator already injects fields post-construction; confirmed by source |

---

*Architecture research: 2026-03-30*
