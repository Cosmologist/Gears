<?php

namespace Cosmologist\Gears;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;

class CacheUtils
{
    /**
     * Generate a deterministic cache key for arbitrary parameters
     *
     * By serializing into a JSON string.<br>
     * You can use the cache value calculation function as part of the cache key
     * (just pass its closure along with the other parameters).
     *
     * ```
     * use Symfony\Contracts\Cache\CacheInterface;
     * use Symfony\Contracts\Cache\ItemInterface;
     *
     * $cacheKey = CacheUtils::generateKey('foo', 123, ['foo' => 'bar'], (object) ['bar' => 'baz'], $identifier);
     * // or
     * $cacheKey = CacheUtils::generateKey('my-cache-key', $identifier);
     * // or
     * $cacheKey = CacheUtils::generateKey(computingFunction(...), $identifier);
     *
     * // On cache misses, a callback is called that should return the missing value.
     * $callback = fn() => computingFunction($identifier);
     * // or
     * $callback = fn(ItemInterface $item) use ($identifier) {
     *     $item->expiresAfter(3600);
     *     ...
     *     computingFunction($identifier);
     * }
     *
     * $value = $cache->get($cacheKey, $callback);
     * ```
     *
     * @param mixed ...$parameters List of values to use in the cache key
     *
     * @return string The cache key
     */
    public static function generateKey(mixed ...$parameters): string
    {
        $normalized = array_map(function (mixed $parameter) {
            if ($parameter instanceof Closure) {
                $reflection = new ReflectionFunction($parameter);

                if ($reflection->isAnonymous()) {
                    throw new InvalidArgumentException('CacheUtils::generateKey() does not support anonymous functions.');
                }

                return $reflection->getName();
            }

            return $parameter;
        }, $parameters);

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
