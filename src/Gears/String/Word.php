<?php

namespace Cosmologist\Gears;

/**
 * Collection of commonly used methods for working with words
 */
class Word
{
    /**
     * Extract words from string
     *
     * @param string $string String
     *
     * @return string[] Words
     */
    public static function extract($string)
    {
        if ($string === '') {
            return array();
        }
        return preg_split('/\s+/', $string);
    }


    /**
     * Remove word from string
     *
     * @param string $string String
     * @param string $word Word
     *
     * @return string String without the word
     */
    public static function remove($string, $word)
    {
        if ($word === $string) {
            return '';
        }

        $string = preg_replace('/^' . preg_quote($word) . '\W/', '', $string);
        $string = preg_replace('/\W' . preg_quote($word) . '\W/', ' ', $string);
        $string = preg_replace('/\W' . preg_quote($word) . '$/', '', $string);

        return $string;
    }
}