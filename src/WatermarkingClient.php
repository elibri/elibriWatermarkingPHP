<?php

declare(strict_types=1);

namespace Elibri\Watermarking;

use Elibri\Watermarking\Exception\ApiConnectionException;
use Elibri\Watermarking\Exception\ForbiddenException;
use Elibri\Watermarking\Exception\InvalidAuthException;
use Elibri\Watermarking\Exception\InvalidResponseException;
use Elibri\Watermarking\Exception\NoServerRespondingException;
use Elibri\Watermarking\Exception\NotFoundException;
use Elibri\Watermarking\Exception\ParametersException;
use Elibri\Watermarking\Exception\RequestExpiredException;
use Elibri\Watermarking\Exception\ServerErrorException;
use Elibri\Watermarking\Exception\UnknownException;
use Elibri\Watermarking\Exception\WrongFormatsException;
use Elibri\Watermarking\Http\CurlHttpClient;
use Elibri\Watermarking\Http\HttpClient;
use Elibri\Watermarking\Http\Response;

/**
 * Client for the elibri watermarking and transactional API.
 *
 * Watermarking a book is a two step process. Call {@see watermark()} as early as
 * you can - when the customer leaves the basket, for instance - so elibri can
 * start marking the file while the payment is being made. Nothing is charged and
 * no transaction is registered at that point. Once the payment is confirmed, call
 * {@see deliver()}; elibri then registers the transaction and pings your callback
 * URL with links to the finished files. A transaction that is never delivered is
 * cancelled after seven days and nobody is billed.
 *
 * @see https://www.elibri.com.pl/doc/watermarking/
 */
final class WatermarkingClient
{
    public const DEFAULT_HOST = 'https://www.elibri.com.pl';

    /** @var string */
    private $token;

    /** @var RequestSigner */
    private $signer;

    /** @var string */
    private $host;

    /** @var HttpClient */
    private $httpClient;

    /**
     * @param string          $token      public token of the elibri watermarking API
     * @param string          $secret     private token of the elibri watermarking API
     * @param string          $host       API host, override only to target a test server
     * @param HttpClient|null $httpClient transport to use; defaults to cURL
     */
    public function __construct(
        string $token,
        string $secret,
        string $host = self::DEFAULT_HOST,
        ?HttpClient $httpClient = null
    ) {
        $this->token = $token;
        $this->signer = new RequestSigner($secret);
        $this->host = rtrim($host, '/');
        $this->httpClient = $httpClient ?? new CurlHttpClient();
    }

    /**
     * Order the watermarking of a book, without registering a transaction.
     *
     * @param string          $ident            ISBN13 (digits only) or a record reference
     * @param string|string[] $formats          one or more of the {@see Format} constants,
     *                                          as an array or a comma separated string
     * @param string          $visibleWatermark footer appended to the end of every chapter
     * @param string|null     $clientSymbol     optional promotion identifier
     *
     * @return string alphanumeric transaction identifier, to pass to {@see deliver()}
     *
     * @throws WrongFormatsException        if a requested format cannot be watermarked
     * @throws NoServerRespondingException  if elibri gives no usable answer
     * @throws Exception\ElibriException    on any other API failure
     */
    public function watermark(
        string $ident,
        $formats,
        string $visibleWatermark,
        ?string $clientSymbol = null
    ): string {
        if (trim($ident) === '') {
            throw new ParametersException('Book identifier must not be empty');
        }

        $identType = preg_match('/^\d{13}$/', $ident) ? 'isbn' : 'record_reference';

        $data = [
            $identType => $ident,
            'formats' => $this->normalizeFormats($formats),
            'visible_watermark' => $visibleWatermark,
        ];

        if ($clientSymbol !== null && $clientSymbol !== '') {
            $data['client_symbol'] = $clientSymbol;
        }

        return $this->sendRequest('POST', 'watermark', $data);
    }

