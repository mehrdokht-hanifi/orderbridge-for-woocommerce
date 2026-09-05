# OrderBridge for WooCommerce — Portfolio Case Study

## Problem

Commerce teams often need WooCommerce orders inside an ERP, warehouse, or fulfillment service. Direct synchronous calls are fragile: a slow ERP can delay checkout, retries may create duplicate records, and unauthenticated callbacks can modify orders.

## Solution

I designed a reusable integration plugin that queues order updates, delivers normalized JSON to an external API, verifies signed status webhooks, retries transient failures, and records an operational audit trail.

## Engineering highlights

- Native WooCommerce hooks and CRUD APIs.
- Action Scheduler with WordPress Cron fallback.
- HMAC-SHA256 signatures and replay-window validation.
- Idempotent delivery per order revision.
- Six-attempt exponential retry strategy.
- HPOS-compatible synchronization metadata.
- Capability-protected settings and escaped administration output.
- Self-contained mock ERP and webhook simulator.

## Role

Architecture, WordPress/PHP implementation, API contract, security model, test tooling, documentation, and release packaging.

## Honest limitation

The release passes static and isolated unit checks. Full integration testing against a live WordPress, WooCommerce, database, and production ERP is outside the current environment and must be completed before deployment.
