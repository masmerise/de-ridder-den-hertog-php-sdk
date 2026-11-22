# Changelog

All notable changes to `de-ridder-den-hertog-php-sdk` will be documented in this file.

## 1.2.1 - 2026-11-22

- Fixed valid API GUIDs being rejected, any well-formed GUID is now accepted regardless of version or case

## 1.2.0 - 2026-11-21

- Added `useProduction()` and `useTest()` to select the environment, production remains the default
- Added `CouldNotCompleteRequest` to report server faults such as the API's own request timeout
- Fixed SOAP faults, which the API answers with a `200`, leaking an uncatchable `RuntimeException`

## 1.1.2 - 2026-11-18

- Fixed GetDayTurnover failing on a `DoNotChargeVAT` or `gewichtartikel` that RenH sends as a number

## 1.1.1 - 2026-11-06

- Fixed the unsigned 64-bit customer number sentinel crashing GetDayTurnover and GetCustomers, now mapped to null
- Fixed malformed responses raising a `TypeError` instead of the documented `JsonException`

## 1.1.0 - 2026-03-31

- Removed `getDayTurnoverPaginated`
- Added `getDayTurnoverPage`

## 1.0.0 - 2026-03-29

- Stable release

## 0.2.0 - 2026-01-01

- Added GetDayTurnover

## 0.1.0 - 2025-12-14

- Initial release
