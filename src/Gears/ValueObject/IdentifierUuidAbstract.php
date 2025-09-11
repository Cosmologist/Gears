<?php

namespace Cosmologist\Gears\ValueObject;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Identifier UUID Value Object
 *
 * ```
 * class ProductIdentifier extends IdentifierUuidAbstract {}
 *
 * // Create UUID-identifier from value
 * $product = new ProductIdentifier('70b3738c-dec5-40a1-a992-bdadb3e33f9d'); // object(ProductIdentifier)
 *
 * // Create UUID-identifier without value validation (default behaviour)
 * $product = new ProductIdentifier('2b29a26d-ce2a-41a1-bcb7-41858ae4820f'); // object(ProductIdentifier)
 *
 * // Create UUID-identifier with value validation
 * $product = new ProductIdentifier('123', validate: true); // InvalidArgumentException
 *
 * // Create UUID-identifier with auto-generated value (UUID v4)
 * $product = new ProductIdentifier(); // // object(ProductIdentifier)
 *
 * // IdentifierUuidAbstract extends IdentifierAbstract so also you can also call
 * $product->getValue(); // string('2b29a26d-ce2a-41a1-bcb7-41858ae4820f')
 * // and
 * $product->equals('2b29a26d-ce2a-41a1-bcb7-41858ae4820f'); // bool(true)
 * ```
 */
abstract class IdentifierUuidAbstract extends IdentifierAbstract
{
    public function __construct(UuidInterface|string $value = null, bool $validate = false)
    {
        if (is_string($value) && $validate && !Uuid::isValid($value)) {
            throw new InvalidArgumentException(sprintf('Invalid UUID "%s"', $value));
        }

        if ($value === null) {
            $value = Uuid::uuid4()->toString();
        } else if ($value instanceof UuidInterface) {
            $value = $value->toString();
        }

        parent::__construct($value);
    }
}
