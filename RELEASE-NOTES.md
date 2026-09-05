# OrderBridge for WooCommerce v1.0.0

Initial portfolio release of a WooCommerce-to-ERP reference integration.

## Included

- Installable WooCommerce plugin.
- Background order export with idempotency keys.
- HMAC-signed requests and replay-protected status webhooks.
- Exponential retry scheduling and synchronization audit log.
- HPOS-compatible order metadata.
- Mock ERP and outbound webhook simulator.
- API contract, architecture notes, test plan, and case study.
- Automated PHP matrix workflow for GitHub Actions.
- Explicit protection against unconfigured webhook secrets and status-update loops.

## Verification status

Structural and credential-pattern checks passed in the build environment. The repository includes PHP lint and isolated unit checks for execution in GitHub Actions or any PHP 7.4+ environment. Live WordPress/WooCommerce integration testing is pending and required before production deployment.
