<?php

namespace Cosmologist\Gears;

use finfo;

class FileType
{
    /**
     * Directory separator in Unix like systems
     */
    public const string UNIX_DIRECTORY_SEPARATOR = '/';

    /**
     * Directory separator in the Windows operating system
     */
    public const string WINDOWS_DIRECTORY_SEPARATOR = '\\';

    /**
     * Get the extension of a file name
     *
     * <code>
     * FileType::extension('/foo/bar.baz'); // 'baz'
     * FileType::extension('/foo/bar'); // ''
     * </code>
     */
    public static function extension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Write a string to a file and create the file directory recursively if it does not exist
     *
     * <code>
     * FileType::put('/foo/bar.txt', 'baz');
     * </code>
     */
    public static function put(string $filename, string $data, int $mkdirPermissions = 0777): void
    {
        $directory = dirname($filename);

        if (!file_exists($directory) && !mkdir($directory, $mkdirPermissions, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }

        file_put_contents($filename, $data);
    }

    /**
     * Get the path to the file with $name inside the system temporary directory
     *
     * <code>
     * FileType::temporary('foo.txt'); // '/tmp/foo.txt'
     * </code>
     */
    public static function temporary(string $name): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * Determine if the path an absolute path
     *
     * <code>
     * FileType::isAbsolutePath('C:/foo'); true
     * FileType::isAbsolutePath('C:\\bar'); true
     * FileType::isAbsolutePath('foo/bar'); false
     * FileType::isAbsolutePath('/foo/bar'); true
     * FileType::isAbsolutePath('\\foo\\bar'); true
     * </code>
     */
    public static function isAbsolutePath(string $path): bool
    {
        return (bool) preg_match('#^([a-z]:)?[/\\\\]#i', $path);
    }

    /**
     * Join the paths into one and fix the directory separators
     *
     * <code>
     * FileType::joinPaths('a/', '/b/', '\\c', 'd'); // Return a/b/c/d
     * </code>
     */
    public static function joinPaths(string ...$paths): string
    {
        return self::fixPath(implode(DIRECTORY_SEPARATOR, $paths));
    }

    /**
     * Fix the directory separators (remove duplicates and replace with the current system directory separator)
     *
     * <code>
     * FileType::fixPath('/foo//bar\baz'); '/foo/bar/baz'
     * </code>
     *
     * @param string $path      The path
     * @param string $separator The separator [optional]
     */
    public static function fixPath(string $path, string $separator = DIRECTORY_SEPARATOR): string
    {
        $replaced     = str_replace([self::WINDOWS_DIRECTORY_SEPARATOR, self::UNIX_DIRECTORY_SEPARATOR], $separator, $path);
        $deduplicated = preg_replace('#[/\\\\]+#', $separator, $replaced);

        return $deduplicated;
    }

    /**
     * Guess the file extensions of the file
     *
     * <code>
     * FileType::guessExtensions('/foo/bar.txt'); // ['txt']
     * FileType::guessExtensions('/foo/bar.jpg'); // ['jpeg', 'jpg', 'jpe', 'jfif']
     * </code>
     *
     * @return string[]
     */
    public static function guessExtensions(string $fileName): arrat
    {
        $raw = (new finfo(FILEINFO_EXTENSION))->file($fileName);

        if (is_string($raw) && $raw !== '???') {
            return explode('/', $raw);
        }

        return [];
    }

    /**
     * Guess the file extension of the file
     *
     * <code>
     * FileType::guessExtension('/foo/bar.txt'); // 'txt'
     * FileType::guessExtension('/foo/bar.jpg'); // 'jpeg'
     * </code>
     */
    public static function guessExtension(string $filename): ?string
    {
        return ArrayType::first(self::guessExtensions($filename));
    }

    /**
     * Guess the mime-type of the file
     *
     * <code>
     * FileType::guessMime('/foo/bar.txt'); // 'text/plain'
     * FileType::guessMime('/foo/bar.jpg'); // 'image/jpeg'
     * </code>
     */
    public static function guessMime(string $filename): ?string
    {
        return (new finfo(FILEINFO_MIME_TYPE))->file($filename) ?? null;
    }
}
