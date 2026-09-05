# API contract

## Authentication

Outbound requests use `Authorization: Bearer <api-key>`. Both directions include:

- `X-OrderBridge-Timestamp`: Unix timestamp.
- `X-OrderBridge-Signature`: HMAC-SHA256 of `<timestamp>.<raw-body>`.
- `Idempotency-Key`: stable key for an order revision (outbound order requests only).

Requests older than five minutes are rejected. Signature comparison is timing-safe.

## `POST /api/orders`

```json
{
  "event": "order.upserted",
  "idempotency_key": "wc-123-1788600000",
  "order": {
    "id": 123,
    "number": "123",
    "status": "processing",
    "currency": "USD",
    "total": "149.00",
    "customer": { "email": "buyer@example.test", "first_name": "Alex", "last_name": "Rivera" },
    "shipping": { "country": "CH", "city": "Zurich", "postcode": "8001" },
    "items": [{ "line_id": 8, "sku": "TS-01", "name": "T-Shirt", "quantity": 2, "total": "98.00" }]
  }
}
```

## `POST /wp-json/orderbridge/v1/webhook`

```json
{ "order_id": 123, "status": "fulfilled", "remote_id": "ERP-123" }
```

Supported ERP statuses: `accepted`, `fulfilled`, `cancelled`, `on_hold`.
