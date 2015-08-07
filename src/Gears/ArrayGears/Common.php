<?php

namespace Cosmologist\Gears\ArrayGears;

/**
 * Collection of commonly used methods for working with arrays
 */
class Common
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
}