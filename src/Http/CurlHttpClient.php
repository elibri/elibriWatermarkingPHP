<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Http;

use Elibri\Watermarking\Exception\ApiConnectionException;

/**
 * Default transport, built on the cURL extension.
 */
final class CurlHttpClient implements HttpClient
{
    public const DEFAULT_TIMEOUT = 30;
    public const DEFAULT_CONNECT_TIMEOUT = 10;

    /** @var int */
    private $timeout;

    /** @var int */
    private $connectTimeout;

    /** @var bool */
    private $verifySsl;

    /**
     * @param int  $timeout        seconds allowed for the whole request, 0 for no limit
     * @param int  $connectTimeout seconds allowed for establishing the connection
     * @param bool $verifySsl      verify the server certificate; only turn this off
     *                             against a test server with a self-signed certificate
     */
    public function __construct(
        int $timeout = self::DEFAULT_TIMEOUT,
        int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        bool $verifySsl = true
    ) {
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->verifySsl = $verifySsl;
    }

    public function request(string $method, string $url, array $params): Response
    {
        $isPost = strtoupper($method) === 'POST';
        $query = http_build_query($params, '', '&');

        if (!$isPost && $query !== '') {
            $url .= '?' . $query;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new ApiConnectionException('Could not initialize cURL', 0);
        }

        try {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifySsl ? 2 : 0);

            if ($isPost) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
            }

            $body = curl_exec($ch);
            if ($body === false) {
                throw new ApiConnectionException(curl_error($ch), curl_errno($ch));
            }

            return new Response((int) curl_getinfo($ch, CURLINFO_HTTP_CODE), (string) $body);
        } finally {
            curl_close($ch);
        }
    }
}
