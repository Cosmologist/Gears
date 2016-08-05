<?php

namespace Cosmologist\Gears;

use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Traversable;

/**
 * Collection of commonly used methods for working with arrays
 */
class ArrayType
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
     * @param mixed $key   Group key
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
        return (bool) count(array_filter(array_keys($array), 'is_string'));
    }

    /**
     * Merge arrays
     *
     * Merge arrays like array_merge, but supports Traversable objects too
     *
     * @param array|Traversable $array1
     * @param array|Traversable $array2
     *
     * @return array The resulting array
     */
    public static function merge($array1, $array2)
    {
        if ($array1 instanceof Traversable) {
            $array1 = iterator_to_array($array1);
        }
        if ($array2 instanceof Traversable) {
            $array2 = iterator_to_array($array1);
        }

        return array_merge($array1, $array2);
    }

    /**
     * Sort the array by contents
     *
     * @param array  $array              The array to sort
     * @param string $propertyPath       The path to the sort element in the collection item
     * @param bool   $preserveKeys       Preserve array keys or not?
     * @param string $comparisonFunction The comparison function name (strcmp, strnatcmp etc.)
     *
     * @return array Sorted array
     */
    public static function sort($array, $propertyPath, $preserveKeys = false, $comparisonFunction = null)
    {
        $sortFunction     = $preserveKeys ? 'uasort' : 'usort';
        $propertyAccessor = new PropertyAccessor();

        $sortFunction($array, function ($left, $right) use ($propertyAccessor, $propertyPath, $comparisonFunction) {
            try {
                $leftValue = $propertyAccessor->getValue($left, $propertyPath);
            } catch (AccessException $e) {
                $leftValue = null;
            }
            try {
                $rightValue = $propertyAccessor->getValue($right, $propertyPath);
            } catch (AccessException $e) {
                $rightValue = null;
            }

            if ($comparisonFunction !== null) {
                return $comparisonFunction($leftValue, $rightValue);
            }

            return ($leftValue < $rightValue) ? -1 : 1;
        });

        return $array;
    }
}