# Signit PHP SDK

A framework-independent PHP client for the Signit v1 e-signature API, with optional Laravel integration. It supports OAuth client credentials, document upload, embedded signature requests, request status, signatory lookup, signing links, and sealed document URLs.

## Requirements

- PHP 7.1 or later
- ext-curl and ext-json

## Install

After the package is available on Packagist, install it with Composer:

```sh
composer require ahmedibrahimyassindev/signit-php-sdk
```

The package can also be installed directly from its Git repository before its first Packagist release:

```sh
composer config repositories.signit vcs https://github.com/ahmedibrahimyassindev/signit-php-sdk
composer require ahmedibrahimyassindev/signit-php-sdk:dev-main
```

## Laravel setup

Laravel discovers and registers the service provider automatically. Publish the configuration file:

```sh
php artisan vendor:publish --tag=signit-config
```

Set the credentials and API URL in your Laravel application's `.env` file:

```dotenv
SIGN_IT_CLIENT_ID=your-client-id
SIGN_IT_CLIENT_SECRET=your-client-secret
SIGN_IT_BASE_URL=https://your-signit-api-base-url
SIGN_IT_TIMEOUT=30
```

The published `config/signit.php` maps these environment values to the SDK client. Keep credentials in your deployment environment and out of source control. Resolve the client through Laravel's container:

```php
use Signit\SignitClient;

class ContractController
{
    public function create(SignitClient $signit)
    {
        // Use the configured Signit client.
    }
}
```

## Plain PHP usage

The SDK does not load environment variables or framework configuration by itself. Read configuration through your application's preferred mechanism and pass the values to the client:

```php
use Signit\SignitClient;

$signit = new SignitClient(
    getenv('SIGN_IT_CLIENT_ID'),
    getenv('SIGN_IT_CLIENT_SECRET'),
    getenv('SIGN_IT_BASE_URL'),
    getenv('SIGN_IT_CLIENT_ID'), // Optional client_id query parameter for signing links.
    30 // Request timeout in seconds.
);

$documentName = $signit->uploadDocument($pdfPath, 'contract.pdf');
$request = $signit->createEmbeddedSignatureRequest($documentName, [
    'title' => 'Contract',
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
$status = $signit->getSignatureStatus($requestId);
$sealedFileUrl = $signit->getSealedFileUrl($requestId);
```

The client caches its OAuth access token in memory until shortly before it expires. API errors throw `Signit\Exceptions\SignitException`, which provides `getHttpStatus()` and `getResponseBody()`.
