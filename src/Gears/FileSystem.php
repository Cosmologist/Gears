<?php

namespace Cosmologist\Gears;

/**
 * Collection of commonly used methods for working with filesystem
 */
class FileSystem
{
    /**
     * Directory separator in the *nix systems
     */
    const UNIX_DIRECTORY_SEPARATOR = '/';

    /**
     * Directory separator in the Windows
     */
    const WINDOWS_DIRECTORY_SEPARATOR = '\\';

    /**
     * Join paths and correct separators count
     *
     * Example:
     * <code>
     * FileSystem::joinPaths('a/', '/b/', '/c', 'd'); // Return a/b/c/d
     * </code>
     *
     * @param array  $paths Paths
     * @param string $directorySeparator Final directory separator
     *
     * @return string Path
     */
    public static function joinPaths(array $paths, $directorySeparator=DIRECTORY_SEPARATOR)
    {
        return preg_replace('#' . preg_quote($directorySeparator) . '+#', $directorySeparator,
            implode($directorySeparator, $paths));
    }

    /**
     * Normalize path separators
     *
     * Function replace path separators by separator specific for the current OS
     *
     * @param string $path The file path
     *
     * @return string
     */
    public static function normalizeSeparators($path)
    {
        $incorrectSeparator = (DIRECTORY_SEPARATOR === self::UNIX_DIRECTORY_SEPARATOR) ?
            self::WINDOWS_DIRECTORY_SEPARATOR : self::UNIX_DIRECTORY_SEPARATOR;

        return str_replace($incorrectSeparator, DIRECTORY_SEPARATOR, $path);
    }
}