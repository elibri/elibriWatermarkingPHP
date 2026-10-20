<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Exception;

/**
 * The token/secret pair was not accepted (HTTP 401).
 */
class InvalidAuthException extends ElibriException
{
    public function __construct()
    {
        parent::__construct('Unauthorized', 401);
    }
}
