# Signit PHP SDK

A standalone, framework-independent PHP client for the Signit v1 e-signature API. It handles OAuth client credentials, document upload, embedded signature requests, request status, signatory lookup, signing links, and sealed document URLs. Booking models, PDF generation, persistence, and business rules remain the responsibility of the integrating application.

## Requirements

- PHP 7.1 or later
- ext-curl and ext-json

## Install

Once published to Packagist:

```sh
composer require ahmedibrahimyassindev/signit-php-sdk
```

For local development in this repository, add a Composer path repository pointing to `packages/signit-php-sdk` and require `ahmedibrahimyassindev/signit-php-sdk`.

## Usage

```php
use Signit\SignitClient;

$signit = new SignitClient(
    getenv('SIGN_IT_CLIENT_ID'),
    getenv('SIGN_IT_CLIENT_SECRET'),
    getenv('SIGN_IT_BASE_URL'),
    getenv('SIGN_IT_CLIENT_ID') // appended to embedded signing links as client_id
);

$documentName = $signit->uploadDocument($pdfPath, 'booking.pdf');
$request = $signit->createEmbeddedSignatureRequest($documentName, [
    'title' => 'Booking agreement',
    'is_embedded' => true,
    'signatories' => [[
        'full_name' => 'Example Signer',
        'order' => 0,
        'verification_method' => ['email' => 'signer@example.com'],
        'notification_method' => ['email' => 'signer@example.com'],
        'fields' => [[
            'position' => ['page' => 1, 'x' => 140, 'y' => 50, 'height' => 50, 'width' => 100],
            'properties' => ['required' => true],
            'placeholder' => 'Sign here',
            'kind' => 'signature',
        ]],
    ]],
]);

$requestId = $request['id'];
$signatoryId = $signit->getSignatoryId($requestId);
$signingUrl = $signit->getSigningUrl($requestId, $signatoryId);

// Later: $signit->getSignatureStatus($requestId);
// Later: $sealedFileUrl = $signit->getSealedFileUrl($requestId);
```

Set the API base URL and credentials from environment-specific configuration. The SDK does not read environment variables itself, depend on Yii, or log request bodies and credentials.

## YaSchools booking flow

`DirectBookingController::actionRequireSign` validates the active booking and signing eligibility, reuses an existing request when possible, otherwise delegates request creation to the booking model's `createSignItRequest()`, then renders the embedded signing URL. `common/helpers/SignItHelper.php` owns the Yii-specific PDF, booking, signature-record, and notification workflow. This SDK packages the reusable Signit HTTP/OAuth operations called by that workflow; it intentionally has no dependency on Yii or school booking models.

API errors throw `Signit\Exceptions\SignitException`, which exposes `getHttpStatus()` and `getResponseBody()`. Access tokens are cached in the client instance until shortly before expiry.
