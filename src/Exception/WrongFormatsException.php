<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Exception;

use Elibri\Watermarking\Format;

/**
 * The requested file formats are not ones elibri can watermark.
 */
class WrongFormatsException extends ElibriException
{
    public function __construct(string $formats = '')
    {
        parent::__construct(
            sprintf(
                "Wrong format%s, allowed: '%s'",
                $formats === '' ? '' : sprintf(" '%s'", $formats),
                implode("', '", Format::all())
            ),
            1000
        );
    }
}
