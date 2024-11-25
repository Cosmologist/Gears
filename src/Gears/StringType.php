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
     * Simple symmetric decryption of a string with a key (using libsodium)
     *
     * @param string $encrypted A base64-encoded encrypted string (via {@link StringType::encrypt()} to decrypt
     * @param string $key       An encryption key
     *
     * @return string A decrypted original string
     */
    public static function decrypt(string $encrypted, string $key): string
    {
        $key        = mb_substr($key, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES, '8bit');
        $key        .= str_repeat('0', SODIUM_CRYPTO_SECRETBOX_KEYBYTES - mb_strlen($key, '8bit'));
        $decoded    = base64_decode($encrypted);
        $nonce      = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        return sodium_crypto_secretbox_open(
            $ciphertext,
            $nonce,
            $key
        );
    }

    /**
     * Simple symmetric encryption of a string with a key (using libsodium)
     *
     * @param string $string A string to encrypt
     * @param string $key    An encryption key
     *
     * @return string A base64-encoded encrypted string
     *
     * @link StringType::encrypt()
     */
    public static function encrypt(string $string, string $key): string
    {
        $key   = mb_substr($key, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES, '8bit');
        $key   .= str_repeat('0', SODIUM_CRYPTO_SECRETBOX_KEYBYTES - mb_strlen($key, '8bit'));
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode(
            $nonce .
            sodium_crypto_secretbox(
                $string,
                $nonce,
                $key
            )
        );
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
     * @return string|null
     * @todo move to FileSystem
     *
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
     * @return string|null
     * @todo move to FileSystem
     *
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
     * @param string $string       The string
     * @param bool   $isNullBinary Consider zero binary?
     *
     * @return bool
     */
    public static function isBinary(string $string, $isNullBinary = false): bool
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
     * - Using return value instead of passing by reference is simpler and more straightforward
     * - You can pass pattern without delimiters
     * - You can select only first set or only first match of each result or only first match of first set (single scalar value) as return value
     *
     * <code>
     * // Default behaviour like preg_match_all(..., ..., PREG_SET_ORDER)
     * StringType::regexp('a1b2', '\S(\d)'); // [0 => [0 => 'a1', 1 => '1'], 1 => [0 => 'b2', 1 => '2']]

     * // Exclude full matches from regular expression matches
     * StringType::regexp('a1b2', '\S(\d)', true); // [0 => [0 => '1'], 1 => [0 => '2']]
     *
     * // Get only first set from regular expression matches (exclude full matches)
     * StringType::regexp('a1b2', '(\S)(\d)', true, true); // [0 => 'a', 1 => '1']
     *
     * // Get only first match of each set from regular expression matches (exclude full matches)
     * StringType::regexp('a1b2', '(\S)(\d)', true, false, true); // [0 => 'a', 1 => 'b']
     *
     * // Get only first match of the first set from regular expression matches as single scalar value
     * StringType::regexp('a1b2', '(\S)(\d)', true, true, true); // 'a'
     * </code>
     *
     * @param string $string           The input string
     * @param string $pattern          The pattern to search for, as a string
     * @param bool   $excludeFullMatch Exclude pattern full matches from the result
     * @param bool   $firstSet         Only first set should be used for the result
     * @param bool   $firstMatch       Only first match of each set should be used for the result
     *                                 if $onlyFirstSet==true the first match of the first set will be returned
     *                                 (Return value will hold a scalar value instead array value)
     *
     * @return array|scalar|null Array of the matches or single result value
     *
     * @todo auto preg_escape if needed
     */
    public static function regexp(string $string, string $pattern, bool $excludeFullMatch = false, bool $firstSet = false, bool $firstMatch = false)
    {
        /**
         * Checks if expression wrapped with delimiter (#....#, (....), /.../ etc)
         *
         * @param string $pattern
         *
         * @return bool
         */
        $hasDelimiter = function (string $pattern) {
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
        };

        if (!$hasDelimiter($pattern)) {
            $pattern = StringType::wrap($pattern, '#');
        }

        preg_match_all($pattern, $string, $matches, PREG_SET_ORDER);

        if ($excludeFullMatch) {
            $matches = array_map(function ($match) {
                // Shifts first item (full match)
                array_shift($match);

                return $match;
            }, $matches);
        }
        if ($firstSet) {
            $matches = array_shift($matches) ?? [];
        }
        if ($firstMatch) {
            if ($firstSet) {
                $matches = array_shift($matches);
            } else {
                $matches = array_map(function ($match) {
                    return array_shift($match);
                }, $matches);
            }
        }

        return $matches;
    }

    /**
     * ltrim() replacement supports UTF-8 chars in the charlist.
     *
     * Use these only if you are supplying the charlist optional arg and it contains
     * UTF-8 characters. Otherwise trim will work normally on a UTF-8 string.
     *
     * @see https://github.com/fluxbb/utf8/blob/master/functions/trim.php
     *
     * @param string $str      The input string.
     * @param string $charlist [optional] The stripped characters.
     *
     * @return string
     */
    public static function ltrim(string $str, string $charlist = '')
    {
        if (empty($charlist)) {
            return ltrim($str);
        }

        // Quote charlist for use in a characterclass
        $charlist = preg_replace('!([\\\\\\-\\]\\[/^])!', '\\\${1}', $charlist);

        return preg_replace('/^[' . $charlist . ']+/u', '', $str);
    }

    /**
     * rtrim() replacement supports UTF-8 chars in the charlist.
     *
     * Use these only if you are supplying the charlist optional arg and it contains
     * UTF-8 characters. Otherwise trim will work normally on a UTF-8 string.
     *
     * @see https://github.com/fluxbb/utf8/blob/master/functions/trim.php
     *
     * @param string $str      The input string.
     * @param string $charlist [optional] The stripped characters.
     *
     * @return string
     */
    public static function rtrim(string $str, string $charlist = '')
    {
        if (empty($charlist)) {
            return ltrim($str);
        }

        // Quote charlist for use in a characterclass
        $charlist = preg_replace('!([\\\\\\-\\]\\[/^])!', '\\\${1}', $charlist);

        return preg_replace('/[' . $charlist . ']+$/u', '', $str);
    }

    /**
     * trim() replacement supports UTF-8 chars in the charlist.
     *
     * Use these only if you are supplying the charlist optional arg and it contains
     * UTF-8 characters. Otherwise trim will work normally on a UTF-8 string.
     *
     * @see https://github.com/fluxbb/utf8/blob/master/functions/trim.php
     *
     * @param string $str      The input string.
     * @param string $charlist [optional] The stripped characters.
     *
     * @return string
     */
    public static function trim(string $str, string $charlist = '')
    {
        return self::ltrim(self::rtrim($str, $charlist), $charlist);
    }

    /**
     * Convert string to CamelCase.
     * Transform a "string_like_this" or "string like this" to a "StringLikeThis".
     *
     * @param string $value The input string
     *
     * @return string The input string
     */
    public static function toCamelCase($value)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $value)));
    }

    /**
     * Convert string to snake_case.
     * Transform a "StringLikeThis" or "string Like this" to a "string_like_this".
     *
     * @param string $value The input string
     *
     * @return string The snake_case string
     */
    public static function toSnakeCase($value)
    {
        return preg_replace_callback(
            '/(.)([A-Z])/',
            function ($matches) {
                if ($matches[1] === '_') {
                    return '_' . lcfirst($matches[2]);
                }
                return $matches[1] . '_' . lcfirst($matches[2]);
            },
            lcfirst(str_replace(' ', '_', $value))
        );
    }

    /**
     * Split text into sentences
     *
     * <code>
     * StringType::sentences('Fry me a Beaver. Fry me a Beaver! Fry me a Beaver? Fry me Beaver no. 4?! Fry me many Beavers... End);
     * </code>
     * returns
     * <code>
     * [
     *   [0] => 'Fry me a Beaver.',
     *   [1] => 'Fry me a Beaver!',
     *   [2] => 'Fry me a Beaver?',
     *   [3] => 'Fry me Beaver no. 4?!',
     *   [4] => 'Fry me many Beavers...',
     *   [5] => 'End'
     * ]
     * </code>
     *
     * @see http://stackoverflow.com/a/16377765
     *
     * @param string $text Text
     *
     * @return string[] Sentences
     */
    public static function sentences($text)
    {

        return preg_split('/(?<=[.?!])\s+(?=[a-z])/i', $text);
    }

    /**
     * Split text into words
     *
     * <code>
     * StringType::words('Fry me many Beavers... End'); // ['Fry', 'me', 'many', 'Beavers', 'End']
     * </code>
     *
     * @param string $text Text
     *
     * @return string[] Words
     */
    public static function words($text)
    {
        return preg_split('/[\s.,!?]+/u', $text);
    }

    /**
     * Remove word from text
     *
     * <code>
     * StringType::unword('Remove word from text', 'word'); // 'Remove from text'
     * </code>
     *
     * @param string $text Text
     * @param string $word Word
     *
     * @return string String without the word
     */
    public static function unword($text, $word)
    {
        if ($word === $text) {
            return '';
        }

        $text = preg_replace('/^' . preg_quote($word) . '\W/', '', $text);
        $text = preg_replace('/\W' . preg_quote($word) . '\W/', ' ', $text);
        $text = preg_replace('/\W' . preg_quote($word) . '$/', '', $text);

        return $text;
    }
}
