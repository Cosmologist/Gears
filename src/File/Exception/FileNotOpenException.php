<?php

namespace Cosmologist\Gears\File\Exception;

/**
 * Exception thrown when an operation requires an open file handle
 */
final class FileNotOpenException extends FileException implements FileExceptionInterface
{
    public function __construct(string $path)
    {
        parent::__construct("File '{$path}' has no open handle; call fopen() before writing");
    }
}
