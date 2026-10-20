<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Http;

/**
 * The bare HTTP answer the client needs: a status code and a body.
 */
final class Response
{
    /** @var int */
    private $statusCode;

    /** @var string */
    private $body;

    public function __construct(int $statusCode, string $body)
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
