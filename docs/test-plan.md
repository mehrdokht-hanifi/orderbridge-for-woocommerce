# Test plan

## Automated checks

Run `bash tests/static-check.sh` to lint PHP, scan for common credential patterns, and verify HMAC and retry behavior.

## Integration checks

1. Start the mock ERP with `cd mock-erp && php -S 127.0.0.1:8787 router.php`.
2. Configure API URL `http://127.0.0.1:8787/api/`, key `demo-api-key`, and secret `demo-webhook-secret`.
3. Test the connection from WooCommerce > OrderBridge.
4. Change a test order status and confirm a successful outbound audit entry.
5. Repeat the identical request and confirm the mock API returns `duplicate: true`.
6. Run `php mock-erp/send-webhook.php <order-id> fulfilled <site-webhook-url>`.
7. Confirm the WooCommerce order becomes completed and stores the ERP ID.
8. Send an invalid signature and confirm HTTP 401 with no order mutation.
9. Stop the mock ERP, trigger a sync, and confirm retry scheduling and failed audit entries.

Live WordPress/WooCommerce integration testing remains required before production use.
