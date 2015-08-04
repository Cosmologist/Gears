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
    public function strBefore($string, $before)
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
    public function striBefore($string, $before)
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
    public function strAfter($string, $after)
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
    public function striAfter($string, $after)
    {
        if ($pos = stripos($string, $after)) {
            return substr($string, $pos+1);
        }

        return false;
    }
}