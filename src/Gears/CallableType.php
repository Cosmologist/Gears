<?php

namespace Cosmologist\Gears;

class CallableType
{
    protected const SEPARATOR = '::';

    /**
     * Parse callable expression.
     *
     * Supported syntax:
     * - 'Foo\Bar::baz' - callable for static class method.
     * - 'baz' - callable for function.
     *
     * @param string $expression The callable expression.
     *
     * @return callable
     */
    public static function parse(string $expression): callable
    {
        return self::isFunctionFormat($expression) ? $expression : self::parseComposite($expression);
    }

    /**
     * Parse composite (like "Foo::bar") callable expression
     *
     * @param string $expression The callable expression.
     *
     * @return callable
     */
    protected static function parseComposite($expression): array
    {
        return [
            StringType::strBefore($expression, self::SEPARATOR),
            StringType::strAfter($expression, self::SEPARATOR)
        ];
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
        return is_callable(self::parse($expression));
    }
}