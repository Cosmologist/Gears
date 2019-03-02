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
     * Checks if the given key or index exists in the array
     *
     * @param array $array
     * @param mixed $key
     *
     * @return bool
     */
    public static function has(array $array, $key): bool
    {
        return array_key_exists($key, $array);
    }

    /**
     * Gets an item from the array by key.
     *
     * Return default value if key does not exist.
     *
     * @param  array $array
     * @param  mixed $key
     * @param  mixed $default
     *
     * @return mixed
     */
    public static function get(array $array, $key, $default = null)
    {
        return $array[$key] ?? $default;
    }

    /**
     * Adds a value to an array with a specific key.
     *
     * @param  array $array
     * @param  mixed $key
     * @param  mixed $value
     *
     * @return array The input array with new item
     */
    public static function set(array $array, $key, $value): array
    {
        $array[$key] = $value;

        return $array;
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
                $ranges[] = $currentRange;
                $currentRange = [$item];
            }
        }
        if (count($currentRange) === 1) {
            $currentRange[] = null;
            $ranges[] = $currentRange;
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
        $array = self::cast($array);

        if (($key = array_search($value, $array)) !== false) {
            unset($array[$key]);
        }

        return $array;
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
    public static function sort(
        $array,
        $propertyPath,
        $preserveKeys = false,
        $comparisonFunction = null,
        $reverse = false
    ) {
        $array = self::cast($array);

        $sortFunction = $preserveKeys ? 'uasort' : 'usort';
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
        $array = self::cast($array);

        $propertyAccessor = new PropertyAccessor();
        $uniqueValues = [];

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
     * Iterates over an array and calls the callback for each item
     *
     * @param iterable $array                          The input array
     * @param callable $callback                       The callback
     *                                                 Arguments:
     *                                                 - 1: Array item value
     *                                                 - 2: Array item key/index
     */
    public static function each(iterable $array, callable $callback)
    {
        foreach ($array as $key => $value) {
            $callback($value, $key);
        }
    }

    /**
     * Recursively iterates over an array and calls the callback for each item
     *
     * @param iterable      $array                       The input array
     * @param callable      $callback                    The callback
     *                                                   Arguments:
     *                                                   - 1: Array item
     *                                                   - 2: Array item key/index
     * @param callable|null $filter                      The callback, which should return TRUE to accept the current
     *                                                   item to recursive or FALSE otherwise
     *                                                   Arguments:
     *                                                   - 1: Array item
     *                                                   - 2: Array item key/index
     */
    public static function eachRecursive(iterable $array, callable $callback, callable $filter = null)
    {
        // Default filter callback - accept all items
        $filter = $filter ?? function () {
                return true;
            };

        $recursive = function ($current, $key) use ($callback, $filter) {
            self::each($item);

            if ($filter($current, $key) === true) {
                self::eachRecursive($current, $callback, $filter);
            }
        };

        self::each($array, $recursive);
    }

    /**
     * Iterates over an array and calls the callback for each children item.
     * Children are determined by the specified key name.
     *
     * @see self::eachRecursive
     *
     * @param iterable $array                            The input array
     * @param callable $callback                         The callback
     *                                                   Arguments:
     *                                                   - 1: Array item
     *                                                   - 2: Array item key/index
     * @param string   $childrenKey                      Name of the key referring to children
     */
    public static function eachDescendant(iterable $array, callable $callback, string $childrenKey)
    {
        $recursionCallback = function ($current, $key) use ($callback, $childrenKey) {
            $callback($current, $key);

            if (CompositeType::has($current, $childrenKey)) {
                self::eachDescendant(CompositeType::get($current, $childrenKey), $callback, $childrenKey);
            }
        };

        self::each($array, $recursionCallback);
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
        $array = self::cast($array);

        if (count($array) === 0) {
            return [];
        }

        $propertyPath = (array)$propertyPath;
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
    public static function filter($array, $expression = null)
    {
        if ($expression === null) {
            return array_filter($array);
        }

        $language = new ExpressionLanguage();
        $parsedNodes = $language->parse($expression, ['item'])->getNodes();

        return array_filter(
            self::cast($array),
            function ($item) use ($parsedNodes) {
                return (bool)$parsedNodes->evaluate([], ['item' => $item]);
            }
        );
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
     * @see http://php.net/manual/en/function.stats-standard-deviation.php
     * @see http://php.net/manual/ru/function.stats-standard-deviation.php#114473
     *
     * @return float|bool The standard deviation or false on error.
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
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double)$val) - $mean;
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
     * Get the first element of an array
     *
     * @param array $array The input array.
     *
     * @return mixed|null
     */
    public static function first($array)
    {
        if (count($array) === 0) {
            return null;
        }

        return reset($array);
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
        return array_combine(
            self::collect($array, $path),
            $array
        );
    }
}