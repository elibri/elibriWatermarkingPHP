<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Exception;

/**
 * The server answered with a status code the client does not know about.
 */
class UnknownException extends ElibriException
{
    public function __construct(int $statusCode = 0)
    {
        parent::__construct(
            $statusCode > 0
                ? sprintf('Unexpected HTTP status code: %d', $statusCode)
                : 'Unknown error',
            1000
        );
    }
}
