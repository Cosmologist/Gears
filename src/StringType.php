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
     * Find the position of a substring within a string with support for case sensitivity, reverse search, and multibyte encodings
     *
     * This method improves upon PHP's native string position functions (like strpos, stripos, etc.) by eliminating common pitfalls:
     * - it returns null instead of false when the substring is not found — preventing type confusion
     * - supports multibyte and 8-bit encodings
     *
     * Examples
     * <code>
     * // Basic search in a UTF-8 string
     * $pos = StringType::position('Hello 世界', '世'); // returns 6
     *
     * // Case-insensitive search
     * $pos = StringType::position('Hello World', 'WORLD', searchCaseSensitive: false); // returns 6
     *
     * // Find last occurrence of substring
     * $pos = StringType::position('abcbc', 'bc', searchFromEnd: true); // returns 3
     *
     * // Returns null when substring is not found (not false)
     * $pos = StringType::position('test', 'x'); // returns null
     *
     * // Disable multibyte mode for ASCII-only strings
     * $pos = StringType::position('simple text', 'text', multibyteEncoding: false); // returns 6
     * </code>
     *
     * @param string $haystack            The string to search in.
     * @param string $needle              The substring to search for.
     * @param bool   $searchCaseSensitive Whether the search should be case-sensitive. Default: true.
     * @param bool   $searchFromEnd       If true, searches from the end of the string (last match). Default: false.
     * @param bool   $multibyteEncoding   If true, uses multibyte-safe functions with current internal encoding. Default: true.
     *
     * @return int|null The position of the match, or null if the substring is not found.
     */
    public static function position(string $haystack, string $needle, bool $searchCaseSensitive = true, bool $searchFromEnd = false, bool $multibyteEncoding = true): ?int
    {
        $encoding = $multibyteEncoding ? mb_internal_encoding() : '8bit';

        $position = $searchCaseSensitive
            ? ($searchFromEnd ? mb_strrpos($haystack, $needle, encoding: $encoding) : mb_strpos($haystack, $needle, encoding: $encoding))
            : ($searchFromEnd ? mb_strripos($haystack, $needle, encoding: $encoding) : mb_stripos($haystack, $needle, encoding: $encoding))
        ;

        return $position === false ? null : $position;
    }

    /**
     * Find the substring before the first (or last) occurrence of a given needle
     *
     * This function extracts the part of the haystack string that appears before the specified needle.
     * It supports case-sensitive and case-insensitive searches, allows searching from the end of the string,
     * and handles multibyte characters correctly by default.
     *
     * <code>
     * // Returns 'Hello ' (before 'World' in a case-sensitive search)
     * StringType::strBefore('Hello World', 'World');
     *
     * // Returns null because 'world' is not found with case-sensitive search
     * StringType::strBefore('Hello World', 'world');
     *
     * // Returns 'Hello ' due to case-insensitive search
     * StringType::strBefore('Hello World', 'world', searchCaseSensitive: false);
     *
     * // Returns 'Hello Wor' (before last 'l', searching from the end)
     * StringType::strBefore('Hello World', 'l', searchFromEnd: true);
     *
     * // Returns 'Привет ' (correctly handles Cyrillic characters)
     * StringType::strBefore('Привет Мир', 'Мир');
     *
     * // Returns null when needle is not found
     * StringType::strBefore('Test', 'xyz');
     * </code>
     *
     * @param string $haystack            The input string to search within.
     * @param string $needle              The substring to search for.
     * @param bool   $searchCaseSensitive Whether the search should be case-sensitive. Default is true.
     * @param bool   $searchFromEnd       If true, searches for the last occurrence of the needle. Default is false.
     * @param bool   $multibyteEncoding   If true, treats the string as multibyte (UTF-8). Default is true.
     *
     * @return ?string The substring before the needle, or null if needle is not found or result is empty.
     */
    public static function strBefore(string $haystack, string $needle, bool $searchCaseSensitive = true, bool $searchFromEnd = false, bool $multibyteEncoding = true): ?string
    {
        if (null === $position = self::position($haystack, $needle, $searchCaseSensitive, $searchFromEnd, $multibyteEncoding)) {
            return null;
        }

        $encoding  = $multibyteEncoding ? mb_internal_encoding() : '8bit';
        $substring = mb_substr($haystack, 0, $position, $encoding);

        return $substring === '' ? null : $substring;
    }

    /**
     * Find the substring after the first (or last) occurrence of a given needle
     *
     * This function extracts the portion of the haystack string that comes after the specified needle.
     * It supports case-sensitive and case-insensitive searches, allows searching from the end of the string,
     * and properly handles multibyte characters by default.
     *
     * <code>
     * // Returns 'World' (after 'Hello ' in a case-sensitive search)
     * StringType::strAfter('Hello World', 'Hello ');
     *
     * // Returns null because 'hello ' is not found when case-sensitive
     * StringType::strAfter('Hello World', 'hello ');
     *
     * // Returns 'World' due to case-insensitive search
     * StringType::strAfter('Hello World', 'hello ', searchCaseSensitive: false);
     *
     * // Returns 'd' (after the last occurrence of 'l', searching from the end)
     * StringType::strAfter('Hello World', 'l', searchFromEnd: true);
     *
     * // Returns 'Мир' (correctly handles multibyte UTF-8 characters)
     * StringType::strAfter('Привет Мир', 'Привет ');
     *
     * // Returns null when needle is at the end and nothing follows
     * StringType::strAfter('Test', 'st');
     * </code>
     *
     * @param string $haystack            The input string to search within.
     * @param string $needle              The substring to search for.
     * @param bool   $searchCaseSensitive Whether the search should be case-sensitive. Default is true.
     * @param bool   $searchFromEnd       If true, searches for the last occurrence of the needle. Default is false.
     * @param bool   $multibyteEncoding   If true, treats the string as multibyte (UTF-8). Default is true.
     *
     * @return ?string The substring after the needle, or null if needle is not found or result is empty.
     */
    public static function strAfter(string $haystack, string $needle, bool $searchCaseSensitive = true, bool $searchFromEnd = false, bool $multibyteEncoding = true): ?string
    {
        if (null === $position = self::position($haystack, $needle, $searchCaseSensitive, $searchFromEnd, $multibyteEncoding)) {
            return null;
        }

        $encoding  = $multibyteEncoding ? mb_internal_encoding() : '8bit';
        $substring = mb_substr($haystack, $position + mb_strlen($needle, $encoding), encoding: $encoding);

        return $substring === '' ? null : $substring;
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
    public static function implode(string $glue, string ...$strings)
    {
        return implode($glue, array_filter($strings));
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
     * Guess the file extensions from the string data
     *
     * <code>
     * StringType::guessExtensions('foo bar'); // ['txt']
     * StringType::guessExtensions(file_get_content('foo.jpg')); // ['jpeg', 'jpg', 'jpe', 'jfif']
     * </code>
     *
     * @return string[]
     */
    public static function guessExtensions(string $string): array
    {
        if (class_exists(MimeTypes::class)) {
            if (null !== $extension = (new MimeTypes())->getExtension(self::guessMime($string))) {
                return [$raw];
            }
        }

        $raw = (new finfo(FILEINFO_EXTENSION))->buffer($string);

        if (is_string($raw) && $raw !== '???') {
            return explode('/', $raw);
        }

        return [];
    }

    /**
     * Guess the file extension from the string data
     *
     * <code>
     * StringType::guessExtension('foo bar'); // 'txt'
     * StringType::guessExtension(file_get_content('foo.jpg')); // 'jpeg'
     * </code>
     */
    public static function guessExtension(string $string): ?string
    {
        return ArrayType::first(self::guessExtensions($string));
    }

    /**
     * Guess the MIME-type of the string data
     *
     * <code>
     * StringType::guessMime('foo bar'); // "text/plain"
     * StringType::guessMime(file_get_content("foo.jpg")); // "image/jpeg"
     * </code>
     */
    public static function guessMime(string $string): ?string
    {
        return (new finfo(FILEINFO_MIME_TYPE))->buffer($string) ?? null;
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
