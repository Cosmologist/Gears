<?php

namespace Cosmologist\Gears\Browser\Exception;

class BrowserControlException extends \RuntimeException
{
    public static function extensionError(string $type, string $message): self
    {
        return new self(sprintf('Browser extension returned error "%s": %s', $type, $message));
    }
}
