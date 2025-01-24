<?php

namespace Cosmologist\Gears;

use ArrayObject;
use Countable;
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
     * Checks if the given key or index exists in the array.
     *
     * Supports negative indexes.
     *
     * @param array $array
     * @param mixed $key
     *
     * @return bool
     */
    public static function has(array $array, $key): bool
    {
        return array_key_exists(self::getRealIndex($key, $array), $array);
    }

    /**
     * Gets an item from the array.
     *
     * Return default value if key does not exist.
     * Supports negative indexes.
     *
     * @param array $array
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public static function get(array $array, $key, $default = null)
    {
        return $array[self::getRealIndex($key, $array)] ?? $default;
    }

    /**
     * Adds a value to an array with a specific key.
     *
     * Supports negative indexes.
     *
     * @param array $array
     * @param mixed $key
     * @param mixed $value
     *
     * @return array The input array with new item
     */
    public static function set(array $array, $key, $value): array
    {
        $array[self::getRealIndex($key, $array)] = $value;

        return $array;
    }

    /**
     * Returns the real index if the passed index is negative or returns the original passed index.
     *
     * @param mixed           $index
     * @param array|Countable $array
     *
     * @return int
     */
    public static function getRealIndex($index, $array)
    {
        return is_int($index) && ($index < 0) ? count($array) + $index : $index;
    }

    /**
     * Checks if a value exists in an array
     *
     * @param array $array
     * @param       $value
     *
     * @return bool
     */
    public static function contains(array $array, $value): bool
    {
        return in_array($value, $array);
    }

    /**
     * Inserts an array after the key
     *
     * @param array $array
     * @param mixed $key
     * @param array $insert
     *
     * @return array
     */
    public static function insertAfter(array $array, $key, $insert): array
    {
        $keyIndex = array_search($key, array_keys($array), true);

        if (false === $keyIndex || ($keyIndex + 1) === count($array)) {
            return $array + $insert;
        }

        return array_slice($array, 0, $keyIndex + 1, true)
            + $insert
            + array_slice($array, $keyIndex + 1, null, true);
    }

    /**
     * Inserts an array before the key
     *
     * @param array $array
     * @param mixed $key
     * @param array $insert
     *
     * @return array
     */
    public static function insertBefore(array $array, $key, $insert): array
    {
        $keyIndex = array_search($key, array_keys($array), true);

        if (false === $keyIndex || 0 === $keyIndex) {
            return $insert + $array;
        }

        return array_slice($array, 0, $keyIndex, true)
            + $insert
            + array_slice($array, $keyIndex, null, true);
    }

    /**
     * Group an array by the specified column
     *
     * @param array $array  Array
     * @param mixed $column Group column
     *
     * @return array Grouped array
     */
    public static function group($array, $column)
    {
        $result = [];

        foreach ($array as $item) {
            if (array_key_exists($column, $item)) {
                $result[$item[$column]][] = $item;
            }
        }

        return $result;
    }

    /**
     * Create ranges from list
     *
     * Example: [1, 3, 7, 9] => [[1, 3], [3, 7], [7, 9]]
     *
     * @param array $list
     *
     * @return array
     */
    public static function ranges(array $list)
    {
        $ranges = [];

        $currentRange = null;
        foreach ($list as $item) {
            if ($currentRange === null) {
                $currentRange[] = $item;
            } else {
                $currentRange[] = $item;
                $ranges[]       = $currentRange;
                $currentRange   = [$item];
            }
        }
        if (count($currentRange) === 1) {
            $currentRange[] = null;
            $ranges[]       = $currentRange;
        }

        return $ranges;
    }

    /**
     * Unset array item by value
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
        $array = self::toArray($array);

        if (($key = array_search($value, $array)) !== false) {
            unset($array[$key]);
        }

        return $array;
    }

    /**
     * Cast to an array
     *
     * Behavior for different types:
     *   - array - returns as is
     *   - iterable - converts to a native array (`iterator_to_array()`)
     *   - another - creates an array with argument ([value])
     *
     * @param mixed $value
     *
     * @return array
     */
    public static function toArray($value)
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
        return array_merge(self::toArray($array1), self::toArray($array2));
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
    public static function sort(
        $array,
        $propertyPath,
        $preserveKeys = false,
        $comparisonFunction = null,
        $reverse = false
    )
    {
        $array = self::toArray($array);

        $sortFunction     = $preserveKeys ? 'uasort' : 'usort';
        $propertyAccessor = new PropertyAccessor();

        $sortFunction(
            $array,
            function ($left, $right) use ($propertyAccessor, $propertyPath, $comparisonFunction, $reverse) {
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

                // Process null values explicitly,
                // because some native comparison functions (e.g. strnatcmp) deprecate to support null arguments
                if ($leftValue === null && $rightValue !== null) {
                    $result = -1;
                } elseif ($leftValue !== null && $rightValue === null) {
                    $result = 1;
                } elseif ($comparisonFunction !== null) {
                    $result = $comparisonFunction($leftValue, $rightValue);
                } elseif ($leftValue === $rightValue) {
                    $result = 0;
                } else {
                    $result = ($leftValue < $rightValue) ? -1 : 1;
                }

                return $reverse ? $result * -1 : $result;
            }
        );

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
        $array = self::toArray($array);

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
     * ```php
     * class MyClass
     * {
     *     public $foo;
     *     private $bar;
     *     public $baz;
     *
     *     public function __construct($foo, $bar, MyClass $baz = null)
     *     {
     *         $this->foo = $foo;
     *         $this->bar = $bar;
     *         $this->baz = $baz;
     *     }
     *
     *     public getBar()
     *     {
     *          return $this->bar;
     *     }
     * }
     *
     * $a = [new MyClass('foo1', 'bar1', 'baz1'), new MyClass('foo2', 'bar2', new MyClass('foo3', 'bar3'))];
     *
     * ArrayType::collect($a, 'foo'); // ['foo1', 'foo2'];
     * ArrayType::collect($a, 'bar'); // returns ['bar1', 'bar2'];
     * ArrayType::collect($a, 'baz.foo'); // returns [null, 'foo3'];
     *
     * ArrayType::collect($a, ['foo', 'bar']); // [['foo1', 'bar1'], ['foo2', 'bar2']];
     * ArrayType::collect($a, ['f' => 'foo', 'b' => 'bar']); // [['f' => 'foo1', 'b' => 'bar1'], ['f' => 'foo2', 'b' =>
     * 'bar2']];
     * ```
     *
     * @see https://symfony.com/doc/current/components/property_access.html
     *
     * @param array        $array        The input array
     * @param string|array $propertyPath The path to the item for collection or array of paths (@see Symfony
     *                                   PropertyAccess syntax)
     *
     * @return array
     */
    public static function collect($array, $propertyPath)
    {
        $array = self::toArray($array);

        if (count($array) === 0) {
            return [];
        }

        $propertyPath     = (array) $propertyPath;
        $propertyAccessor = new PropertyAccessor();

        $result = array_map(
            function ($item) use ($propertyAccessor, $propertyPath) {
                try {
                    $result = [];
                    foreach ($propertyPath as $key => $path) {
                        $result[$key] = $propertyAccessor->getValue($item, $path);
                    }

                    return $result;
                } catch (AccessException $e) {
                    return null;
                }
            },
            $array
        );

        if (count($propertyPath) === 1) {
            return array_column($result, key($propertyPath));
        }

        return $result;
    }

    /**
     * Map data to the object
     *
     * @param array         $data   Data
     * @param string|object $target FQCN or object
     *
     * @return object
     */
    public static function map($data, $target)
    {
        if (is_string($target)) {
            $target = new $target;
        }

        $propertyAccessor = new PropertyAccessor();

        foreach ($data as $key => $value) {
            $propertyAccessor->setValue($target, $key, $value);
        }

        return $target;
    }

    /**
     * Filters the items.
     *
     * @param array                   $array                The input array
     * @param string|callable|null    $expressionOrFunction The function or ExpressionLanguage expression for filter callback.
     *                                                      The callback function will auto-generated if passed ExpressionLanguage expression.
     *                                                      If the callback or expression evaluation result returns true, the current value from array is returned into
     *                                                      the result array. Array keys are preserved.
     *                                                      Use "item" keyword in the expression for access to iterated array item.
     * @param bool                    $invert
     * @param ExpressionLanguage|null $expressionLanguage   Pre-configured ExpressionLanguage instance
     * @param array                   $vars                 The parameters used in the expression and to be passed to the evaluator
     *                                                      As an associative array
     *
     * @return array
     */
    public static function filter($array, $expressionOrFunction = null, $invert = false, ExpressionLanguage $expressionLanguage = null, array $vars = [])
    {
        if ($expressionOrFunction === null) {
            return array_filter($array);
        }

        $language = $expressionLanguage ?? new ExpressionLanguage();

        $callback = is_callable($expressionOrFunction) ? $expressionOrFunction : function ($item) use ($language, $expressionOrFunction, $vars) {
            return (bool) $language->evaluate($expressionOrFunction, compact('item') + $vars);
        };

        if ($invert === true) {
            $callback = function ($item) use ($callback) {
                return !$callback($item);
            };
        }

        return array_filter(self::toArray($array), $callback);
    }

    /**
     * Calculate the average of values in an array
     *
     * @param array $array The input array
     *
     * @return float|int
     */
    public static function average($array)
    {
        return array_sum($array) / count($array);
    }

    /**
     * Returns the standard deviation
     *
     * This user-land implementation follows the implementation quite strictly;
     * it does not attempt to improve the code or algorithm in any way. It will
     * raise a warning if you have fewer than 2 values in your array, just like
     * the extension does (although as an E_USER_WARNING, not E_WARNING).
     *
     * @param array $a
     * @param bool  $sample [optional] Defaults to false
     *
     * @return float|bool The standard deviation or false on error.
     * @see http://php.net/manual/ru/function.stats-standard-deviation.php#114473
     *
     * @see http://php.net/manual/en/function.stats-standard-deviation.php
     */
    public static function deviation(array $a, $sample = false)
    {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);

            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);

            return false;
        }
        $mean  = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d     = ((double) $val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
            --$n;
        }

        return sqrt($carry / $n);
    }

    /**
     * Remove a specified column from in the input array
     *
     * @param array $array  The input array.
     * @param mixed $column The column of values to return.
     *                      This value may be the integer key of the column you wish to retrieve,
     *                      or it may be the string key name for an associative array
     *
     * @return array
     */
    public static function unsetColumn($array, $column)
    {
        foreach ($array as $key => $row) {
            if (array_key_exists($column, $row)) {
                unset($array[$key][$column]);
            }
        }

        return $array;
    }

    /**
     * Returns the first element from an array or iterable
     *
     * @param array|iterable $array The input array.
     *
     * @return mixed|null
     */
    public static function first($array)
    {
        foreach ($array as $item) {
            return $item;
        }

        return null;
    }

    /**
     * Get the last element of an array
     *
     * @param array $array The input array.
     *
     * @return mixed|null
     */
    public static function last($array)
    {
        if (count($array) === 0) {
            return null;
        }

        return end($array);
    }

    /**
     * Creates array by using the value by the path as keys and the item as value.
     *
     * @see https://symfony.com/doc/current/components/property_access.html
     *
     * @param array  $array The input array
     * @param string $path  The array column or property path
     *
     * @return array
     */
    public static function index($array, $path)
    {
        return array_combine(self::collect($array, $path), $array);
    }

    /**
     * Verify that the contents of a variable is a countable value
     *
     * @use If PHP >= 7.3.0 use is_countable function
     *
     * @param mixed $arrayOrCountable
     *
     * @return bool
     */
    public static function isCountable($arrayOrCountable): bool
    {
        return is_array($arrayOrCountable) || ($arrayOrCountable instanceof Countable);
    }

    /**
     * Push element onto the end of array and returns the modified array
     *
     * This is a wrapper around array_push, the difference in the return value - this function returns a modified array
     *
     * @param array $array
     * @param mixed $element
     *
     * @return array
     */
    public static function push(array $array, $element)
    {
        array_push($array, $element);

        return $array;
    }

    /**
     * Prepend element to the beginning of an array and returns the modified array
     *
     * This is a wrapper around array_unshift, the difference in the return value - this function returns a modified array
     *
     * @param array $array
     * @param mixed $element
     *
     * @return array
     */
    public static function unshift(array $array, $element)
    {
        array_unshift($array, $element);
        return $array;
    }

    /**
     * Get the array encoded in json
     *
     * If encoded value is false, true or null then returns empty array.
     * JSON_THROW_ON_ERROR always enabled.
     *
     * @param string $json    The json string being decoded.
     * @param int    $options Bitmask of JSON decode options
     *
     * @return array
     */
    public static function fromJson(string $json, int $options = 0)
    {
        $array = json_decode($json, true, 512, $options & JSON_THROW_ON_ERROR);

        return is_array($array) ? $array : [];
    }
}
