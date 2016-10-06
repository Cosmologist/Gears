<?php

namespace Cosmologist\Gears;

/**
 * Collection of commonly used methods for working with strings
 */
class StringType
{
    /**
     * Search and return string before haystack string
     *
     * @param string $string        String
     * @param string $before        Before haystack
     * @param bool   $caseSensitive Case-sensitive search?
     *
     * @return false|string String before haystack string or false
     */
    public static function strBefore($string, $before, $caseSensitive = true)
    {
        $pos = $caseSensitive ? strpos($string, $before) : stripos($string, $before);
        if ($pos !== false) {
            return substr($string, 0, $pos);
        }

        return false;
    }

    /**
     * Search(case-sensitive) and return string after haystack string
     *
     * @param string $string        String
     * @param string $after         After haystack
     * @param bool   $caseSensitive Case-sensitive search?
     *
     * @return string|false String after haystack string or false
     */
    public static function strAfter($string, $after, $caseSensitive = true)
    {
        $pos = $caseSensitive ? strpos($string, $after) : stripos($string, $after);
        if ($pos !== false) {
            return substr($string, $pos + 1);
        }

        return false;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string       $haystack
     * @param  string|array $needles
     *
     * @see Illuminate/Support/Str
     *
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) === 0) return true;
        }

        return false;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string       $haystack
     * @param  string|array $needles
     *
     * @see Illuminate/Support/Str
     *
     * @return bool
     */
    public static function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle === substr($haystack, -strlen($needle))) return true;
        }

        return false;
    }
}