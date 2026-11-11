<?php

namespace Cosmologist\Gears\File\Exception;

/**
 * Exception thrown when resolving a link with a Linux pipe: display target
 */
final class MagicLinuxLinksUnsupportedException extends FileException
{
    public function __construct(string $path, string $target)
    {
        parent::__construct("Unable to resolve symbolic links for '{$path}': target '{$target}' is a Linux pipe reference, "
            . 'which PHP may resolve as a pathname. For a descriptor of the current process, '
            . 'use php://stdin, php://stdout, php://stderr or php://fd/N');
    }
}
