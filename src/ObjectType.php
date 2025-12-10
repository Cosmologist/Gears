<?php

namespace Cosmologist\Gears;

use Cosmologist\Gears\Symfony\PropertyAccessor\RecursivePropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Collection of commonly used methods for working with objects
 */
class ObjectType
{
    /**
     * Return the object identifier.
     *
     * @see spl_object_id
     */
    public static function identifier(object $object): int
    {
        return spl_object_id($object);
    }

    /**
     * Return the value at the end of the property path of the object graph.
     *
     * @see PropertyAccessorInterface::getValue()
     */
    public static function get(object $object, string $path): mixed
    {
        return (new PropertyAccessor())->getValue($object, $path);
    }

    /**
     * Read value of internal object property (protected and private).
     *
     * Use with caution!
     *
     * @see https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
     */
    public static function getInternal(object $object, string $property, string|null $scope = 'static'): mixed
    {
        $closure = function () use ($property) {
            return $this->$property;
        };

        return $closure->bindTo($object, $scope)();
    }

    /**
     * Get the values of the property path of the object recursively
     *
     *  <code>
     *  $grandfather = new Person(name: 'grandfather');
     *  $dad = new Person(name: 'dad', parent: $grandfather);
     *  $i = new Person(name: 'i', parent: $dad);
     *
     *  ObjectType::getRecursive($i, 'parent'); // [Person(dad), Person(grandfather)]
     *  </code>
     *
     * @see RecursivePropertyAccessor::getValue()
     */
    public static function getRecursive(object $object, string $path): array
    {
        return (new RecursivePropertyAccessor())->getValue($object, $path);
    }

    /**
     * Set the value at the end of the property path of the object graph.
     *
     * @see PropertyAccessorInterface::setValue()
     */
    public static function set(object $object, string $path, mixed $value): void
    {
        (new PropertyAccessor())->setValue($object, $path, $value);
    }

    /**
     * Write value to internal object property (protected and private).
     *
     * Use with caution!
     *
     * @see https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
     */
    public static function setInternal(object $object, string $property, mixed $value, string|null $scope = 'static'): mixed
    {
        $closure = function () use ($property, $value) {
            $this->$property = $value;
        };

        return $closure->call($object);
    }

    /**
     * Call the internal object method (protected and private) and returns result.
     *
     * Use with caution!
     *
     * @see https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
     */
    public static function callInternal(object $object, string $method, array $args, string|null $scope = 'static'): mixed
    {
        $closure = function () use ($method, $args) {
            return call_user_func([$this, $method], ...$args);
        };

        return $closure->bindTo($object, $scope)(...$args);
    }

    /**
     * Check whether a property path exists and can be read from an object.
     *
     * @see PropertyAccessorInterface::isReadable()
     */
    public static function has(object $object, string $path): bool
    {
        return (new PropertyAccessor())->isReadable($object, $path);
    }

    /**
     * Determine the target FQCN.
     *
     *  - if $target is an object then returns him FQCN
     *  - if $target is a FQCN of existing class then returns as is
     *  - else returns null
     */
    public static function toClassName(object|string $target): ?string
    {
        if (is_object($target)) {
            return $target::class;
        }
        if (class_exists($target)) {
            return $target;
        }

        return null;
    }

    /**
     * Get a string representation of the object or enum.
     *
     * - Result of __toString method if presents
     * - String value of case for the BackedEnum
     * - Name of case for the UnitEnum
     * - or generated string like "FQCN@spl_object_id"
     *
     * PHP default behavior: if the method is not defined, an error (`Object of class X could not be converted to string`) is triggered.
     *
     * Examples:
     * <code>
     * namespace Foo;
     *
     * class Bar {
     * }
     * class BarMagicMethod {
     *     public function __toString(): string {
     *         return 'Bar';
     *     }
     * }
     * enum BazUnitEnum {
     *     case APPLE;
     * }
     * enum BazStringBackedEnum: string {
     *     case APPLE = 'apple';
     * }
     * enum BazIntBackedEnum: int {
     *     case APPLE = 1;
     * }
     *
     * ObjectType::toString(new Foo); // 'Foo/Bar@1069'
     * ObjectType::toString(new FooMagicMethod); // 'Foo'
     * ObjectType::toString(BazUnitEnum::APPLE); // 'APPLE'
     * ObjectType::toString(BazStringBackedEnum::APPLE); // '1'
     * </code>
     */
    public static function toString(object $object): string
    {
        if (method_exists($object, '__toString')) {
            return $object->__toString();
        }
        if ($object instanceof \BackedEnum) {
            return (string) $object->value;
        }
        if ($object instanceof \UnitEnum) {
            return $object->name;
        }

        return $object::class . '@' . spl_object_id($object);
    }
}
