<?php

namespace Cosmologist\Gears\File\Exception;

/**
 * Exception thrown when a directory cannot be created
 */
final class DirectoryCreateException extends FileException implements FileExceptionInterface
{
    public function __construct(string $path)
    {
        parent::__construct("Unable to create directory '{$path}'");
    }
}
