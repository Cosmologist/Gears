<?php

namespace Cosmologist\Gears;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Collection of commonly used methods for working with objects
 */
class ObjectType
{
    /**
     * Returns the value at the end of the property path of the object graph.
     *
     * @see PropertyAccessorInterface::getValue()
     *
     * @param object $object       The object to traverse
     * @param string $propertyPath The property path to read
     *
     * @return mixed The value at the end of the property path
     */
    public static function get($object, $propertyPath)
    {
        return (new PropertyAccessor())->getValue($object, $propertyPath);
    }

    /**
     * Sets the value at the end of the property path of the object graph.
     *
     * @see PropertyAccessorInterface::setValue()
     *
     * @param object $object       The object to modify
     * @param string $propertyPath The property path to modify
     * @param mixed  $value        The value to set at the end of the property path
     */
    public static function set($object, $propertyPath, $value)
    {
        (new PropertyAccessor())->setValue($object, $propertyPath, $value);
    }

    /**
     * @param $object
     * @param $propertyPath
     *
     * @return bool
     */
    public static function has($object, $propertyPath)
    {
        return (new PropertyAccessor())->isReadable($object, $propertyPath);
    }

    /**
     * Casts target to class name.
     *
     * @param object|string $target Object or FQCN
     *
     * @return string FQCN
     */
    public static function castClass($target)
    {
        if (is_object($target)) {
            return get_class($target);
        }

        return $target;
    }

    /**
     * @deprecated
     * @see self::toString
     */
    public static function getStringRepresentation($object)
    {
        return self::toString($object);
    }

    /**
     * Returns the result of __toString method if presents or generate object string
     *
     * @see self::toStringOrNull
     * @see self::toStringAuto
     *
     * @param object $object Object
     *
     * @return string
     */
    public static function toString($object)
    {
        if (null !== $objectString = self::toStringOrNull($object)) {
            return $objectString;
        }

        return  self::toStringAuto($object);
    }

    /**
     * Returns the result of __toString method if presents or null
     *
     * Useful to avoid: `PHP Recoverable fatal error:  Object of class X could not be converted to string`
     *
     * @param object $object Object
     *
     * @return string|null
     */
    public static function toStringOrNull($object)
    {
        return method_exists($object, '__toString') ? $object->__toString() : null;
    }

    /**
     * Generates a readable string representation of any object
     *
     * @param object $object Object
     *
     * @return string
     */
    public static function toStringAuto($object)
    {
        return get_class($object) . '@' . spl_object_id($object);
    }

    /**
     * Reads value of internal object property (protected and private).
     *
     * Use with caution!
     *
     * @see https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
     *
     * @param object $object   Object
     * @param string $property Property name
     *
     * @return mixed
     */
    public static function readInternalProperty($object, $property)
    {
        $closure = function () use ($property) {
            return $this->$property;
        };

        return $closure->call($object);
    }

    /**
     * Writes value to internal object property (protected and private).
     *
     * Use with caution!
     *
     * @see https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
     *
     * @param object $object   Object
     * @param string $property Property name
     * @param mixed  $value    Value
     *
     * @return mixed
     */
    public static function writeInternalProperty($object, $property, $value)
    {
        $closure = function () use ($property, $value) {
            $this->$property = $value;
        };

        return $closure->call($object);
    }
}