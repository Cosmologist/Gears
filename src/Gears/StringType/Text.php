<?php

namespace Cosmologist\Gears\StringType;

/**
 * Collection of commonly used methods for working with text
 */
class Text
{
    /**
     * Split text into sentences
     *
     * Example:
     * <code>
     * print_r(
     *      Text::splitIntoSentences(
     *          'Fry me a Beaver. Fry me a Beaver! Fry me a Beaver? Fry me Beaver no. 4?! Fry me many Beavers... End)
     * );
     * </code>
     *
     * Result:
     * <code>
     * Array
     * (
     *     [0] => Fry me a Beaver.
     *     [1] => Fry me a Beaver!
     *     [2] => Fry me a Beaver?
     *     [3] => Fry me Beaver no. 4?!
     *     [4] => Fry me many Beavers...
     *     [5] => End
     * )
     * </code>
     *
     * @see http://stackoverflow.com/a/16377765
     *
     * @param string $text Text
     *
     * @return string[] Sentences
     */
    public static function splitIntoSentences($text)
    {
        return preg_split('/(?<=[.?!])\s+(?=[a-z])/i', $text);
    }

    /**
     * Split text into words
     *
     * @param string $text Text
     *
     * @return string[] Words
     */
    public static function splitIntoWords($text)
    {
        return preg_split('/\s+/', $text);
    }

    /**
     * Remove word from text
     *
     * @param string $text Text
     * @param string $word Word
     *
     * @return string String without the word
     */
    public static function removeWord($text, $word)
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