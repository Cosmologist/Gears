<?php

namespace Cosmologist\Gears\File\Exception;

/**
 * Exception thrown when writing fails or makes no progress
 */
final class WriteFileException extends FileException implements FileExceptionInterface
{
    public function __construct(string $path, int $written, int $length)
    {
        parent::__construct("Unable to write file '{$path}': {$written} of {$length} bytes written");
    }
}
