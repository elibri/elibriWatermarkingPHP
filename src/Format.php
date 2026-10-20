<?php

declare(strict_types=1);

namespace Elibri\Watermarking;

/**
 * The file formats elibri is able to watermark.
 */
final class Format
{
    public const EPUB = 'epub';
    public const MOBI = 'mobi';
    public const PDF = 'pdf';
    public const MP3_IN_ZIP = 'mp3_in_zip';
    public const MP3_IN_LPF = 'mp3_in_lpf';

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::EPUB,
            self::MOBI,
            self::PDF,
            self::MP3_IN_ZIP,
            self::MP3_IN_LPF,
        ];
    }

    public static function isValid(string $format): bool
    {
        return \in_array($format, self::all(), true);
    }

    private function __construct()
    {
    }
}
