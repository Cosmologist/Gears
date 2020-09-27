<?php

namespace Cosmologist\Gears;

use finfo;
use Mimey\MimeTypes;
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
     * @param string       $haystack
     * @param string|array $needles
     * @param bool         $caseSensitive
     *
     * @return bool
     * @see Illuminate/Support/Str
     *
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
     * @param string       $haystack
     * @param string|array $needles
     * @param bool         $caseSensitive
     *
     * @return bool
     * @see Illuminate/Support/Str
     *
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

    /**
     * Guess the mime-type of data
     *
     * @param string $data
     *
     * @todo move to FileSystem
     *
     * @return string|null
     */
    public static function guessMime(string $data): ?string
    {
        return (new finfo(FILEINFO_MIME_TYPE))->buffer($data) ?? null;
    }

    /**
     * Guess the suitable file-extension for data
     *
     * @param string $data
     *
     * @todo move to FileSystem
     *
     * @return string|null
     */
    public static function guessExtension(string $data): ?string
    {
        return self::guessExtensionFinfo($data) ?? self::guessExtensionMimey($data);
    }

    /**
     * Guess extension for data with finfo
     *
     * @param string $data
     *
     * @return string|null
     */
    private static function guessExtensionFinfo(string $data): ?string
    {
        if (!class_exists('finfo')) {
            return null;
        }

        $result = (new finfo(FILEINFO_EXTENSION))->buffer($data);

        if (is_string($result) && $result !== '???') {
            return ArrayType::first(explode('|', $result));
        }

        return null;
    }

    /**
     * Guess extension for data with ralouphie/mimey library
     *
     * @param string $data
     *
     * @return string|null
     */
    private static function guessExtensionMimey(string $data): ?string
    {
        if (!class_exists('Mimey\\MimeTypes')) {
            return null;
        }

        if (null === $mime = self::guessMime($data)) {
            return null;
        }

        return (new MimeTypes())->getExtension($mime);
    }

    /**
     * Check if a string is a binary string
     *
     * @param string $string The string
     * @param bool   $isNullBinary Consider zero binary?
     *
     * @return bool
     */
    public static function isBinary(string $string, $isNullBinary=false): bool
    {
        $mime = self::guessMime($string);

        return $mime === null ? $isNullBinary : !self::startsWith($mime, 'text/');
    }

    /**
     * Makes a technical name human readable.
     *
     * Sequences of underscores are replaced by single spaces. The first letter
     * of the resulting string is capitalized, while all other letters are
     * turned to lowercase.
     *
     * @param string $text The text to humanize
     *
     * @return string The humanized text
     */
    public static function humanize($text)
    {
        return ucfirst(strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $text))));
    }

    /**
     * Perform a regular expression match
     *
     * More convenient than built-in functions
     * - The return value is always an array
     * - Using return value instead of passing by reference is simpler and more straightforward
     * - PREG_SET_ORDER is always on
     *
     * @todo auto preg_escape if needed
     *
     * @param string $string The input string
     * @param string $pattern The pattern to search for, as a string
     *
     * @return array Array of all matches
     */
    public static function regexp(string $string, string $pattern): array
    {
        /**
         * Checks if expression wrapped with delimiter (#....#, (....), /.../ etc)
         *
         * @param string $pattern
         *
         * @return bool
         */
        function hasDelimiters(string $pattern)
        {
            // Empty string has no delimiters
            if ($pattern === '') {
                return false;
            }

            // Read possible delimiter
            $delimiter = $pattern[0];

            // The pattern must contain at least two delimiters
            if (mb_substr_count($pattern, $delimiter) === 1) {
                return false;
            }

            // The last separator can be followed by modifiers
            $modifiers = ArrayType::last(explode($delimiter, $pattern));

            $pcreKnownModifiers = ['i', 'm', 's', 'x', 'e', 'A', 'D', 'S', 'U', 'X', 'J', 'u'];

            if ($modifiers !== '' && !in_array((array) $modifiers, $pcreKnownModifiers, true)) {
                return false;
            }

            return true;
        }

        if (!hasDelimiters($pattern)) {
            $pattern = StringType::wrap($pattern, '#');
        }

        preg_match_all($pattern, $string, $matches, PREG_SET_ORDER);

        return $matches;
    }
}