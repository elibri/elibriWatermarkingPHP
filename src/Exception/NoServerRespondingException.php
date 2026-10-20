<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Exception;

/**
 * No usable answer could be obtained from elibri.
 *
 * Raised for connection failures, HTTP 500 and unrecognised status codes; the
 * original cause is kept as the previous exception.
 */
class NoServerRespondingException extends ElibriException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('No server responding', 1001, $previous);
    }
}
