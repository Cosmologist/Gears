<?php

namespace Cosmologist\Gears\File\Exception;

/**
 * Exception thrown when appending requires a handle opened in append mode
 */
final class AppendModeRequiredException extends FileException implements FileExceptionInterface
{
    public function __construct(string $path, string $mode)
    {
        parent::__construct("Cannot append to file '{$path}': handle mode '{$mode}' is not an append mode");
    }
}
