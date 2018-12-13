<?php

namespace Cosmologist\Gears;

class CallableType
{
    const SEPARATOR = '::';

    /**
     * Parse callable from string expression.
     *
     * Supported syntax:
     * - 'My\ClassName::method' - callable for static class method.
     * - 'myFunction' - callable for function.
     *
     * @param string $expression The callable expression.
     *
     * @return callable
     */
    public static function parse(string $expression): callable
    {
        if (!StringType::contains($expression, self::SEPARATOR)) {
            return $expression;
        }

        return [
            StringType::strBefore($expression, self::SEPARATOR),
            StringType::strAfter($expression, self::SEPARATOR)
        ];
    }
}