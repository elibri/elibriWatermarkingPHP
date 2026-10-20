<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Exception;

/**
 * The server has no such endpoint (HTTP 404).
 */
class NotFoundException extends ElibriException
{
    public function __construct()
    {
        parent::__construct('Invalid url or http method', 404);
    }
}
