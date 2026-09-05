# OrderBridge for WooCommerce

OrderBridge is a portfolio-grade WooCommerce integration demonstrating reliable order synchronization with an external ERP or fulfillment API.

## Features

- Background order export using Action Scheduler
- Normalized order, customer, shipping, and line-item payloads
- Bearer authentication and HMAC-SHA256 request signing
- Idempotency keys for duplicate-safe writes
- Exponential retries with a six-attempt limit
- Signed inbound status webhooks with replay protection
- WooCommerce HPOS-compatible metadata
- Connection test and synchronization audit log
- Standalone mock ERP for integration demonstrations

## Requirements

- WordPress 6.4+
- WooCommerce 8.0+
- PHP 7.4+

## Installation

1. Upload `orderbridge-for-woocommerce.zip` in **Plugins > Add New > Upload Plugin**.
2. Activate the plugin.
3. Open **WooCommerce > OrderBridge**.
4. Enter the ERP API URL, API key, and a strong shared webhook secret.
5. Run **Test connection** before synchronizing orders.

## Webhook endpoint

`POST /wp-json/orderbridge/v1/webhook`

See [`docs/api-contract.md`](docs/api-contract.md) for headers and payloads.

## Local demonstration

```bash
cd mock-erp
php -S 127.0.0.1:8787 router.php
```

Demo values are intentionally local-only:

- API URL: `http://127.0.0.1:8787/api/`
- API key: `demo-api-key`
- Webhook secret: `demo-webhook-secret`

Never reuse demo credentials in production.

## Verification

```bash
bash tests/static-check.sh
```

See [`docs/test-plan.md`](docs/test-plan.md). Live WooCommerce integration testing is still required before production use.

## Security

- Settings require the `manage_woocommerce` capability.
- API secrets render as password inputs and are never logged.
- Webhook signatures cover the raw request body and timestamp.
- Timestamps outside a five-minute window are rejected.
- Inputs are sanitized and administration output is escaped.

## License

GPL-2.0-or-later.
