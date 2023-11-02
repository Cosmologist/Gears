<?php

namespace Cosmologist\Gears;

use ReflectionClass;

/**
 * Collection of commonly used methods for working with classes
 */
class ClassType
{
    /**
     * Get the class short name
     *
     * @param string|object $class An object (class instance) or a string (class name).
     *
     * @return string The class short name.
     */
    public static function shortName($class): string
    {
        return (new ReflectionClass($class))->getShortName();
    }

    /**
     * Get the class and the parent classes
     *
     * @param string|object $class An object (class instance) or a string (class name).
     *
     * @return array
     */
    public static function selfAndParents($class): array
    {
        if (null === $selfClass = ObjectType::toClassName($class)) {
            return [];
        }
        if (false === $parentsClasses = class_parents($selfClass)) {
            return [$selfClass];
        }

        return array_merge([$selfClass], $parentsClasses);
    }
}
