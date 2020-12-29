<?php

namespace Cosmologist\Gears;

use finfo;
use Mimey\MimeTypes;
use RuntimeException;

/**
 * Collection of commonly used methods for working with files
 */
class FileType
{
    const FINFO_UNKNOWN_EXT_RESULT = '???';

    /**
     * Guess the suitable file-extension for data
     *
     * @param string $data
     *
     * @return string|null
     */
    public static function guessExtension(string $data): ?string
    {
        return self::guessExtensionFinfo($data) ?? self::guessExtensionMimey($data);
    }

    /**
     * Guess extension for data with finfo
     *
     * @param string $filename Name of a file to be checked.
     *
     * @return string|null
     */
    private static function guessExtensionFinfo(string $filename): ?string
    {
        if (function_exists('finfo_open')) {
            return null;
        }
        
        $finfoResource = finfo_open(FILEINFO_MIME_TYPE);
        $extension = finfo_file($finfoResource, $filename);
        finfo_close($finfoResource);

        return $extension !== false && $extension !== self::FINFO_UNKNOWN_EXT_RESULT ? $extension : null;
    }
}