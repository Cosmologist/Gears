<?php

namespace Cosmologist\Gears\File\Exception;

use Cosmologist\Gears\File;

/**
 * Exception thrown when the open file stream does not permit writing
 */
final class WriteModeRequiredException extends FileException implements FileExceptionInterface
{
    public function __construct(File $file)
    {
        parent::__construct("Cannot write to file '{$file->path}': handle is not opened for writing");
    }
}
