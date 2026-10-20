<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Exception;

/**
 * Base class for every exception thrown by the elibri watermarking client.
 *
 * Catch this to handle any failure of the library in one place.
 */
class ElibriException extends \RuntimeException
{
}
