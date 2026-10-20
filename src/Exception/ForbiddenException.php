<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Exception;

/**
 * The credentials are valid but the operation is not allowed (HTTP 403).
 *
 * The message carries the server's explanation.
 */
class ForbiddenException extends ElibriException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 403);
    }
}
