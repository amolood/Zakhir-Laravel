# Changelog

All notable changes to `laravel-zakhir` will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] — 2025-05-14

### Added
- Initial release
- `ZakhirPaymentService` — create, poll, and cancel payments
- `ZakhirClient` — HTTP layer with full request/response logging
- `ZakhirWebhookController` — handles `COMPLETED` and `REJECTED` webhooks with idempotency guards
- `VerifyZakhirWebhookSignature` middleware — optional HMAC-SHA256 webhook verification
- `ZakhirPayment` model — polymorphic, attaches to any Eloquent model
- `ZakhirLog` model — append-only audit log of every API call
- `Zakhir` facade
- `ZakhirPaymentCompleted`, `ZakhirPaymentFailed`, `ZakhirWebhookReceived` events
- Typed DTOs: `PaymentResponse`, `PaymentStatusResponse`, `CreatePaymentData`, `WebhookPayload`
- `PaymentStatus` enum with `isTerminal()` helper
- Exception hierarchy: `ZakhirException`, `ZakhirApiException`, `ZakhirWebhookException`
- Database migrations for `zakhir_payments` and `zakhir_logs` tables
- Publishable config file (`config/zakhir.php`)
- Laravel 10, 11, and 12 support
- PHP 8.2+ support
- PHPUnit test suite (unit + feature)
