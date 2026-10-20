<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Exception;

/**
 * The HTTP request never reached the server (DNS, TLS, timeout, ...).
 *
 * The exception code is the underlying cURL error number.
 */
class ApiConnectionException extends ElibriException
{
}
