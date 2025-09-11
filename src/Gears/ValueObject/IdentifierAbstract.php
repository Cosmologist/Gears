<?php

namespace Cosmologist\Gears\ValueObject;

use Stringable;

/**
 * Identifier Value Object
 *
 * ```
 * class ProductIdentifier extends IdentifierAbstract {}
 *
 * $p1 = new ProductIdentifier(123);
 * $p1->getValue(); // 123
 *
 * $p2 = new ProductIdentifier('string-id');
 * $p2->getValue(); // 'string-id'
 *
 * $p1->equals($p2); // false
 * $p1->equals(new ProductIdentifier(123)); // true
 * $p1->equals(123); // true
 * ```
 */
abstract class IdentifierAbstract implements Stringable
{
    public function __construct(protected string|int $value)
    {
    }

    public function getValue(): string|int
    {
        return $this->value;
    }

    public function equals(IdentifierAbstract|string|int|null $other): bool
    {
        if ($other === null) {
            return false;
        }
        if (is_object($other)) {
            return $this->getValue() === $other->getValue();
        }

        return $this->getValue() === $other;
    }

    public function hash(): string
    {
        return md5(static::class . '@' . $identifier->getValue());
    }

    public function __toString(): string
    {
        return (string) $this->getValue();
    }
}
