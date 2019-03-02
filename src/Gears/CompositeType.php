<?php

namespace Cosmologist\Gears;

use ArrayAccess;
use InvalidArgumentException;

class CompositeType
{
    protected static function isObject($source): bool
    {
        return is_object($source);
    }

    public static function hasArrayAccess($source): bool
    {
        return is_array($source) || $source instanceof ArrayAccess;
    }

    protected static function accessible($source): bool
    {
        return self::isObject($source) || self::hasArrayAccess($source);
    }

    protected static function wrapper($source): string
    {
        if (self::isObject($source)) {
            return ObjectType::class;
        }
        if (self::hasArrayAccess($source)) {
            return ArrayType::class;
        }

        throw new InvalidArgumentException('Type \''.gettype($source).'\' does not support');
    }

    protected static function wrapperCall($source, string $function, ...$arguments)
    {
        return forward_static_call([self::wrapper($source), $function], $source, ...$arguments);
    }

    public static function has($source, $key): bool
    {
        return self::wrapperCall($source, 'has', $key);
    }

    public static function get($source, $key)
    {
        return self::wrapperCall($source, 'get', $key);
    }

    public static function set($source, $key)
    {
        return self::wrapperCall($source, 'set', $key);
    }
}