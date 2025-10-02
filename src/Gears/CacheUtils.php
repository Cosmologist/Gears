<?php

namespace Cosmologist\Gears;

use Closure;
use Commerce\Bundle\PlatformBundle\Entity\Shop\Goods;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class CacheUtils
{
    /**
     * Generate a cache key by serializing arbitrary parameters into a JSON string
     *
     * ```
     * $cacheKey        = CacheUtils::generateKey('my-heavy-duty-function-cache-key', $identifier);
     * $computeIfNotHit = fn() => heavyDutyFn($identifier);
     *
     * $cache->get($cacheKey, $computeIfNotHit);
     * ```
     *
     * @param mixed ...$parameters List of values to use in the cache key
     *
     * @return string The cache key
     */
    public static function generateKey(mixed ...$parameters): string
    {
        return json_encode($parameters, JSON_THROW_ON_ERROR);
    }

    /**
     * Generate a cache key by serializing a function name and arbitrary parameters into a JSON string
     *
     * ```
     * $cacheKey                     = CacheUtils::generateKeyFn(heavyDutyFn(...), $identifier);
     * $heavyDutyComputationIfNotHit = fn() => heavyDutyFn($identifier);
     *
     * $cache->get($cacheKey, $computeIfNotHit);
     * ```
     * or with an anonymous computation function
     * ```
     * function getResult()
     * {
     *     $cacheKey                     = CacheUtils::generateKeyFn(getResult(...), $identifier);
     *     $heavyDutyComputationIfNotHit = function() { ... };
     *
     *     return $cache->get($cacheKey, $computeIfNotHit);
     * }
     * ```
 *
     * @param Closure $closure       A non-anonymous closure referencing a named function
     * @param mixed   ...$parameters List of values to use in the cache key
     *
     * @return string The cache key
     */
    public static function generateKeyFn(Closure $closure, mixed ...$parameters): string
    {
        $reflection = new ReflectionFunction($closure);

        if ($reflection->isAnonymous()) {
            throw new InvalidArgumentException('CacheUtils::generateKeyFn() does not support anonymous functions.');
        }

        return self::generateKey($reflection->getName(), ...$parameters);
    }
}
