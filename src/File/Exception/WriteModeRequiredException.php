<?php

namespace Cosmologist\Gears\File\Exception;

/**
 * Exception thrown when writing requires a handle opened in a writable mode
 */
final class WriteModeRequiredException extends FileException implements FileExceptionInterface
{
    public function __construct(string $path, string $mode)
    {
        parent::__construct("Cannot write to file '{$path}': handle mode '{$mode}' is not writable");
    }
}
