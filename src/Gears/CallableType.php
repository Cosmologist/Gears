<?php

namespace Cosmologist\Gears;

use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class CallableType
{
    protected const SEPARATOR = '::';

    /**
     * Extracts a class name from callable expression
     *
     * Example:
     * ```php
     * self::extractClassFromExpression('Foo\Bar::baz');
     * // Result is: 'Foo\Bar'
     * ```
     *
     * @param string $expression
     *
     * @return string
     */
    protected static function extractClassFromExpression(string $expression): string
    {
        return StringType::strBefore($expression, self::SEPARATOR);
    }

    /**
     * Extracts a method name from callable expression
     *
     * Example:
     * ```php
     * self::extractClassFromExpression('Foo\Bar::baz');
     * // Result is: 'baz'
     * ```
     *
     * @param string $expression
     *
     * @return string
     */
    protected static function extractMethodFromExpression(string $expression): string
    {
        return StringType::strAfter($expression, self::SEPARATOR);
    }

    /**
     * Is composite expression
     *
     * @param string $expression The callable expression
     *
     * @return bool
     */
    protected static function isCompositeFormat(string $expression): bool
    {
        return StringType::contains($expression, self::SEPARATOR);
    }

    /**
     * Is function expression
     *
     * @param string $expression The callable expression
     *
     * @return bool
     */
    public static function isFunctionFormat(string $expression): bool
    {
        return !self::isCompositeFormat($expression);
    }

    /**
     * Validate callable expression
     *
     * @param string $expression The callable expression
     *
     * @return bool
     */
    public static function validate(string $expression): bool
    {
        return is_callable($expression) && (!self::isCompositeFormat($expression) || (new ReflectionMethod(self::extractClassFromExpression($expression), self::extractMethodFromExpression($expression)))->isStatic()) ;
    }

    /**
     * Get a suitable reflection object for the callable
     *
     * @param callable $callable
     *
     * @return ReflectionFunctionAbstract|ReflectionFunction|ReflectionMethod
     */
    public static function reflection(callable $callable): ReflectionFunctionAbstract
    {
        if (is_array($callable)) {
            return new ReflectionMethod(...$callable);
        }
        if (self::isCompositeFormat($callable)) {
            return new ReflectionMethod($callable);
        }

        return new ReflectionFunction($callable);
    }
}
