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
     * Return the object identifier (integer object handle as string)
     *
     * @see spl_object_id
     *
     * @param $object
     *
     * @return string
     */
    public static function identifier($object): string
    {
        return (string) spl_object_id($object);
    }
    

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
     * Reads value of internal object property (protected and private).
     *
     * Use with caution!
     *
     * @see https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
     *
     * @param object $object       Object
     * @param string $propertyName Property name
     *
     * @return mixed
     */
    public static function getInternal($object, $propertyName)
    {
        $closure = function () use ($propertyName) {
            return $this->$propertyName;
        };

        return $closure->call($object);
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
     * Writes value to internal object property (protected and private).
     *
     * Use with caution!
     *
     * @see https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
     *
     * @param object $object       Object
     * @param string $propertyName Property name
     * @param mixed  $value        Value
     *
     * @return mixed
     */
    public static function setInternal($object, $propertyName, $value)
    {
        $closure = function () use ($propertyName, $value) {
            $this->$propertyName = $value;
        };

        return $closure->call($object);
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
     * Determine data classname
     *
     *  - if $data is object then returns FQCN of the object
     *  - if $data is FQCN of existing class then returns as is
     *  - else returns null
     *
     * @param object|string
     *
     * @return string|null
     */
    public static function toClassName($data): ?string
    {
        if (is_object($data)) {
            return get_class($data);
        }
        if (class_exists($data)) {
            return $data;
        }

        return null;
    }

    /**
     * Returns the object string representation
     *
     * - Result of __toString method if presents
     * - Value of case if object implement a BackedEnum interface
     * - or generate string like "FQCN@spl_object_id"
     *
     * @param object $object Object
     *
     * @return string
     *
     * @see self::toStringMagicMethod
     * @see self::toStringAuto
     */
    public static function toString($object)
    {
        if (null !== $objectString = self::toStringMagicMethod($object)) {
            return $objectString;
        }
        if ($object instanceof \BackedEnum) {
            return $object->value;
        }

        return  self::toStringAuto($object);
    }

    /**
     * Returns the result of __toString magic method or null
     *
     * Useful to avoid: `PHP Recoverable fatal error:  Object of class X could not be converted to string`
     *
     * @param object $object Object
     *
     * @return string|null
     */
    public static function toStringMagicMethod($object)
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
}