    /**
     * Register the transaction and deliver the watermarked files.
     *
     * Call this only once the payment is confirmed: the publisher invoices the
     * shop for every delivered transaction. Must happen within 7 days of the
     * matching {@see watermark()} call.
     *
     * @param string $transId transaction identifier returned by {@see watermark()}
     *
     * @throws Exception\ElibriException on any API failure
     */
    public function deliver(string $transId): string
    {
        if (trim($transId) === '') {
            throw new ParametersException('Transaction identifier must not be empty');
        }

        return $this->sendRequest('POST', 'deliver', ['trans_id' => $transId]);
    }

    /**
     * Books available to this account right now.
     *
     * Includes titles whose files are all uploaded but whose release date is still
     * ahead; those carry an `available_date` field.
     *
     * @return array<int,array<string,mixed>>
     *
     * @throws Exception\ElibriException on any API failure
     */
    public function availableFiles(): array
    {
        return $this->getJson('available_files.json');
    }

    /**
     * Books not available yet, but with a release date coming up.
     *
     * @return array<int,array<string,mixed>>
     *
     * @throws Exception\ElibriException on any API failure
     */
    public function soonAvailableFiles(): array
    {
        return $this->getJson('soon_available_files.json');
    }

    /**
     * Books that will stop being available shortly.
     *
     * @return array<int,array<string,mixed>>
     *
     * @throws Exception\ElibriException on any API failure
     */
    public function soonUnavailableFiles(): array
    {
        return $this->getJson('soon_unavailable_files.json');
    }

    /**
     * @param string|string[] $formats
     *
     * @throws WrongFormatsException
     */
    private function normalizeFormats($formats): string
    {
        $requested = \is_array($formats) ? $formats : explode(',', (string) $formats);

        $normalized = [];
        foreach ($requested as $format) {
            $format = strtolower(trim((string) $format));
            if ($format === '') {
                continue;
            }
            if (!Format::isValid($format)) {
                throw new WrongFormatsException($format);
            }
            if (!\in_array($format, $normalized, true)) {
                $normalized[] = $format;
            }
        }

        if ($normalized === []) {
            throw new WrongFormatsException();
        }

        return implode(',', $normalized);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function getJson(string $methodName): array
    {
        $body = $this->sendRequest('GET', $methodName, []);

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidResponseException(
                sprintf('Could not decode the response of %s: %s', $methodName, $e->getMessage())
            );
        }

        if (!\is_array($decoded)) {
            throw new InvalidResponseException(
                sprintf('Expected %s to return a list, got %s', $methodName, \gettype($decoded))
            );
        }

        return $decoded;
    }

    /**
     * @param array<string,string> $data
     */
    private function sendRequest(string $method, string $methodName, array $data): string
    {
        $stamp = time();
        $data['stamp'] = (string) $stamp;
        $data['sig'] = $this->signer->sign($stamp);
        $data['token'] = $this->token;

        $url = $this->host . '/watermarking/' . $methodName;

        try {
            return $this->validateResponse($this->httpClient->request($method, $url, $data));
        } catch (ApiConnectionException $e) {
            // The server could not be reached at all.
            throw new NoServerRespondingException($e);
        } catch (ServerErrorException $e) {
            throw new NoServerRespondingException($e);
        } catch (UnknownException $e) {
            throw new NoServerRespondingException($e);
        }
    }

    private function validateResponse(Response $response): string
    {
        $code = $response->getStatusCode();
        $body = $response->getBody();

        switch ($code) {
            case 200:
            case 202:
            case 412:
                return $body;
            case 400:
                throw new ParametersException($body);
            case 401:
                throw new InvalidAuthException();
            case 403:
                throw new ForbiddenException($body);
            case 404:
                throw new NotFoundException();
            case 408:
                throw new RequestExpiredException();
            case 500:
                throw new ServerErrorException();
            default:
                throw new UnknownException($code);
        }
    }
}
