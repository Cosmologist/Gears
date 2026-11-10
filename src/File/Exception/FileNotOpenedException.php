<?php

namespace Cosmologist\Gears\File\Exception;

use Cosmologist\Gears\File;

/**
 * Exception thrown when an operation requires an open file stream
 */
final class FileNotOpenedException extends FileException implements FileExceptionInterface
{
    public function __construct(File $file)
    {
        parent::__construct("File '{$file->path}' is not opened");
    }
}
