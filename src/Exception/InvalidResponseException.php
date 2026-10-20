<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Exception;

/**
 * The server answered with a body the client could not parse.
 */
class InvalidResponseException extends ElibriException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 1002);
    }
}
