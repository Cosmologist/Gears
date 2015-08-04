<?php

namespace Cosmologist\Gears\StringGears;

/**
 * Collection of commonly used methods for working with strings
 */
class Common
{
    /**
     * Search and return string before haystack string
     *
     * @param string $string String
     * @param string $before Before haystack
     *
     * @return string|false String before haystack string or false
     */
    public function stringBefore($string, $before)
    {
        return current(explode($before, $string));
    }


    /**
     * Search and return string after haystack string
     *
     * @param string $string String
     * @param string $after After haystack
     *
     * @return string|false String after haystack string or false
     */
    public function stringAfter($string, $after)
    {
        $result = explode($after, $string);
        if (count($result) > 1) {
            return $result[1];
        }
        return false;
    }
}