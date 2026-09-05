# Changelog

## 1.0.0 — 2026-09-05

- Initial portfolio release.
- Outbound WooCommerce order synchronization.
- HMAC-signed inbound and outbound requests.
- Exponential retry scheduling with a six-attempt limit.
- Idempotency keys for safe delivery.
- HPOS-compatible order metadata.
- Audit log, connection test, and development ERP simulator.
- Guarded webhook operation when no strong shared secret is configured.
- Prevented inbound status updates from creating outbound synchronization loops.
