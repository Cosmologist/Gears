<?php

namespace Cosmologist\Gears;

/**
 * Collection of commonly used methods for working with collections
 */
class Collection
{
    /**
     * Unset array items by value
     *
     * @see http://stackoverflow.com/a/7225113/663322
     *
     * @param array $array Array
     * @param mixed $value Value
     *
     * @return array Array after the items removing
     */
    public static function unsetValue($array, $value)
    {
        if (($key = array_search($value, $array)) !== false) {
            unset($array[$key]);
        }

        return $array;
    }


    /**
     * Group array by key value
     *
     * @param array $array Array
     * @param mixed $key Groupping key
     *
     * @return array Grouped array
     */
    public static function group($array, $key)
    {
        $result = array();

        foreach ($array as $item) {
            if (isset($item[$key])) {
                if (isset($result[$item[$key]])) {
                    $result[$item[$key]][] = $item;
                } else {
                    $result[$item[$key]] = array($item);
                }
            }
        }

        return $result;
    }


    /**
     * Check if array is associative
     *
     * @param array $array Array
     *
     * @return bool
     */
    public static function checkAssoc($array)
    {
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }
}