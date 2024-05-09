<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Utils;

class Helpers
{
    /**
     * Get a byte value in human-readable format e.g., 1024 --> 1kb.
     *
     * @param int $bytes Number of bytes
     * @param int $decimals Number of decimal places (precision)
     *
     * @return string Human-readable size
     */
    public static function humanReadableFileSize(int $bytes, int $decimals = 2): string
    {
        if ($bytes === 0) {
            return 'None';
        }

        $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = (int) floor((strlen((string)$bytes) - 1) / 3);

        if ($factor === 0 || $decimals < 0) {
            $decimals = 0;
        }

        return sprintf("%.{$decimals}f %s", $bytes / (1024 ** $factor), $size[$factor]);
    }
}
