<?php

namespace Cosmologist\Gears\File\Exception;

/**
 * Exception thrown when a file cannot be opened
 */
final class OpenFileException extends FileException implements FileExceptionInterface
{
    public function __construct(string $path, string $mode)
    {
        parent::__construct("Unable to open file '{$path}' in mode '{$mode}'");
    }
}
