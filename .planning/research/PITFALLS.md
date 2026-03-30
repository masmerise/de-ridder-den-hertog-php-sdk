# Pitfalls Research

**Domain:** Adding single-page cursor-based pagination fetch to an existing PHP SDK (RENH / De Ridder & Den Hertog)
**Researched:** 2026-03-30
**Confidence:** HIGH — derived from direct codebase analysis; all patterns verified against existing source files.

---

## Critical Pitfalls

### Pitfall 1: Cursor Value Inconsistency — Integer vs. Nullable

**What goes wrong:**
The `Cursor` value object is implemented to always require a positive integer, mirroring `PerPage` or `CustomerId`. But the first page has no prior cursor — callers must pass `null` to signal "start from the beginning." If the value object enforces `greaterThan(0)` unconditionally and has no null-safe factory, the only way to request page 1 becomes impossible through the typed API, forcing callers to work around it.

**Why it happens:**
Developers look at `PerPage` and `CustomerId` as the model for `Cursor`. Both use `Assert::greaterThan` in the constructor. They copy this pattern without considering that a cursor has a "no cursor yet" state that those value objects do not have.

**How to avoid:**
The `Cursor` value object must represent a valid non-zero cursor (from a previous response's `Lastrecord`). The `getDayTurnoverPage` method signature must accept `?Cursor` — null meaning "first page." Do not encode the null-cursor concept inside the value object itself. Keep `Cursor` strictly positive, pass null through the method parameter.

**Warning signs:**
- A `Cursor::none()` or `Cursor::fromInteger(0)` factory method appearing in the codebase
- `getDayTurnoverPage` accepting `Cursor` (non-nullable) directly
- Test that calls `getDayTurnoverPage` for page 1 using a workaround integer

**Phase to address:**
Phase implementing the `Cursor` value object — enforce in its constructor and verify the method signature is `?Cursor`.

---

### Pitfall 2: Misreading the Response Field Name — `Lastrecord` vs `LastRecord`

**What goes wrong:**
The RENH API uses `LastRecord` (capital R) in the request body and `Lastrecord` (lowercase r) in the response payload. The existing `CursorPaginator` correctly reads `$result->raw['Lastrecord']` (lowercase). A new implementation that follows the request-side casing (`LastRecord`) will silently receive `null` from the array access — PHP does not throw on missing array keys — leading to a cursor of `null` reported as "end of data" or a type error when constructing the next `Cursor`.

**Why it happens:**
The asymmetry is not obvious. When writing the `getDayTurnoverPage` response processing, developers see `LastRecord` in the request-building code and use the same string for the response read. The existing paginator source is the only authoritative reference for the correct casing.

**How to avoid:**
Read `CursorPaginator::getNextCursor()` before writing response extraction. Use exactly `$result->raw['Lastrecord']` (lowercase r). Add a PHPDoc array shape annotation on the response extraction code to make the casing explicit and verified by PHPStan.

**Warning signs:**
- `$result->raw['LastRecord']` anywhere in new code
- `nextCursor` returning `null` unexpectedly on a non-final page in integration tests
- PHPStan reporting a possibly-undefined array key on `raw`

**Phase to address:**
Phase implementing `getDayTurnoverPage` response parsing — cross-check against `CursorPaginator::getNextCursor` before writing.

---

### Pitfall 3: Last-Page Detection Using Wrong Field — `NbRecords` vs `Lastrecord`

**What goes wrong:**
The `DayTurnoverPage` DTO must expose a `nextCursor` to the caller so they know whether to continue fetching. A naive implementation stores `Lastrecord` as the next cursor unconditionally. But the existing `CursorPaginator` shows the correct last-page sentinel: `NbRecords < perPageLimit`. When `NbRecords` equals the page size, there may be more records. Returning a non-null cursor on the final page (when `NbRecords < RequestCount`) causes callers to make one extra API call that returns zero records.

**Why it happens:**
Developers think the cursor itself signals the end: "if `Lastrecord` is 0 or absent, we're done." The API does not guarantee this. The actual sentinel is record count vs. page size, which is how `CursorPaginator::isLastPage` works today.

**How to avoid:**
In the response processing for `getDayTurnoverPage`, compute whether it is the last page as `NbRecords < RequestCount`. When it is the last page, set `nextCursor` to `null` on `DayTurnoverPage`. When it is not the last page, set `nextCursor` to a new `Cursor` constructed from `Lastrecord`.

**Warning signs:**
- `DayTurnoverPage::$nextCursor` is always non-null even when fewer records than page size were returned
- Integration test fetching a small dataset with a large page size and receiving a non-null next cursor
- `nextCursor` is null-checked against `Lastrecord === 0` rather than against record count

**Phase to address:**
Phase implementing `DayTurnoverPage` construction — include a specific test: fetch with `PerPage` larger than dataset and assert `nextCursor` is `null`.

---

### Pitfall 4: `DayTurnoverPage` Placed in Wrong Namespace / Directory

**What goes wrong:**
`DayTurnoverPage` is a domain DTO that belongs to the `GetDayTurnover` module. Because it bundles a `Transactions` collection with cursor metadata, a developer may place it in `Core\Type\` (because it feels generic) or at the root `src/` level. This breaks the established module structure: every domain type lives under `GetDayTurnover\Type\`, every domain exception under `GetDayTurnover\Failure\`, every domain request under `GetDayTurnover\Request\`.

**Why it happens:**
`DayTurnoverPage` does not map 1:1 to a single API response record — it wraps a collection plus metadata — so it does not feel like a normal `Type`. Developers look for a "results" layer and don't find one, defaulting to `Core`.

**How to avoid:**
Place `DayTurnoverPage` at `src/GetDayTurnover/Type/DayTurnoverPage.php`, namespace `DeRidderDenHertog\GetDayTurnover\Type`. This is consistent with `Transactions`, `Transaction`, `Item`, and `PayForm` all living there. The `Cursor` value object belongs at `src/Core/Type/Parameter/Cursor.php` (alongside `PerPage`, `Date`, `Filter`) because it is a generic pagination parameter, not domain-specific.

**Warning signs:**
- `DayTurnoverPage` under `src/Core/`
- `Cursor` under `src/GetDayTurnover/`
- Import statements in `DeRidderDenHertog.php` that cross module boundaries unexpectedly

**Phase to address:**
Phase scaffolding the new files — establish correct paths before writing any logic.

---

### Pitfall 5: `getDayTurnoverPage` Not Following the `send` / `getResult` Pattern

**What goes wrong:**
The existing facade `DeRidderDenHertog` uses a private `send()` method that wraps `$this->client->send()`, catches `Throwable`, and calls `getResult()` which checks for auth failure and domain failure before returning `Result`. A new `getDayTurnoverPage` that calls `$this->client->send()` directly bypasses `UnknownException` wrapping and auth-failure detection. Uncaught exceptions surface as raw Saloon or HTTP exceptions rather than the SDK's typed exception hierarchy.

**Why it happens:**
`getDayTurnoverPaginated` is the closest existing method but uses `paginate()` not `send()` — developers may not notice `send()` exists and write their own send call.

**How to avoid:**
`getDayTurnoverPage` must call `$this->send(request: ..., onFailure: CouldNotGetDayTurnover::class)` exactly as `getDayTurnover` does. The `Result` returned by `send()` is then used to construct `DayTurnoverPage`.

**Warning signs:**
- `$this->client->send()` called directly inside `getDayTurnoverPage`
- Missing `@throws CouldNotAuthenticate` or `@throws UnknownException` in the docblock
- No test exercising auth failure on the new method

**Phase to address:**
Phase implementing `getDayTurnoverPage` in the facade — review `getDayTurnover` as the direct template.

---

### Pitfall 6: Request Mutation — `LastRecord` and `RequestCount` Added to Wrong Scope

**What goes wrong:**
The existing `GetDayTurnover` request class does not include `LastRecord` or `RequestCount` in its `message()` method — those are injected by `CursorPaginator::applyPagination()` via `$request->body()->add(...)`. A new single-page request needs to inject these two parameters. If done by modifying `GetDayTurnover::message()` to conditionally include them, it risks breaking `getDayTurnoverPaginated` (which also uses `GetDayTurnover` and has its own injection logic from the paginator).

**Why it happens:**
It seems economical to add optional `?PerPage` and `?Cursor` parameters to the existing `GetDayTurnover` request and include them conditionally. The coupling to the paginator's own injection is not immediately obvious.

**How to avoid:**
Create a separate `GetDayTurnoverPage` request class that extends `Request` and includes `RequestCount` and `LastRecord` in its own `message()` method. Never modify `GetDayTurnover` (the existing request). The PROJECT.md already states "Modifying the existing `getDayTurnoverPaginated` method" is out of scope — treat the existing request class the same way.

**Warning signs:**
- Optional `$cursor` or `$perPage` parameters added to `GetDayTurnover::__construct()`
- Conditional `array_filter` in `GetDayTurnover::message()` that checks for cursor
- `getDayTurnoverPaginated` tests failing after adding cursor support

**Phase to address:**
Phase implementing the new request object — create a distinct class; do not touch `GetDayTurnover.php`.

---

## Technical Debt Patterns

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Add `RequestCount` / `LastRecord` as optional params to existing `GetDayTurnover` | One less file | Breaks paginator injection; conditional logic in message() pollutes existing request | Never |
| Skip `Cursor` value object, accept raw `?int` in `getDayTurnoverPage` | Simpler API surface | Breaks type consistency; callers can pass 0 or negatives; no validation | Never |
| Return `array` instead of `DayTurnoverPage` | No new DTO to write | Caller must know array shape; no type hints; breaks IDE completion | Never |
| Skip `null` cursor on last page, always return a `Cursor` | Simpler `DayTurnoverPage` construction | Callers cannot detect end-of-data without making an extra empty-result call | Never |

---

## Integration Gotchas

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| RENH API — cursor field name | Reading `$result->raw['LastRecord']` (capital R) from response | Read `$result->raw['Lastrecord']` (lowercase r) — matches existing `CursorPaginator::getNextCursor()` |
| RENH API — last page detection | Checking `Lastrecord === 0` as end-of-data sentinel | Check `NbRecords < RequestCount` — matches existing `CursorPaginator::isLastPage()` |
| RENH API — first page | Sending `LastRecord: 0` in request body | Omit `LastRecord` entirely from the request body when no cursor is provided (null cursor) |
| Saloon body mutation | Calling `$request->body()->add()` after `boot()` has run | Populate `message()` with all required fields; let `Request::request()` assemble the body before `boot()` fires |

---

## Performance Traps

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Fetching first page with an arbitrarily large `PerPage` to simulate "all records" | Works on small datasets; RENH API timeout at 120 s already set | Use `getDayTurnover()` (no pagination) for full dataset; reserve `getDayTurnoverPage` for genuinely paged use cases | Any dataset where a single large page exceeds the API's processing time |

---

## Security Mistakes

| Mistake | Risk | Prevention |
|---------|------|------------|
| Exposing raw `Lastrecord` integer as a public field on `DayTurnoverPage` | Callers may construct arbitrary cursors and probe internal record IDs | Already mitigated: `Cursor` value object wraps and validates the integer; callers receive `?Cursor`, not a raw int |

---

## "Looks Done But Isn't" Checklist

- [ ] **`Cursor` value object:** Has static factory method (`fromInteger`) and `toInteger()` for serialising into `LastRecord` request field — not just a constructor
- [ ] **`DayTurnoverPage` DTO:** Exposes `nextCursor` as `?Cursor`, not `?int` or `int`
- [ ] **`getDayTurnoverPage` docblock:** Declares all four `@throws` tags: `CouldNotAuthenticate`, `CouldNotGetDayTurnover`, `UnknownException`, `ValidationException` — same set as `getDayTurnover`
- [ ] **Last-page case tested:** Integration test with a page size larger than the total record set asserts `nextCursor === null`
- [ ] **First-page case tested:** Integration test calling `getDayTurnoverPage` with `null` cursor returns a non-empty `Transactions` collection
- [ ] **`Kassabonnen` key guarded:** Response extraction uses `$result->records['Kassabonnen'] ?? []` — matches existing `getDayTurnover` and `CursorPaginator::getPageItems`
- [ ] **Existing `getDayTurnoverPaginated` still passes:** Run its integration test after adding the new method to confirm `GetDayTurnover` request was not modified

---

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Wrong cursor field casing in response read | LOW | Fix string key from `LastRecord` to `Lastrecord`; rerun integration test |
| `GetDayTurnover` modified with optional params | MEDIUM | Revert `GetDayTurnover.php` to original; create `GetDayTurnoverPage` request class; retest paginator |
| `DayTurnoverPage` in wrong namespace | LOW | Move file; update namespace declaration; update import in `DeRidderDenHertog.php` |
| Non-null cursor returned on last page | LOW | Add `NbRecords < RequestCount` check in response processing; fix `DayTurnoverPage` constructor call |

---

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Cursor nullable inconsistency | `Cursor` value object implementation | Constructor rejects 0/negatives; method signature is `?Cursor`; unit test confirms |
| `Lastrecord` vs `LastRecord` casing | `getDayTurnoverPage` response parsing | Code review against `CursorPaginator::getNextCursor`; PHPStan array shape check |
| Last-page detection using wrong sentinel | `DayTurnoverPage` construction | Integration test: page size > dataset count → `nextCursor === null` |
| `DayTurnoverPage` in wrong namespace | File scaffolding (first action) | Namespace declaration matches `DeRidderDenHertog\GetDayTurnover\Type`; autoloader resolves correctly |
| `getDayTurnoverPage` bypassing `send()` | Facade implementation | `@throws` docblock complete; existing auth-failure test passes on new method |
| Request mutation of existing `GetDayTurnover` | New request class creation | `GetDayTurnover.php` diff is empty; `getDayTurnoverPaginated` integration test passes unchanged |

---

## Sources

- Direct codebase analysis: `src/Core/Http/Pagination/CursorPaginator.php` — authoritative source for `Lastrecord` casing and `NbRecords` last-page logic
- Direct codebase analysis: `src/GetDayTurnover/Request/GetDayTurnover.php` — confirms existing request does not include cursor/page-size fields
- Direct codebase analysis: `src/DeRidderDenHertog.php` — confirms `send()` / `paginate()` / `getResult()` pattern that new method must follow
- Direct codebase analysis: `src/Core/Type/Parameter/PerPage.php` and `src/Core/Type/Primitive/CustomerId.php` — value object templates for `Cursor`
- Direct codebase analysis: `tests/DeRidderDenHertogTest.php` — shows integration test structure and confirms existing paginator test that must stay green

---
*Pitfalls research for: Adding single-page cursor-based day turnover fetch to De Ridder & Den Hertog PHP SDK*
*Researched: 2026-03-30*
