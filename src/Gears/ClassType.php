<?php

namespace Cosmologist\Gears;

use ReflectionClass;

/**
 * Collection of commonly used methods for working with classes
 */
class ClassType
{
    /**
     * Get the class or an object class short name.
     */
    public static function short(object|string $objectOrClass): string
    {
        return (new ReflectionClass($objectOrClass))->getShortName();
    }

    /**
     * Get the class and the parent classes.
     */
    public static function selfAndParents(string $class): array
    {
        if (false === $parentsClasses = class_parents($class)) {
            return [$class];
        }

        return array_merge([$class], $parentsClasses);
    }

    /**
     * Get the corresponding basic enum case dynamically from variable.
     *
     * Basic enumerations does not implement from() or tryFrom() methods,
     * but it is possible to return the corresponding enum case using the constant() function.
     *
     * @param string $enumClass An enum FQCN
     * @param string $caseName  An enum case name
     *
     * @return \UnitEnum
     */
    public static function enumCase(string $enumClass, string $caseName): \UnitEnum
    {
        return constant($enumClass . '::' . $caseName);
    }
}
