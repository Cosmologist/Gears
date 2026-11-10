<?php

namespace Cosmologist\Gears\File\Exception;

use Cosmologist\Gears\File;

/**
 * Exception thrown when the open file stream does not permit reading
 */
final class ReadModeRequiredException extends FileException implements FileExceptionInterface
{
    public function __construct(File $file)
    {
        parent::__construct("Cannot read from file '{$file->path}': handle is not opened for reading");
    }
}
