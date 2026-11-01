<?php

namespace Cosmologist\Gears;

/**
 * File system exception
 */
final class FileException extends \RuntimeException
{
    public static function unableToServe(string $address, string $reason): self
    {
        return new self("Unable to serve file on '{$address}': {$reason}");
    }

    public static function notFile(string $path): self
    {
        return new self("Path '{$path}' is not a regular file and cannot be served");
    }

    public static function alreadyLocked(): self
    {
        return new self('File is already locked');
    }

    public static function unableToOpen(string $path): self
    {
        return new self("Unable to open file '{$path}' for locking");
    }

    public static function unableToRead(string $path): self
    {
        return new self("Unable to open file '{$path}' for reading");
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

    public static function unableToChangePermissions(string $path, int $permissions): self
    {
        return new self(sprintf("Unable to change permissions on '%s' to %04o", $path, $permissions));
    }

    public static function unableToTouch(string $path): self
    {
        return new self("Unable to update access and modification times of file '{$path}'");
    }

    public static function notFound(string $path): self
    {
        return new self("File '{$path}' doesn't exist");
    }

    public static function notWritable(string $path): self
    {
        return new self("Path '{$path}' is not writable");
    }
}
