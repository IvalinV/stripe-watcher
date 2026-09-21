# Webhook Record Schema and Redaction Design

## Goal

Define the durable package-owned record used to inspect Stripe webhook traffic without persisting sensitive values blindly.

## Record

Create an `stripe_watcher_webhooks` table and `StripeWebhook` model. The record stores Stripe event metadata, request metadata, redacted request content, signature verification, response metadata/content, processing duration, exception details, and lifecycle timestamps.

JSON values use database JSON columns. Request and response bodies use `longText`; bodies are redacted before persistence. The migration replaces the scaffold's unused placeholder table.

## Redaction

Redaction occurs before a record is created. JSON payloads are traversed recursively and matching keys are replaced with `[REDACTED]`. Headers are matched case-insensitively. Defaults cover authorization, cookies, Stripe signatures, passwords, tokens, API keys, client secrets, signing secrets, and webhook secrets. Applications can configure additional keys and the replacement value.

The persisted request body is never the original body. Valid JSON is decoded, redacted, and re-encoded. Invalid or non-JSON bodies are omitted by default because they cannot be safely inspected by the structured redactor.

## Configuration

Add `storage.table`, `redaction.enabled`, `redaction.replacement`, `redaction.keys`, and `redaction.headers` to the package config. Defaults preserve useful diagnostics while excluding credentials and signature material.

## Scope

This change defines storage and redaction only. Middleware capture, retention/pruning, dashboard controllers, and views remain later phases.

## Verification

Tests must prove migration columns, model casts, recursive payload redaction, case-insensitive header redaction, configured keys/replacement, invalid-body omission, and disabled-mode behavior.
