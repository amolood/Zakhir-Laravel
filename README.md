<div align="center">
  <img src="https://raw.githubusercontent.com/amolood/Zakhir-Laravel/main/art/logo.png" alt="Zakhir" width="160" />

  <h1>Laravel Zakhir</h1>

  <p>
    Official Laravel package for integrating the <strong>Zakhir</strong> payment gateway.<br/>
    Create payments, poll status, handle webhooks — with full audit logging and polymorphic model support.
  </p>

  <p>
    <a href="https://packagist.org/packages/amolood/zakhir-laravel"><img src="https://img.shields.io/packagist/v/amolood/zakhir-laravel.svg?style=flat-square" alt="Latest Version"></a>
    <a href="https://packagist.org/packages/amolood/zakhir-laravel"><img src="https://img.shields.io/packagist/php-v/amolood/zakhir-laravel.svg?style=flat-square" alt="PHP Version"></a>
    <a href="https://packagist.org/packages/amolood/zakhir-laravel"><img src="https://img.shields.io/packagist/l/amolood/zakhir-laravel.svg?style=flat-square" alt="License"></a>
    <a href="https://packagist.org/packages/amolood/zakhir-laravel"><img src="https://img.shields.io/packagist/dt/amolood/zakhir-laravel.svg?style=flat-square" alt="Total Downloads"></a>
  </p>

  <p>
    Built by <a href="https://digitalize.sd">Digitalize Lab</a> &nbsp;·&nbsp;
    Maintained by <a href="https://amolood.com">Abdalrahman Molood</a>
  </p>
