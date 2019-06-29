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
    public const UNIX_DIRECTORY_SEPARATOR = '/';

    /**
     * Directory separator in the Windows
     */
    public const WINDOWS_DIRECTORY_SEPARATOR = '\\';

    /**
     * Returns whether the path is an absolute path
     *
     * @param string $path A path
     *
     * @return bool
     */
    public static function isAbsolutePath(string $path): bool
    {
        return StringType::startsWith($path, [self::UNIX_DIRECTORY_SEPARATOR, self::WINDOWS_DIRECTORY_SEPARATOR]);
    }

    /**
     * Join paths and correct separators count
     *
     * Example:
     * <code>
     * FileSystem::joinPaths('a/', '/b/', '/c', 'd'); // Return a/b/c/d
     * </code>
     *
     * @param string[] $paths Paths
     *
     * @return string Path
     */
    public static function joinPaths(...$paths): string
    {
        return str_replace(
            [self::UNIX_DIRECTORY_SEPARATOR, self::WINDOWS_DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR],
            DIRECTORY_SEPARATOR,
            implode(
                DIRECTORY_SEPARATOR,
                $paths
            )
        );
    }

    /**
     * Corrects the path separators
     *
     * Replace the separators in the path to the system suitable separators
     *
     * @param string $path The path
     * @param string $separator The separator [optional]
     *
     * @return string
     */
    public static function correctPathSeparators(string $path, string $separator = DIRECTORY_SEPARATOR): string
    {
        return str_replace([self::WINDOWS_DIRECTORY_SEPARATOR, self::UNIX_DIRECTORY_SEPARATOR], $separator, $path);
    }
}