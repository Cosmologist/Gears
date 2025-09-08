<?php

namespace Cosmologist\Gears;

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
     * Returns the real index if the passed index is negative or returns the original passed index
     */
    private static function getRealIndex(string|int $index, Countable|array $array): int|string
    {
        return is_int($index) && ($index < 0) ? count($array) + $index : $index;
    }

    /**
     * Checks if the given key or index exists in the array
     *
     * Supports negative indexes.
     */
    public static function has(array $array, string|int $key): bool
    {
        return array_key_exists(self::getRealIndex($key, $array), $array);
    }

    /**
     * Gets an item from the array by key or index (supports negative index too)
     */
    public static function get(array $array, int|string $key, mixed $default = null): mixed
    {
        return $array[self::getRealIndex($key, $array)] ?? $default;
    }

    /**
     * Adds a value to an array with a specific key
     */
    public static function set(array $array, string|int $key, mixed $value): array
    {
        $array[self::getRealIndex($key, $array)] = $value;

        return $array;
    }

    /**
     * Adds a value to an array with a specific key only if key not presents in an array
     *
     * It's more intuitive variant to <code>$array += [$key => $value];</code>
     *
     * <code>
     * $array = ['fruit' => 'apple'];
     * ArrayType::touch($array, 'color', 'red']); // ['fruit' => 'apple', 'color' => 'red'];
     * ArrayType::touch($array, 'fruit', 'banana']); // ['fruit' => 'apple'];
     * </code>
     */
    public static function touch(array $array, string|int $key, mixed $value): array
    {
        return self::has($array, $key) ? $array : self::set($array, $key, $value);
    }

    /**
     * Checks if a value exists in an array
     */
    public static function contains(array $array, mixed $value): bool
    {
        return in_array($value, $array);
    }

    /**
     * Get the first item from an iterable that optionally matches a given condition.
     *
     * Unlike array_shift() or reset(), this function safely handles any iterable
     * and allows filtering via a callback.
     *
     * <code>
     * // Get the first item of any iterable
     * ArrayType::first([1, 2, 3]); // returns 1
     *
     * // Find first even number
     * ArrayType::first([1, 3, 4, 6], fn($x) => $x % 2 === 0); // returns 4
     *
     * // Use named argument for optional parameter
     * ArrayType::first([1, 2, 3], condition: fn($x) => $x > 1); // returns 2
     *
     * // Returns null if no match or empty
     * ArrayType::first([], condition: fn($x) => $x > 0); // returns null
     * </code>
     *
     * @param iterable  $array     The iterable to search through.
     * @param ?callable $condition Optional callback that takes an item and returns true to accept it.
     *
     * @return mixed The first matching item, or null if none found or iterable is empty.
     */
    public static function first(iterable $array, ?callable $condition = null): mixed
    {
        foreach ($array as $item) {
            if ($condition === null) {
                return $item;
            }
            if ($condition($item) === true) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Get the last item from an iterable that optionally matches a given condition.
     *
     * Unlike end() or array_pop(), this function works with any iterable and supports filtering via a callback.
     *
     * ```
     * // Get the last item of any iterable
     * ArrayType::last([1, 2, 3]); // returns 3
     *
     * // Find last even number
     * ArrayType::last([1, 4, 3, 6], fn($x) => $x % 2 === 0); // returns 6
     *
     * // Use named argument for optional parameter
     * ArrayType::last([1, 2, 3], condition: fn($x) => $x < 3); // returns 2
     *
     * // Returns null if no match or empty
     * ArrayType::last([], condition: fn($x) => $x > 0); // returns null
     * ```
     *
     * @param iterable  $array     The iterable to search through.
     * @param ?callable $condition Optional callback that takes an item and returns true to accept it.
     *
     * @return mixed The last matching item, or null if none found or iterable is empty.
     */
    public static function last(iterable $array, ?callable $condition = null): mixed
    {
        return self::first(array_reverse(iterator_to_array($array)), $condition);
    }

    /**
     * Inserts an array after the key
     */
    public static function insertAfter(array $array, mixed $key, array $insert): array
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
     */
    public static function insertBefore(array $array, mixed $key, array $insert): array
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
     */
    public static function group(array $array, mixed $byColumn): array
    {
        $result = [];

        foreach ($array as $item) {
            if (array_key_exists($byColumn, $item)) {
                $result[$item[$byColumn]][] = $item;
            }
        }

        return $result;
    }

    /**
     * Create ranges from list
     *
     * Example: [1, 3, 7, 9] => [[1, 3], [3, 7], [7, 9]]
     */
    public static function ranges(array $list): array
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
     * Unset array items with specified value
     *
     * @see http://stackoverflow.com/a/7225113/663322
     */
    public static function unsetValue(array $array, mixed $value): array
    {
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
     *   - Traversable - converts to a native array (`iterator_to_array()`)
     *   - another - creates an array with argument ([value])
     */
    public static function toArray(mixed $value): array
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
     */
    public static function checkAssoc(array $array): bool
    {
        return (bool) count(array_filter(array_keys($array), 'is_string'));
    }

    /**
     * Merge arrays like array_merge, but supports Traversable objects too
     */
    public static function merge(iterable $array1, iterable $array2): array
    {
        return array_merge(self::toArray($array1), self::toArray($array2));
    }

    /**
     * Sort an array
     *
     * @param array    $array              The array to sort
     * @param string   $path               The path to the sort element in the collection
     * @param bool     $preserveKeys       Preserve array keys or not?
     * @param callable $comparisonFunction The comparison function name (strcmp, strnatcmp etc.)
     * @param bool     $reverse            Reverse sorted result (DESC if true, ASC is false)
     *
     * @return array Sorted array
     */
    public static function sort(
        iterable  $array,
        string    $path,
        bool      $preserveKeys = false,
        ?callable $comparisonFunction = null,
        bool      $reverse = false): array
    {
        $array = self::toArray($array);

        $sortFunction     = $preserveKeys ? 'uasort' : 'usort';
        $propertyAccessor = new PropertyAccessor();

        $sortFunction(
            $array,
            function ($left, $right) use ($propertyAccessor, $path, $comparisonFunction, $reverse) {
                try {
                    $leftValue = $propertyAccessor->getValue($left, $path);
                } catch (AccessException $e) {
                    $leftValue = null;
                }
                try {
                    $rightValue = $propertyAccessor->getValue($right, $path);
                } catch (AccessException $e) {
                    $rightValue = null;
                }

                // Process null values explicitly,
                // because some native comparison functions (e.g. strnatcmp) deprecate to support null arguments
                if ($leftValue === null && $rightValue !== null) {
                    $result = -1;
                } elseif ($leftValue !== null && $rightValue === null) {
                    $result = 1;
                } elseif ($leftValue === $rightValue) {
                    $result = 0;
                } elseif ($comparisonFunction !== null) {
                    $result = $comparisonFunction($leftValue, $rightValue);
                } else {
                    $result = ($leftValue < $rightValue) ? -1 : 1;
                }

                return $reverse ? $result * -1 : $result;
            }
        );

        return $array;
    }

    /**
     * Remove the items with duplicates values by a path from an array
     */
    public static function unique(iterable $array, string $path): array
    {
        $array = self::toArray($array);

        $propertyAccessor = new PropertyAccessor();
        $uniqueValues     = [];

        return array_filter(
            $array,
            function ($item) use ($propertyAccessor, $path, &$uniqueValues) {
                try {
                    $value = $propertyAccessor->getValue($item, $path);
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
     * @param iterable $array            The input array
     * @param string[] $path             The path to the item for collection or array of paths.
     *                                   (see Symfony PropertyAccess syntax)
     */
    public static function collect(iterable $array, string ...$path): array
    {
        $array = self::toArray($array);

        if (count($array) === 0) {
            return [];
        }

        $propertyAccessor = new PropertyAccessor();

        $result = array_map(
            function ($item) use ($propertyAccessor, $path) {
                try {
                    $result = [];
                    foreach ($path as $key => $path) {
                        $result[$key] = $propertyAccessor->getValue($item, $path);
                    }

                    return $result;
                } catch (AccessException $e) {
                    return null;
                }
            },
            $array
        );

        if (count($path) === 1) {
            return array_column($result, key($path));
        }

        return $result;
    }

    /**
     * Map an array to the object
     */
    public static function map(iterable $array, object $object): object
    {
        $propertyAccessor = new PropertyAccessor();

        foreach ($array as $key => $value) {
            $propertyAccessor->setValue($object, $key, $value);
        }

        return $object;
    }

    /**
     * Filters the items
     *
     * @param array                   $array                The input array
     * @param string|callable|null    $expressionOrFunction The function or ExpressionLanguage expression for filter callback.
     *                                                      The callback function will auto-generated if passed ExpressionLanguage expression.
     *                                                      If the callback or expression evaluation result returns true, the current value from array is returned into
     *                                                      the result array. Array keys are preserved.
     *                                                      Use "item" keyword in the expression for access to iterated array item.
     * @param ExpressionLanguage|null $expressionLanguage   Pre-configured ExpressionLanguage instance
     * @param array                   $vars                 The parameters used in the expression and to be passed to the evaluator.
     *                                                      As an associative array.
     */
    public static function filter(array                $array,
                                  string|callable|null $expressionOrFunction = null,
                                  bool                 $invert = false,
                                  ?ExpressionLanguage  $expressionLanguage = null,
                                  array                $vars = []): array
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
     */
    public static function average($array): float|int
    {
        return array_sum($array) / count($array);
    }

    /**
     * Calculate the standard deviation of values in an array
     *
     * <code>
     * ArrayType::deviation([1, 2, 1]); // float(0.4714045207910317)
     * </code>
     *
     * @see http://php.net/manual/en/function.stats-standard-deviation.php#114473
     */
    public static function deviation(array $array): ?float
    {
        $n = count($array);

        if ($n === 0) {
            return null;
        }

        $mean  = array_sum($array) / $n;
        $carry = 0.0;
        foreach ($array as $val) {
            $d     = ((double) $val) - $mean;
            $carry += $d * $d;
        }

        return sqrt($carry / $n);
    }

    /**
     * Remove a specified column from a two-dimensional array
     */
    public static function unsetColumn(array $array, int|string $column): array
    {
        foreach ($array as $key => $row) {
            if (array_key_exists($column, $row)) {
                unset($array[$key][$column]);
            }
        }

        return $array;
    }

    /**
     * Creates array by using the value by the path as keys and the item as value
     *
     * @see https://symfony.com/doc/current/components/property_access.html
     *
     * @param array  $array The input array
     * @param string $path  The array column or property path
     */
    public static function index(array $array, string $path): array
    {
        return array_combine(self::collect($array, $path), $array);
    }

    /**
     * Push element onto the end of array and returns the modified array
     *
     * This is a wrapper around array_push, the difference in the return value - this function returns a modified array.
     */
    public static function push(array $array, ...$values): array
    {
        array_push($array, ...$values);

        return $array;
    }

    /**
     * Prepend element to the beginning of an array and returns the modified array
     *
     * This is a wrapper around array_unshift, the difference in the return value - this function returns a modified array.
     */
    public static function unshift(array $array, ...$values): array
    {
        array_unshift($array, ...$values);

        return $array;
    }

    /**
     * Get the array encoded in json
     *
     * - If encoded value is false, true or null then returns empty array
     * - JSON_THROW_ON_ERROR always enabled
     *
     * @param string $json    The json string being decoded
     * @param int    $options Bitmask of JSON decode options
     */
    public static function fromJson(string $json, int $options = 0): array
    {
        $array = json_decode($json, true, 512, $options & JSON_THROW_ON_ERROR);

        return is_array($array) ? $array : [];
    }
}
