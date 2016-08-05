<?php

namespace Cosmologist\Gears\StringType;

/**
 * Collection of methods for manipulate with CamelCase and snake_case strings
 */
class CamelSnakeCase
{
    /**
     * Take a string_like_this and return a StringLikeThis
     *
     * @param string $value The snake_case string
     *
     * @see http://www.refreshinglyblue.com/2009/03/20/php-snake-case-to-camel-case/
     *
     * @return string The CamelCase string
     */
    public static function snakeToCamel($value)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $value)));
    }

    /**
     * Take a StringLikeThis and return a string_like_this
     *
     * @param string $value The CamelCase string
     *
     * @see http://www.refreshinglyblue.com/2009/03/20/php-snake-case-to-camel-case/
     *
     * @return string The snake_case string
     */
    public static function camelToSnake($value)
    {
        $value = preg_replace_callback(
            '/(.)([A-Z])/',
            function($matches) {
                return $matches[1] . '_' . strtolower($matches[2]);
            },
            $value
        );
        return lcfirst($value);
    }
}