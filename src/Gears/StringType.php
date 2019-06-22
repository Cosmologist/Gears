<?php

namespace Cosmologist\Gears;

use RuntimeException;

/**
 * Collection of commonly used methods for working with strings
 */
class StringType
{
    /**
     * Determine if a given string contains a given substring.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function contains($haystack, $needle)
    {
        return mb_strpos($haystack, $needle) !== false;
    }

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
        $pos = $caseSensitive ? mb_strpos($string, $before) : mb_stripos($string, $before);
        if ($pos !== false) {
            return mb_substr($string, 0, $pos);
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
        $pos = $caseSensitive ? mb_strpos($string, $after) : mb_stripos($string, $after);
        if ($pos !== false) {
            return mb_substr($string, $pos + mb_strlen($after));
        }

        return false;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string       $haystack
     * @param  string|array $needles
     * @param bool          $caseSensitive
     *
     * @see Illuminate/Support/Str
     *
     * @return bool
     */
    public static function startsWith($haystack, $needles, $caseSensitive = true)
    {
        foreach ((array) $needles as $needle) {
            if (!is_string($needle) || $needle === '') {
                continue;
            }

            if (($caseSensitive ? mb_strpos($haystack, $needle) : mb_stripos($haystack, $needle)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string       $haystack
     * @param  string|array $needles
     * @param bool          $caseSensitive
     *
     * @see Illuminate/Support/Str
     *
     * @return bool
     */
    public static function endsWith($haystack, $needles, $caseSensitive = true)
    {
        foreach ((array) $needles as $needle) {
            $needle    = (string) $needle;
            $substring = mb_substr($haystack, -mb_strlen($needle));

            if (!$caseSensitive) {
                $needle    = mb_strtolower($needle);
                $substring = mb_strtolower($substring);
            }

            if ($needle === $substring) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace first string occurrence in an another string
     *
     * @see https://stackoverflow.com/a/22274299/663322 The speed test between different implementations
     *
     * @param string $string Haystack string
     * @param string $from   Replace from string
     * @param string $to     Replace to string
     *
     * @return string Replaced string or haystack
     */
    public static function replaceFirst($string, $from, $to)
    {
        if (false !== $pos = strpos($string, $from)) {
            return substr_replace($string, $to, $pos, strlen($from));
        }

        return $string;
    }

    /**
     * Wraps string by another string.
     *
     * @param string $string Target string
     * @param string $with   Another string
     *
     * @return string
     */
    public static function wrap($string, $with)
    {
        return $with . $string . $with;
    }

    /**
     * Join strings with a glue, ignore null-strings
     *
     * @param string   $glue    The glue
     * @param string[] $strings The array of strings to implode
     *
     * @return string
     */
    public static function implode(string $glue, ...$strings)
    {
        $strings = array_filter($strings);

        return implode($glue, $strings);
    }

    /**
     * Formats a string like spintf, but with name arguments support.
     *
     * @param string $format A formatted string with named template fields
     * @param array  $args   An associative array of values to place in the formatted string.
     *
     * @return string A formatted string
     */
    public static function namedSprintf($format, $args)
    {
        preg_match_all('/\%\((\S*?)\)\b/', $format, $matches, PREG_SET_ORDER);

        $values = [];
        foreach ($matches as $match) {
            if (!array_key_exists($match[1], $args)) {
                throw new RuntimeException("Key '{$match[1]}' does not found in the arguments");
            }

            $value    = $args[$match[1]];
            $format   = self::replaceFirst($format, '(' . $match[1] . ')', '');
            $values[] = $value;
        }

        return vsprintf($format, $values);
    }
}