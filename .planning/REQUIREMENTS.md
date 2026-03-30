# Requirements: De Ridder & Den Hertog PHP SDK

**Defined:** 2026-03-30
**Core Value:** Enable PHP applications to interact with the RENH API through a type-safe, well-structured SDK that handles the protocol complexity transparently.

## v1.0 Requirements

Requirements for milestone v1.0. Each maps to roadmap phases.

### Pagination

- [ ] **PAG-01**: SDK provides a `Cursor` value object that wraps an integer for type-safe cursor parameter handling
- [ ] **PAG-02**: SDK provides a `DayTurnoverPage` DTO that bundles a `Transactions` collection with an optional next `Cursor`
- [ ] **PAG-03**: SDK provides a `getDayTurnoverPage` method that fetches a single page of day turnover given a `PerPage` and optional `Cursor`, returning a `DayTurnoverPage`

## Future Requirements

(None deferred)

## Out of Scope

| Feature | Reason |
|---------|--------|
| Offset-based pagination | RENH API uses cursor-based pagination only |
| Single-page fetch for other endpoints | Only GetDayTurnover supports pagination currently |
| Modifying `getDayTurnoverPaginated` | Stays as-is for full-iteration use cases |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| PAG-01 | — | Pending |
| PAG-02 | — | Pending |
| PAG-03 | — | Pending |

**Coverage:**
- v1.0 requirements: 3 total
- Mapped to phases: 0
- Unmapped: 3 ⚠️

---
*Requirements defined: 2026-03-30*
*Last updated: 2026-03-30 after initial definition*
