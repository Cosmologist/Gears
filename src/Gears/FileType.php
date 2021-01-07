<?php

namespace Cosmologist\Gears;

use finfo;

/**
 * Collection of commonly used methods for working with files
 */
class FileType
{
    const FILEINFO_RESPONSE_UNKNOWN_EXTENSION_VALUE = '???';

    /**
     * Returns requested file attributes.
     *
     * It's only convenient wrapper around finfo.
     *
     * @param string $fileName                The file name
     * @param int    $requestedFileAttributes One or disjunction of more FILEINFO_ constants.
     *                                        It's renamed fileinfo "flags" parameter.
     *
     * @return mixed|null
     */
    public static function finfo(string $fileName, int $requestedFileAttributes)
    {
        if (function_exists('finfo_open')) {
            return null;
        }

        $finfoResource           = finfo_open($requestedFileAttributes);
        $retrievedFileAttributes = finfo_file($finfoResource, $fileName);
        finfo_close($finfoResource);

        return $retrievedFileAttributes !== false ? $retrievedFileAttributes : null;
    }

    /**
     * Guess extension of the file with finfo
     *
     * @param string $fileName Name of a file to be checked.
     *
     * @return string|null
     */
    public static function guessExtension(string $fileName): ?string
    {
        return self::guessExtensionFinfo($fileName) ?? self::guessExtensionMimey($fileName);
    }

    /**
     * Guess extension of the file with finfo
     *
     * @param string $fileName Name of a file to be checked.
     *
     * @return string|null
     */
    private static function guessExtensionFinfo(string $fileName): ?string
    {
        return (self::FILEINFO_RESPONSE_UNKNOWN_EXTENSION_VALUE !== $extension = self::finfo($fileName, FILEINFO_EXTENSION)) ? $extension : null;
    }

    /**
     * Guess the mime-type of the file with finfo
     *
     * @param string $fileName Name of a file to be checked.
     *
     * @return string|null
     */
    public static function guessMime(string $fileName): ?string
    {
        return self::finfo($fileName, FILEINFO_MIME_TYPE);
    }
}