</div>

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Options Reference](#options-reference)
  - [Staging Environment](#staging-environment)
- [Usage](#usage)
  - [Create a Payment](#create-a-payment)
  - [Poll Payment Status](#poll-payment-status)
  - [Cancel a Payment](#cancel-a-payment)
  - [Using the Facade](#using-the-facade)
  - [Dependency Injection](#dependency-injection)
- [Webhook Handling](#webhook-handling)
  - [Registering a Payment](#registering-a-payment)
  - [Listening to Events](#listening-to-events)
  - [Webhook Security](#webhook-security)
- [Database](#database)
  - [Migrations](#migrations)
  - [ZakhirPayment Model](#zakhirpayment-model)
  - [ZakhirLog Model](#zakhirlog-model)
- [Events Reference](#events-reference)
- [Exception Handling](#exception-handling)
- [Architecture Overview](#architecture-overview)
- [Testing](#testing)
- [Changelog](#changelog)
- [Credits](#credits)
- [License](#license)

---

## Requirements

| Dependency | Version                   |
| ---------- | ------------------------- |
| PHP        | `^8.2`                    |
| Laravel    | `^10.0 \| ^11.0 \| ^12.0` |

---

## Installation

Install via Composer:

```bash
composer require amolood/zakhir-laravel
```

Laravel's auto-discovery will register the service provider and `Zakhir` facade automatically. No manual registration needed.

Publish the configuration file:

```bash
php artisan vendor:publish --tag=zakhir-config
```

Run the migrations:

```bash
php artisan migrate
```

> If you prefer to publish migrations instead of letting the package load them automatically:
>
> ```bash
> php artisan vendor:publish --tag=zakhir-migrations
> ```

---

## Configuration

All configuration lives in `config/zakhir.php`. After publishing, open that file and fill in your values directly — no `.env` entries are required.

```bash
php artisan vendor:publish --tag=zakhir-config
```

```php
// config/zakhir.php

return [

    // "production" or "staging"
    'environment' => 'production',

    // Production credentials — from your Zakhir merchant dashboard
    'base_url' => 'https://zakhir.net/api/',
    'tenant'   => 'your_tenant_id',
    'profile'  => 'your_profile_id',
    'api_key'  => 'your_api_key',

    // Staging credentials — used when environment is "staging"
    'staging_base_url' => '',
    'staging_tenant'   => '',
    'staging_profile'  => '',
    'staging_api_key'  => '',

    // Where Zakhir POSTs payment status notifications
    'webhook_url' => 'https://yourdomain.com/api/zakhir/webhook',

    // Where customers are redirected after checkout
    'return_url' => 'https://yourdomain.com/orders/return',

    // Optional HMAC-SHA256 secret for webhook signature verification
    // Leave empty to skip signature checks
    'webhook_secret' => '',

    // HTTP timeout in seconds
    'timeout' => 15,

    // Log every API request and response to the zakhir_logs table
    'logging' => true,

    'routes' => [
        'enabled'    => true,
        'prefix'     => 'api/zakhir',    // webhook available at POST /api/zakhir/webhook
        'middleware' => ['api'],
    ],

];
```

### Options Reference

| Key                 | Type     | Description                                     |
| ------------------- | -------- | ----------------------------------------------- |
| `environment`       | `string` | `"production"` or `"staging"`                   |
| `base_url`          | `string` | Production API base URL                         |
| `tenant`            | `string` | Merchant tenant ID                              |
| `profile`           | `string` | Merchant profile ID                             |
| `api_key`           | `string` | API key for request authentication              |
| `staging_base_url`  | `string` | Staging API base URL                            |
| `staging_tenant`    | `string` | Staging tenant ID                               |
| `staging_profile`   | `string` | Staging profile ID                              |
| `staging_api_key`   | `string` | Staging API key                                 |
| `webhook_url`       | `string` | Public URL where Zakhir sends status callbacks  |
| `return_url`        | `string` | URL customers land on after checkout            |
| `webhook_secret`    | `string` | HMAC secret for webhook verification (optional) |
| `timeout`           | `int`    | HTTP request timeout in seconds                 |
| `logging`           | `bool`   | Write every API call to `zakhir_logs` table     |
| `routes.enabled`    | `bool`   | Auto-register the built-in webhook route        |
| `routes.prefix`     | `string` | URL prefix for the webhook route                |
| `routes.middleware` | `array`  | Middleware applied to the webhook route         |

### Staging Environment

Set `environment` to `"staging"` and fill in the staging credentials block. The package selects the correct set of credentials automatically — no other changes needed.

```php
'environment'    => 'staging',
'staging_base_url' => 'https://staging.zakhir.net/api/',
'staging_tenant'   => 'staging_tenant_id',
'staging_profile'  => 'staging_profile_id',
'staging_api_key'  => 'staging_api_key',
```

---

## Usage

### Create a Payment

```php
use Zakhir\LaravelZakhir\ZakhirPaymentService;
use Zakhir\LaravelZakhir\Data\PaymentResponse;

$zakhir = app(ZakhirPaymentService::class);

$response = $zakhir->createPayment(
    amount: 250.00,            // in SDG (or your configured currency)
    currency: 'SDG',
    note: 'Order #1024',
    returnUrl: 'https://yourdomain.com/orders/1024', // optional, falls back to config
    notifyUrl: 'https://yourdomain.com/api/zakhir/webhook', // optional, falls back to config
    referenceId: null,         // optional — a UUID is auto-generated if omitted
);

// Redirect the customer to the Zakhir checkout page
return redirect($response->checkoutUrl);
```

The returned `PaymentResponse` object exposes:

| Property                 | Type           | Description                                                    |
| ------------------------ | -------------- | -------------------------------------------------------------- |
| `$id`                    | `string`       | Zakhir's internal payment ID                                   |
| `$referenceId`           | `string`       | The UUID sent in the request (store this to poll/cancel later) |
| `$status`                | `string`       | `PENDING`, `COMPLETED`, etc.                                   |
| `$checkoutUrl`           | `string\|null` | Hosted checkout page URL — redirect your customer here         |
| `$mobileAppUrl`          | `string\|null` | Deep link for mobile Zakhir app                                |
| `$paymentToken`          | `string\|null` | Short-lived payment token                                      |
| `$paymentTokenExpiresAt` | `string\|null` | ISO 8601 expiry timestamp                                      |
| `$raw`                   | `array`        | Full raw API response                                          |

```php
$response->isPending();    // true
$response->isCompleted();  // false
```

### Poll Payment Status

Use this to check the current state of a payment without waiting for a webhook:

```php
$status = $zakhir->getPaymentStatus($referenceId);

if ($status->isCompleted()) {
    // Mark your order as paid
}

if ($status->isRejected()) {
    // Notify the customer
}
```

`PaymentStatusResponse` properties:

| Property       | Type     | Description                          |
| -------------- | -------- | ------------------------------------ |
| `$referenceId` | `string` | Your original referenceId            |
| `$status`      | `string` | `PENDING` / `COMPLETED` / `REJECTED` |
| `$id`          | `string` | Zakhir's payment ID                  |
| `$raw`         | `array`  | Full raw API response                |

```php
$status->isPending();    // bool
$status->isCompleted();  // bool
$status->isRejected();   // bool
```

### Cancel a Payment

Cancel a `PENDING` payment that has no transaction attached yet:

```php
$result = $zakhir->cancelPayment($referenceId);
```

Returns the raw response array from the Zakhir API.

### Using the Facade

All methods are also available via the `Zakhir` facade:

```php
use Zakhir\LaravelZakhir\Facades\Zakhir;

$response = Zakhir::createPayment(250.00, 'SDG', 'Order #1024');

$status = Zakhir::getPaymentStatus($referenceId);

Zakhir::cancelPayment($referenceId);
```

### Dependency Injection

Inject `ZakhirPaymentService` directly into your controllers or services:

```php
use Zakhir\LaravelZakhir\ZakhirPaymentService;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly ZakhirPaymentService $zakhir,
    ) {}

    public function pay(Order $order)
    {
        $response = $this->zakhir->createPayment(
            amount: $order->total,
            currency: 'SDG',
            note: "Order #{$order->id}",
        );

        // Store the referenceId so you can look it up later
        $order->update(['zakhir_reference_id' => $response->referenceId]);

        return redirect($response->checkoutUrl);
    }
}
```

---

## Webhook Handling

The package registers a webhook endpoint automatically at:

```
POST /api/zakhir/webhook
```

The route prefix is configurable via `ZAKHIR_ROUTE_PREFIX`. To disable the built-in route entirely and register your own, set:

```env
ZAKHIR_ROUTES_ENABLED=false
```

Then point to your own controller that resolves `ZakhirWebhookController` or implements the same logic.

### Registering a Payment

Before Zakhir's webhook can update a payment, you must create a `ZakhirPayment` record **after** calling `createPayment`. This record is the package's local representation of the payment:

```php
use Zakhir\LaravelZakhir\Models\ZakhirPayment;

$response = Zakhir::createPayment(250.00, 'SDG', "Order #{$order->id}");

ZakhirPayment::create([
    'transaction_id'    => 'zakhir-pending-' . $response->referenceId,
    'reference_id'      => $response->referenceId,
    'gateway_reference' => $response->id,
    'payable_id'        => $order->id,
    'payable_type'      => Order::class,
    'amount'            => 25000,     // store in piasters (SDG × 100)
    'currency'          => 'SDG',
    'status'            => 'PENDING',
]);
```

When Zakhir sends a `COMPLETED` webhook, the controller updates the record atomically (row-level lock, idempotency guard) and dispatches `ZakhirPaymentCompleted`.

### Listening to Events

Register listeners in your `EventServiceProvider` (or using `#[AsEventListener]`):

```php
use Zakhir\LaravelZakhir\Events\ZakhirPaymentCompleted;
use Zakhir\LaravelZakhir\Events\ZakhirPaymentFailed;
use Zakhir\LaravelZakhir\Events\ZakhirWebhookReceived;

// AppServiceProvider or EventServiceProvider
Event::listen(ZakhirPaymentCompleted::class, function ($event) {
    $payment = $event->payment; // ZakhirPayment model (already COMPLETED)

    $order = Order::find($payment->payable_id);
    $order->markAsPaid();
    $order->customer->notify(new OrderConfirmed($order));
});

Event::listen(ZakhirPaymentFailed::class, function ($event) {
    // $event->payload      → WebhookPayload DTO
    // $event->localPayableId   → your model's ID
    // $event->localPayableType → your model's class
});

Event::listen(ZakhirWebhookReceived::class, function ($event) {
    // Fired for every webhook regardless of status — useful for raw auditing
    // $event->payload → WebhookPayload DTO
});
```

Or use a dedicated listener class:

```php
class HandleZakhirPayment
{
    public function handle(ZakhirPaymentCompleted $event): void
    {
        $payment = $event->payment;
        // ...
    }
}
```

### Webhook Security

If you set `ZAKHIR_WEBHOOK_SECRET`, the `VerifyZakhirWebhookSignature` middleware validates every incoming webhook using HMAC-SHA256:

```
X-Zakhir-Signature: sha256=<hmac_hex>
```

If the secret is empty the middleware is a no-op — no signature check is performed. To enforce it, always configure the secret in production.

---

## Database

### Migrations

Two tables are created:

| Table             | Purpose                                                             |
| ----------------- | ------------------------------------------------------------------- |
| `zakhir_payments` | One row per payment attempt; tracks status, amount, and raw payload |
| `zakhir_logs`     | Append-only audit log of every outgoing API request/response        |

Migrations are loaded automatically. To publish them instead:

```bash
php artisan vendor:publish --tag=zakhir-migrations
```

### ZakhirPayment Model

`Zakhir\LaravelZakhir\Models\ZakhirPayment`

| Column              | Type              | Description                                   |
| ------------------- | ----------------- | --------------------------------------------- |
| `id`                | `bigint`          | Auto-increment primary key                    |
| `transaction_id`    | `string`          | Unique internal ID — format `zakhir-{seed}`   |
| `gateway_reference` | `string\|null`    | Zakhir's own payment ID                       |
| `reference_id`      | `string`          | UUID sent as `referenceId` in the API request |
| `payable_id`        | `int`             | ID of the related local model                 |
| `payable_type`      | `string`          | Class of the related local model              |
| `amount`            | `bigint`          | Amount in smallest unit (piasters for SDG)    |
| `currency`          | `string(3)`       | ISO currency code, e.g. `SDG`                 |
| `status`            | `string`          | `PENDING` / `COMPLETED` / `FAILED`            |
| `raw_payload`       | `json\|null`      | Full webhook or API response payload          |
| `paid_at`           | `timestamp\|null` | When the payment completed                    |

**Polymorphic relation** — attach payments to any Eloquent model:

```php
// On your Invoice / Order model
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Zakhir\LaravelZakhir\Models\ZakhirPayment;

public function zakhirPayments(): MorphMany
{
    return $this->morphMany(ZakhirPayment::class, 'payable');
}
```

```php
$completedPayments = $order->zakhirPayments()->where('status', 'COMPLETED')->get();
```

### ZakhirLog Model

`Zakhir\LaravelZakhir\Models\ZakhirLog`

Every outgoing API call is recorded automatically when `ZAKHIR_LOGGING=true`:

| Column          | Type           | Description                     |
| --------------- | -------------- | ------------------------------- |
| `id`            | `bigint`       | Auto-increment primary key      |
| `direction`     | `string`       | `outgoing` or `incoming`        |
| `method`        | `string`       | HTTP verb                       |
| `url`           | `string`       | Full endpoint URL               |
| `ip`            | `string\|null` | Client IP (incoming only)       |
| `status_code`   | `smallint`     | HTTP status code                |
| `request_body`  | `json\|null`   | Request payload                 |
| `response_body` | `json\|null`   | Response payload                |
| `duration_ms`   | `int`          | Round-trip time in milliseconds |
| `created_at`    | `timestamp`    | Log timestamp                   |

> Logging failures are silently swallowed — a broken log table will never block a payment.

---

## Events Reference

| Event                    | When                                        | Payload                                                                      |
| ------------------------ | ------------------------------------------- | ---------------------------------------------------------------------------- |
| `ZakhirWebhookReceived`  | Every incoming webhook                      | `WebhookPayload $payload`                                                    |
| `ZakhirPaymentCompleted` | Webhook `status=COMPLETED`, after DB update | `ZakhirPayment $payment`                                                     |
| `ZakhirPaymentFailed`    | Webhook `status=REJECTED`, after DB update  | `WebhookPayload $payload`, `int $localPayableId`, `string $localPayableType` |

### `WebhookPayload` DTO

```php
$payload->id;            // string — Zakhir's payment ID
$payload->referenceId;   // string — your original referenceId
$payload->status;        // PaymentStatus enum
$payload->raw;           // array — full raw webhook body
```

### `PaymentStatus` Enum

```php
use Zakhir\LaravelZakhir\Enums\PaymentStatus;

PaymentStatus::Pending;    // 'PENDING'
PaymentStatus::Completed;  // 'COMPLETED'
PaymentStatus::Rejected;   // 'REJECTED'
PaymentStatus::Unknown;    // 'UNKNOWN'

$status->isTerminal();     // true for Completed and Rejected
```

---

## Exception Handling

All package exceptions extend `ZakhirException` (which extends `RuntimeException`):

| Exception                | Thrown when                                                          |
| ------------------------ | -------------------------------------------------------------------- |
| `ZakhirException`        | Base class — gateway disabled, missing config, invalid response      |
| `ZakhirApiException`     | Zakhir API returns a non-2xx HTTP response                           |
| `ZakhirWebhookException` | Webhook payload is missing a required field or fails signature check |

```php
use Zakhir\LaravelZakhir\Exceptions\ZakhirException;
use Zakhir\LaravelZakhir\Exceptions\ZakhirApiException;

try {
    $response = Zakhir::createPayment(250.00, 'SDG', 'Order #1024');
} catch (ZakhirApiException $e) {
    // HTTP-level error from the Zakhir API
    logger()->error('Zakhir API error', [
        'status'   => $e->statusCode,
        'body'     => $e->responseBody,
        'message'  => $e->getMessage(),
    ]);
} catch (ZakhirException $e) {
    // Configuration issue or invalid response
    logger()->error('Zakhir error: ' . $e->getMessage());
}
```

`ZakhirApiException` exposes two read-only properties:

```php
$e->statusCode;    // int  — HTTP status code (401, 422, 500, …)
$e->responseBody;  // array — decoded JSON response body
```

---

## Architecture Overview

```
src/
├── ZakhirServiceProvider.php          Auto-discovery, DI bindings, routes, migrations
├── ZakhirPaymentService.php           Public API — createPayment / getPaymentStatus / cancelPayment
│
├── Contracts/
│   └── ZakhirClientInterface.php      Interface for the HTTP client (swap or mock in tests)
│
├── Http/
│   ├── ZakhirConfig.php               Reads config; handles prod/staging switching
│   ├── ZakhirClient.php               HTTP layer — all Zakhir API calls + logging
│   ├── Controllers/
│   │   └── ZakhirWebhookController.php  Processes COMPLETED / REJECTED webhooks
│   └── Middleware/
│       └── VerifyZakhirWebhookSignature.php  Optional HMAC-SHA256 guard
│
├── Data/                              Typed DTOs — no raw arrays leaking across boundaries
│   ├── CreatePaymentData.php
│   ├── PaymentResponse.php
│   ├── PaymentStatusResponse.php
│   └── WebhookPayload.php
│
├── Enums/
│   └── PaymentStatus.php              PENDING / COMPLETED / REJECTED / UNKNOWN
│
├── Events/
│   ├── ZakhirWebhookReceived.php
│   ├── ZakhirPaymentCompleted.php
│   └── ZakhirPaymentFailed.php
│
├── Exceptions/
│   ├── ZakhirException.php
│   ├── ZakhirApiException.php
│   └── ZakhirWebhookException.php
│
├── Facades/
│   └── Zakhir.php
│
├── Models/
│   ├── ZakhirPayment.php              Polymorphic payment record
│   └── ZakhirLog.php                 Append-only API audit log
│
└── Support/
    └── ZakhirLogger.php               Writes to zakhir_logs; silently skips on DB failure
```

**Key design decisions:**

- **Idempotent webhooks** — every status update runs inside a `DB::transaction()` with `lockForUpdate()`, so replayed or concurrent webhooks are safe.
- **Polymorphic `ZakhirPayment`** — attach payments to any Eloquent model (Order, Invoice, Subscription…) without modifying the package.
- **Events over tight coupling** — the package fires events; your application decides what to do.
- **Logging never crashes** — `ZakhirLogger` catches all exceptions internally so a broken `zakhir_logs` table can never block a live payment.
- **Interface-bound client** — `ZakhirClientInterface` lets you swap or mock the HTTP client cleanly in tests.

---

## Testing

The package ships with a full PHPUnit suite using [Orchestra Testbench](https://github.com/orchestral/testbench).

```bash
composer install
./vendor/bin/phpunit
```

In your own application, use Laravel's `Http::fake()` to mock Zakhir API calls without hitting the real gateway:

```php
use Illuminate\Support\Facades\Http;
use Zakhir\LaravelZakhir\Facades\Zakhir;

Http::fake([
    '*/payments' => Http::response([
        'id'          => 'zakhir-id-001',
        'referenceId' => 'test-uuid',
        'status'      => 'PENDING',
        'checkoutPage' => [
            'url' => 'https://zakhir.net/pay/test',
        ],
    ], 200),
]);

$response = Zakhir::createPayment(100.00, 'SDG', 'Test payment');

$this->assertEquals('PENDING', $response->status);
$this->assertNotEmpty($response->checkoutUrl);
```

To test webhook handling, use `ZakhirPaymentCompleted` with `Event::fake()`:

```php
use Illuminate\Support\Facades\Event;
use Zakhir\LaravelZakhir\Events\ZakhirPaymentCompleted;
use Zakhir\LaravelZakhir\Models\ZakhirPayment;

Event::fake();

ZakhirPayment::create([
    'transaction_id' => 'zakhir-pending-ref-001',
    'reference_id'   => 'ref-001',
    'payable_id'     => 1,
    'payable_type'   => Order::class,
    'amount'         => 10000,
    'currency'       => 'SDG',
    'status'         => 'PENDING',
]);

$this->postJson('/api/zakhir/webhook', [
    'id'          => 'gw-id-001',
    'referenceId' => 'ref-001',
    'status'      => 'COMPLETED',
])->assertOk();

Event::assertDispatched(ZakhirPaymentCompleted::class);

$this->assertDatabaseHas('zakhir_payments', [
    'reference_id' => 'ref-001',
    'status'       => 'COMPLETED',
]);
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a full history of releases and changes.

---

## Credits

|                     |                                           |
| ------------------- | ----------------------------------------- |
| **Package Author**  | [Abdalrahman Molood](https://amolood.com) |
| **Company**         | [Digitalize Lab](https://digitalize.sd)   |
| **Payment Gateway** | [Zakhir](https://zakhir.net/)             |

Contributions, issues, and pull requests are welcome.

---

## License

This package is open-source software licensed under the [MIT License](LICENSE).
