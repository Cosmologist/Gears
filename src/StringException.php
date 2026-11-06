<?php

namespace Cosmologist\Gears;

final class StringException extends \InvalidArgumentException
{
    public static function emptyString(): self
    {
        return new self('The string must not be empty');
    }

    public static function containsNullBytes(): self
    {
        return new self('The string must not contain null bytes');
    }
}
