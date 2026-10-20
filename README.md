# elibri watermarking client

PHP client for the [elibri](https://www.elibri.com.pl) watermarking and transactional API.

[Full API documentation](https://www.elibri.com.pl/doc/watermarking/) ·
[Library documentation](https://elibri.github.io/elibriWatermarkingPHP/)

## Requirements

* PHP 7.4 or newer
* the `curl` and `json` extensions

## Installation

```bash
composer require elibri/watermarking-client
```

## Usage

Watermarking is a two step process, so the customer waits as little as possible.

1. Call `watermark()` as early as you can — when the customer leaves the basket and
   enters their details, for instance. elibri starts marking the file, but no
   transaction is registered and nothing is charged yet.
2. Once the payment is confirmed, call `deliver()`. elibri registers the transaction
   and pings your callback URL with links to the finished files.

A transaction that is never delivered is cancelled after 7 days, and neither side is
billed. `deliver()` must be called within those 7 days.

```php
use Elibri\Watermarking\Format;
use Elibri\Watermarking\WatermarkingClient;
use Elibri\Watermarking\Exception\ElibriException;

$client = new WatermarkingClient($token, $secret);

try {
    $transId = $client->watermark(
        '9788324631766',                  // ISBN13 (digits only) or a record reference
        [Format::EPUB, Format::MOBI],     // or 'epub,mobi'
        'Książka dla Wojtka Kowalskiego'  // footer appended to every chapter
    );

    // ... after the payment is confirmed:
    $client->deliver($transId);
} catch (ElibriException $e) {
    // every failure of this library derives from ElibriException
}
```

The link to the watermarked file arrives with the
[ping](https://www.elibri.com.pl/doc/watermarking/ping) that announces the finished
transaction.

### Listing products

```php
$client->availableFiles();         // available to your account now
$client->soonAvailableFiles();     // release date coming up
$client->soonUnavailableFiles();   // about to be withdrawn
```

`availableFiles()` also returns titles whose files are all uploaded but whose release
date is still ahead; those carry an `available_date` field. All three respect your
publisher assignments and release dates.

### Formats

`Format::EPUB`, `Format::MOBI`, `Format::PDF`, `Format::MP3_IN_ZIP`,
`Format::MP3_IN_LPF`. Pass them as an array or as a comma separated string.

### Exceptions

Everything thrown by this library extends `Elibri\Watermarking\Exception\ElibriException`.

| Exception | Raised when |
| --- | --- |
| `WrongFormatsException` | a requested format cannot be watermarked (checked locally, before any request) |
| `ParametersException` | a parameter is empty, or the server rejected the request (HTTP 400) |
| `InvalidAuthException` | the token/secret pair was not accepted (HTTP 401) |
| `ForbiddenException` | the operation is not allowed for this account (HTTP 403) |
| `NotFoundException` | no such endpoint (HTTP 404) |
| `RequestExpiredException` | the signature was already stale on arrival — check the local clock (HTTP 408) |
| `InvalidResponseException` | the body could not be parsed |
| `NoServerRespondingException` | elibri gave no usable answer; the cause is kept as the previous exception |

### Configuring the transport

Timeouts and TLS verification live on the cURL transport, which you can replace:

```php
use Elibri\Watermarking\Http\CurlHttpClient;

$client = new WatermarkingClient(
    $token,
    $secret,
    WatermarkingClient::DEFAULT_HOST,
    new CurlHttpClient($timeout = 30, $connectTimeout = 10, $verifySsl = true)
);
```

Implement `Elibri\Watermarking\Http\HttpClient` to route requests through your own
HTTP stack, or to stub them out in tests.

## Upgrading from 0.x

Version 1.0 is a clean break.

* The library is installed with Composer and autoloaded via PSR-4; `require`ing
  `elibriWatermarkingPHP.php` is gone, as is the PEAR channel.
* Classes are namespaced: `ElibriWatermarkingClient` → `Elibri\Watermarking\WatermarkingClient`.
* Exceptions moved to `Elibri\Watermarking\Exception\` and dropped the `Elibri` prefix.
  They all extend `ElibriException`. Renames: `ElibriParametersError` → `ParametersException`,
  `ElibriNoServerResponsingException` → `NoServerRespondingException`,
  `ElibriAPIConnectionException` → `ApiConnectionException`.
* Methods are camelCase: `available_files()` → `availableFiles()`,
  `soon_available_files()` → `soonAvailableFiles()`,
  `soon_unavailable_files()` → `soonUnavailableFiles()`.
* `watermark()` accepts an array of formats, validates each one strictly, and no
  longer accepts the `$title_postfix` / `$client_id` parameters removed in 2024.
  The `retry()` method is gone.
* TLS certificates are now verified, and requests time out instead of hanging
  forever.
* Server errors keep their cause: `NoServerRespondingException::getPrevious()`.

## Development

```bash
composer install
composer test
```

## License

MIT.
