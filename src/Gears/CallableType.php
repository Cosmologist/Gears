<?php

namespace Cosmologist\Gears;

use Closure;
use ReflectionFunction;
use ReflectionMethod;

class CallableType
{
    /**
     * Determine if a callable a closure
     *
     * <code>
     * CallableType::reflection(fn($foo) => $foo); // bool(true)
     * CallableType::reflection('foo'); // bool(false)
     * CallableType::reflection([$foo, 'bar']); // bool(false)
     * CallableType::reflection('Foo\Bar::baz'); // bool(false)
     * </code>
     */
    public static function isClosure(callable $callable): bool
    {
        return $callable instanceof Closure;
    }

    /**
     * Determine if a callable a function
     *
     * <code>
     * CallableType::isFunction(fn($foo) => $foo); // bool(false)
     * CallableType::isFunction('foo'); // bool(true)
     * CallableType::isFunction([$foo, 'bar']); // bool(false)
     * CallableType::isFunction('Foo\Bar::baz'); // bool(false)
     * </code>
     */
    public static function isFunction(callable $callable): bool
    {
        return is_string($callable) && !StringType::contains($callable, '::');
    }

    /**
     * Determine if a callable a method
     *
     * <code>
     * CallableType::isMethod(fn($foo) => $foo); // bool(false)
     * CallableType::isMethod('foo'); // bool(false)
     * CallableType::isMethod([$foo, 'bar']); // bool(true)
     * CallableType::isMethod('Foo\Bar::baz'); // bool(true)
     * </code>
     */
    public static function isMethod(callable $callable): bool
    {
        if (self::isClosure($callable)) {
            return false;
        }
        if (self::isFunction($callable)) {
            return false;
        }

        return true;
    }

    /**
     * Determine if a callable a static method
     *
     * <code>
     * CallableType::isStaticMethod(fn($foo) => $foo); // bool(false)
     * CallableType::isStaticMethod('foo'); // bool(false)
     * CallableType::isStaticMethod([$foo, 'bar']); // bool(false)
     * CallableType::isStaticMethod('Foo\Bar::baz'); // bool(true)
     * </code>
     */
    public static function isStaticMethod(callable $callable): bool
    {
        if (!self::isMethod($callable)) {
            return false;
        }
        if (is_array($callable) && is_object($callable[0])) {
            return false;
        }

        return true;
    }

    /**
     * Get suitable reflection implementation for the callable
     *
     * <code>
     * CallableType::reflection(fn($foo) => $foo); // object(ReflectionFunction)
     * CallableType::reflection('foo'); // object(ReflectionFunction)
     * CallableType::reflection([$foo, 'bar']); // object(ReflectionMethod)
     * CallableType::reflection('Foo\Bar::baz'); // object(ReflectionMethod)
     * </code>
     */
    public static function reflection(callable $callable): ReflectionFunction|ReflectionMethod
    {
        if (self::isMethod($callable)) {
            return is_array($callable) ? new ReflectionMethod($callable[0], $callable[1]) : new ReflectionMethod($callable);
        }

        return new ReflectionFunction($callable);
    }
}
