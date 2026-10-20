<?php

declare(strict_types=1);

namespace Elibri\Watermarking;

/**
 * Builds the `sig` parameter that authenticates a request against elibri.
 *
 * The value is percent-encoded here and percent-encoded a second time when the
 * request is form-encoded on the wire. That double encoding is what the elibri
 * server expects, so do not "fix" it.
 */
final class RequestSigner
{
    /** @var string */
    private $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function sign(int $stamp): string
    {
        return rawurlencode(base64_encode(
            hash_hmac('sha1', $this->secret, (string) $stamp, true)
        ));
    }
}
