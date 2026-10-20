# Changelog

## 1.0.0

Modernisation release. Not backward compatible with the 0.x series; see
"Upgrading from 0.x" in the README.

### Added

* Composer package `elibri/watermarking-client`, with PSR-4 autoloading.
* `Elibri\Watermarking\Format` constants for the supported file formats;
  `watermark()` now accepts an array of formats as well as a comma separated string.
* `Elibri\Watermarking\Http\HttpClient` transport interface, so requests can be
  routed through another HTTP stack or stubbed out in tests. The default
  `CurlHttpClient` takes a request timeout, a connect timeout and a TLS
  verification flag.
* A PHPUnit test suite covering parameter handling, request signing and the full
  status-code-to-exception mapping, plus a GitHub Actions matrix over PHP 7.4-8.4.
* `InvalidResponseException` for bodies that cannot be parsed.

### Changed

* Everything is namespaced under `Elibri\Watermarking`;
  `ElibriWatermarkingClient` is now `WatermarkingClient`.
* Exceptions moved to `Elibri\Watermarking\Exception\`, dropped their `Elibri`
  prefix and now all extend `ElibriException`, so a single catch suffices.
  `ElibriParametersError` → `ParametersException`,
  `ElibriNoServerResponsingException` → `NoServerRespondingException`,
  `ElibriAPIConnectionException` → `ApiConnectionException`.
* Methods are camelCase: `available_files()` → `availableFiles()`,
  `soon_available_files()` → `soonAvailableFiles()`,
  `soon_unavailable_files()` → `soonUnavailableFiles()`.
* Format validation is strict: each format is checked individually, so values
  like `epubmobi` or a bare `,` are rejected instead of being passed to the server.
  Whitespace and letter case are normalised and duplicates collapsed.
* TLS certificates are verified. 0.x disabled both peer and host verification.
* Requests time out (30s total, 10s connect) instead of hanging indefinitely.
* `NoServerRespondingException` keeps the underlying connection or server error
  as its previous exception, rather than discarding it.
* Empty identifiers and transaction ids are rejected locally with
  `ParametersException`.
* Documentation sources moved to `doxygen/mainpage.dox`; generated HTML is no
  longer committed.

### Fixed

* `watermark()` was unusable: a missing semicolon made the file a parse error.

### Removed

* PEAR packaging (`pearfarm.spec`) and the `elibriWatermarkingPHP.php` bootstrap,
  including its `date_default_timezone_set()` side effect.
