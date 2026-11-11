<?php

namespace Cosmologist\Gears\File\Exception;

/**
 * Exception thrown when a symbolic link target cannot be read
 */
final class ReadLinkException extends FileException
{
    public function __construct(string $path)
    {
        parent::__construct("Unable to read symbolic link '{$path}'");
    }
}
