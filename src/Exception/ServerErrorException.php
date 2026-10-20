<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Exception;

/**
 * The server failed while handling the request (HTTP 500).
 */
class ServerErrorException extends ElibriException
{
    public function __construct()
    {
        parent::__construct('Server Error', 500);
    }
}
