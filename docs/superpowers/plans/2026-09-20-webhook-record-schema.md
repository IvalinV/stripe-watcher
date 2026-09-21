# Webhook Record Schema Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the package-owned Stripe webhook record and safe, configurable redaction layer.

**Architecture:** A focused `StripeWebhook` Eloquent model stores normalized metadata and JSON diagnostics. A `WebhookRedactor` service transforms headers, structured payloads, and request bodies before persistence; the capture middleware will consume this service in a later phase.

**Tech Stack:** PHP 8.3, Laravel 12/13, Eloquent, migrations, Pest 4, Orchestra Testbench.

**Spec:** `docs/superpowers/specs/2026-09-20-webhook-record-schema-design.md`

## Global Constraints

- Keep the package focused and Laravel-native.
- Never persist an unredacted request body.
- Keep dashboard and capture middleware out of this phase.
- Preserve strict types and 100% type coverage.

---

### Task 1: Configuration and Redaction Contract

**Files:**
- Modify: `config/stripe-watcher.php`
- Create: `src/Support/WebhookRedactor.php`
- Create: `tests/Unit/WebhookRedactorTest.php`

**Interfaces:**
- Produces `WebhookRedactor::headers(array $headers): array`.
- Produces `WebhookRedactor::payload(array $payload): array`.
- Produces `WebhookRedactor::body(?string $body): ?string`.

- [ ] **Step 1: Write failing tests** for recursive key replacement, case-insensitive headers, configured keys/replacement, JSON body encoding, invalid-body omission, and disabled redaction.
- [ ] **Step 2: Run `composer test:unit -- --filter=WebhookRedactorTest`** and verify the tests fail because the class/configuration does not exist.
- [ ] **Step 3: Add config defaults and implement the redactor with recursive arrays and case-insensitive matching.** Default sensitive keys must include `authorization`, `cookie`, `set-cookie`, `stripe-signature`, `password`, `token`, `access_token`, `refresh_token`, `api_key`, `client_secret`, `signing_secret`, and `webhook_secret`.
- [ ] **Step 4: Run the focused tests and verify they pass.**

### Task 2: Webhook Migration and Model

**Files:**
- Delete: `database/migrations/2026_01_01_000000_create_stripe_watcher_placeholder_table.php`
- Create: `database/migrations/2026_09_20_000001_create_stripe_watcher_webhooks_table.php`
- Create: `src/Models/StripeWebhook.php`
- Create: `tests/Feature/WebhookRecordTest.php`

**Interfaces:**
- `StripeWebhook` maps to the configured `stripe-watcher.storage.table` table.
- JSON attributes cast to arrays: `request_headers`, `request_payload`, `response_headers`.
- Boolean attribute: `livemode`.
- Datetime attributes: `started_at`, `completed_at`, `failed_at`.

- [ ] **Step 1: Write failing migration/model tests** that migrate SQLite, create a record with all supported attributes, and assert persisted values and casts.
- [ ] **Step 2: Run `composer test:unit -- --filter=WebhookRecordTest`** and verify failure from the missing table/model.
- [ ] **Step 3: Add the migration with indexed event ID/type/status timestamps and the request, Stripe, response, timing, and exception columns defined by the spec.**
- [ ] **Step 4: Add the model with guarded/fillable attributes, configured table name, and casts.**
- [ ] **Step 5: Run the focused tests and verify they pass.**

### Task 3: Provider Wiring and Documentation

**Files:**
- Modify: `src/StripeWatcherServiceProvider.php`
- Modify: `README.md`
- Modify: `tests/Feature/StripeWatcherTest.php`

- [ ] **Step 1: Add a failing assertion that the migration is published through the existing `stripe-watcher-migrations` tag and that the redactor resolves from the container.**
- [ ] **Step 2: Run the focused provider tests and verify the new assertions fail.**
- [ ] **Step 3: Bind `WebhookRedactor` as a singleton and retain the existing guarded migration publishing.**
- [ ] **Step 4: Document the record table, redaction defaults, and configuration keys without promising capture behavior yet.**
- [ ] **Step 5: Run `composer test`, `composer lint:check`, and `composer analyse`.**
