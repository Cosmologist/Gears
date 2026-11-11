<?php

namespace Cosmologist\Gears\File\Exception;

/**
 * Exception thrown when a symbolic link chain exceeds the resolution limit
 */
final class LinkDepthExceededException extends FileException
{
    public function __construct(string $path, int $maxDepth)
    {
        parent::__construct("Unable to resolve symbolic links for '{$path}': maximum depth {$maxDepth} exceeded");
    }
}
