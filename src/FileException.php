<?php

namespace Cosmologist\Gears;

/**
 * File system exception
 */
final class FileException extends \RuntimeException
{
    public static function alreadyLocked(): self
    {
        return new self('File is already locked');
    }

    public static function unableToOpen(string $path): self
    {
        return new self("Unable to open file '{$path}' for locking");
    }

    public static function unableToAcquireLock(string $path): self
    {
        return new self("Unable to acquire lock on '{$path}'");
    }

    public static function notLocked(): self
    {
        return new self('No lock is currently held');
    }

    public static function unableToReleaseLock(string $path): self
    {
        return new self("Unable to release lock on '{$path}'");
    }

    public static function notFound(string $path): self
    {
        return new self("File '{$path}' doesn't exist");
    }
}
