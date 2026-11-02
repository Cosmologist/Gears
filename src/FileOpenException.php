<?php

namespace Cosmologist\Gears;

/**
 * Exception thrown when a file cannot be opened
 */
final class FileOpenException extends FileException
{
    public function __construct(string $path, string $mode)
    {
        parent::__construct("Unable to open file '{$path}' in mode '{$mode}'");
    }
}
