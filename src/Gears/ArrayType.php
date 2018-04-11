<?php

namespace Cosmologist\Gears;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
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
        $array = self::cast($array);

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
     * Cast value to array
     *
     * If value is an array -return it
     * If value is instance of Traversable - convert it to array
     * Else - return new array, where value is a item
     *
     * @param $value
     *
     * @return array
     */
    public static function cast($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value instanceof Traversable) {
            return iterator_to_array($value);
        }

        return [$value];
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
        return array_merge(self::cast($array1), self::cast($array2));
    }

    /**
     * Sort the array by contents
     *
     * @param array  $array              The array to sort
     * @param string $propertyPath       The path to the sort element in the collection
     * @param bool   $preserveKeys       Preserve array keys or not?
     * @param string $comparisonFunction The comparison function name (strcmp, strnatcmp etc.)
     * @param bool   $reverse            Reverse sorted result (DESC if true, ASC is false)
     *
     * @return array Sorted array
     */
    public static function sort($array, $propertyPath, $preserveKeys = false, $comparisonFunction = null, $reverse = false)
    {
        $array = self::cast($array);

        $sortFunction     = $preserveKeys ? 'uasort' : 'usort';
        $propertyAccessor = new PropertyAccessor();

        $sortFunction($array, function ($left, $right) use ($propertyAccessor, $propertyPath, $comparisonFunction, $reverse) {
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
                $result = $comparisonFunction($leftValue, $rightValue);
            } elseif ($leftValue === $rightValue) {
                $result = 0;
            } else {
                $result = ($leftValue < $rightValue) ? -1 : 1;
            }

            if ($reverse) {
                $result *= -1;
            }

            return $result;
        });

        return $array;
    }

    /**
     * Remove the items with duplicates values from an array
     *
     * @param array  $array        The input array
     * @param string $propertyPath The path to the unique element in the collection
     *
     * @return array
     */
    public static function unique($array, $propertyPath)
    {
        $array = self::cast($array);

        $propertyAccessor = new PropertyAccessor();
        $uniqueValues     = [];

        return array_filter(
            $array,
            function ($item) use ($propertyAccessor, $propertyPath, &$uniqueValues) {
                try {
                    $value = $propertyAccessor->getValue($item, $propertyPath);
                } catch (AccessException $e) {
                    $value = null;
                }

                if (!in_array($value, $uniqueValues)) {
                    $uniqueValues[] = $value;

                    return true;
                }

                return false;
            }
        );
    }

    /**
     * Collects the items by path from an array
     *
     * @param array  $array        The input array
     * @param string $propertyPath The path to the item for collecting
     *
     * @return array
     */
    public static function collect($array, $propertyPath)
    {
        $array = self::cast($array);

        $propertyAccessor = new PropertyAccessor();

        return array_map(
            function ($item) use ($propertyAccessor, $propertyPath) {
                try {
                    return $propertyAccessor->getValue($item, $propertyPath);
                } catch (AccessException $e) {
                    return null;
                }
            },
            $array
        );
    }

    /**
     * Filters the items by expression
     *
     * @param array  $array      The input array
     * @param string $expression The expression
     *                           If the expression returns true, the current value from array is returned into
     *                           the result array. Array keys are preserved.
     *                           Use "item" alias in the expression for access to iterated array item.
     *
     * @return array
     */
    public static function filter($array, $expression)
    {
        $array = self::cast($array);

        $language    = new ExpressionLanguage();
        $parsedNodes = $language->parse($expression, ['item'])->getNodes();

        return array_filter(
            $array,
            function ($item) use ($parsedNodes) {
                $res = (bool) $parsedNodes->evaluate([], ['item' => $item]);

                return $res;
            }
        );
    }
}