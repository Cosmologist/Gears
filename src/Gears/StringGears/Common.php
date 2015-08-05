<?php

namespace Cosmologist\Gears\StringGears;

/**
 * Collection of commonly used methods for working with strings
 */
class Common
{
    /**
     * Search(case-sensitive) and return string before haystack string
     *
     * @param string $string String
     * @param string $before Before haystack
     *
     * @return string|false String before haystack string or false
     */
    public static function strBefore($string, $before)
    {
        if ($pos = strpos($string, $before)) {
            return substr($string, 0, $pos);
        }

        return false;
    }


    /**
     * Search(case-insensitive) and return string before haystack string
     *
     * @param string $string String
     * @param string $before Before haystack
     *
     * @return string|false String before haystack string or false
     */
    public static function striBefore($string, $before)
    {
        if ($pos = stripos($string, $before)) {
            return substr($string, 0, $pos);
        }

        return false;
    }


    /**
     * Search(case-sensitive) and return string after haystack string
     *
     * @param string $string String
     * @param string $after After haystack
     *
     * @return string|false String after haystack string or false
     */
    public static function strAfter($string, $after)
    {
        if ($pos = strpos($string, $after)) {
            return substr($string, $pos+1);
        }

        return false;
    }


    /**
     * Search(case-insensitive) and return string after haystack string
     *
     * @param string $string String
     * @param string $after After haystack
     *
     * @return string|false String after haystack string or false
     */
    public static function striAfter($string, $after)
    {
        if ($pos = stripos($string, $after)) {
            return substr($string, $pos+1);
        }

        return false;
    }


    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     *
     * @see Illuminate/Support/Str
     *
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle)
        {
            if ($needle != '' && strpos($haystack, $needle) === 0) return true;
        }

        return false;
    }


    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     *
     * @see Illuminate/Support/Str
     *
     * @return bool
     */
    public static function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle)
        {
            if ((string) $needle === substr($haystack, -strlen($needle))) return true;
        }

        return false;
    }
}