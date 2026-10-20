<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Exception;

/**
 * The server rejected the request parameters (HTTP 400).
 *
 * The message carries the server's explanation.
 */
class ParametersException extends ElibriException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 400);
    }
}
