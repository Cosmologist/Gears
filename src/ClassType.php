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
     * Retrieve a list of parent classes (and optionally interfaces) for a given class or object.
     *
     * This function extends PHP's built-in class_parents() by optionally including the class itself and implemented interfaces.
     *
     * ```
     * namespace Foo;
     *
     * class Bar {};
     * class Baz extends Bar implements Stringable {};
     *
     * ClassType::parents(Baz::class) // [Baz::class, Bar::class]
     * ClassType::parents(Baz::class, withSelf: false) // [Bar::class]
     * ClassType::parents('MyClass', withSelf: true, withInterfaces: true) // [Baz::class, Bar::class, Stringable::class]
     * ```
     *
     * @param object|string $objectOrClass  The class name or an object instance to inspect.
     * @param bool          $withSelf       Whether to include the class itself in the result (default: true).
     * @param bool          $withInterfaces Whether to include implemented interfaces in the result (default: false).
     *
     * @return array List of class/interface names in order: self (if included), parents, then interfaces (if included).
     */
    public static function parents(object|string $objectOrClass, bool $withSelf = true, bool $withInterfaces = false): array
    {
        $class = is_object($objectOrClass) ? get_class($objectOrClass) : $objectOrClass;

        $self       = $withSelf ? [$class] : [];
        $parents    = class_parents($class) ?: [];
        $implements = $withInterfaces ? (class_implements($class) ?: []) : [];

        return array_merge($self, $parents, $implements);
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
