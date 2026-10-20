<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Http;

/**
 * Transport used to talk to elibri.
 *
 * Implement this to route requests through your own HTTP stack (Guzzle, a PSR-18
 * client, a recording double in tests) instead of the bundled cURL transport.
 */
interface HttpClient
{
    /**
     * Send a form-encoded request and return the raw answer.
     *
     * @param string               $method GET or POST
     * @param string               $url    absolute URL, without a query string
     * @param array<string,string> $params sent as a query string for GET, as a form body for POST
     *
     * @throws \Elibri\Watermarking\Exception\ApiConnectionException if the server cannot be reached
     */
    public function request(string $method, string $url, array $params): Response;
}
