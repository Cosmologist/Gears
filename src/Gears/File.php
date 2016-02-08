<?php

namespace Cosmologist\Gears;

/**
 * Collection of commonly used methods for working with files and paths
 */
class File
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
     * File::joinPaths('a/', '/b/', '/c', 'd'); // Return a/b/c/d
     * </code>
     *
     * @param array  $paths Paths
     * @param string $directorySeparator Final directory separator
     *
     * @return string Path
     */
    public static function joinPaths(array $paths, $directorySeparator=self::UNIX_DIRECTORY_SEPARATOR)
    {
        return preg_replace('#' . preg_quote($directorySeparator) . '+#', $directorySeparator,
            implode($directorySeparator, $paths));
    }
}