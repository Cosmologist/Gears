<?php

namespace Cosmologist\Gears\ArrayGears;

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
    public static function unsetValue(&$array, $value)
    {
        if(($key = array_search($value, $array)) !== false) {
            unset($array[$key]);
        }

        return $array;
    }
}