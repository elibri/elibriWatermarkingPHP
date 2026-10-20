<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Exception;

/**
 * The request signature was already stale when it arrived (HTTP 408).
 *
 * Almost always means the local clock drifted away from real time.
 */
class RequestExpiredException extends ElibriException
{
    public function __construct()
    {
        parent::__construct('Request Expired - check the local clock', 408);
    }
}
