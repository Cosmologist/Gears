<?php

namespace Cosmologist\Gears\ValueObject;

use Assert\Assertion;
use Cosmologist\Gears\ClassType;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;

/**
 * Value Object that represents a hybrid UUID-identifier
 *
 * The hybrid UUID-Identifier Value Object allows encoding up to two numeric values in a human-readable format.
 *
 * This can convenient, for example, when a system works with UUIDs,
 * but certain entities still rely on classic incremental identifiers.
 * This hybrid UUID implementation may be used to hold 1–2 integer values that can be extracted from it.
 *
 * ```
 * $userEntity->getId(); // 12345
 * $uuid = new UserIdentifier($userEntity->getId()); // 00012345-0000-8aaa-bbbb-cccdddeeefff
 * $uuid->getPrimaryValue(); // 12345
 * ```
 *
 * It also supports storing a secondary value to identify nested or aggregated data:
 * ```
 * $userEntity->getId(); // 12345
 * $userPhoto = $userEntity->getRandomPhoto();
 * $userPhoto->getNumber(); // 25
 * $uuid = new UserPhotoIdentifier($userEntity->getId(), $userPhoto->getNumber()); // 00012345-0025-8aaa-bbbb-cccdddeeefff
 * $uuid->getPrimaryValue(); // 12345
 * $uuid->getSecondaryValue(); // 25
 * ```
 *
 * Hybrid identifiers are highly readable for humans, especially within context.
 * From the example above, it’s immediately clear that this refers to photo #25 of user #12345.
 *
 * The hybrid UUID identifier follows this structure:
 * `01234567-0890-8aaa-bbbb-cccdddeeefff`
 *
 * - `1234567`               — Encodes the primary integer identifier, supporting values from 0 to 99,999,999
 *                             (approximately uint26).
 * - `890`                   — Encodes the optional secondary identifier, supporting values from 0 to 9,999
 *                             (approximately uint13).
 * - `8`                     — UUID specification version.
 * - `aaa-bbbb-cccdddeeefff` — A suffix unique to each identifier implementation,
 *                             defined and returned by the method IdentifierUuidHybridAbstract::suffix().
 *
 * This technique is made possible by leveraging the _UUID v8_ (_custom UUID_) specification.
 */
abstract class IdentifierUuidHybridAbstract extends IdentifierUuidAbstract
{
    public function __construct(UuidInterface|string|int $value, int $secondaryValue = 0, bool $validate = false)
    {
        $suffix = static::suffix();

        if (!preg_match('/[0-9A-F]{3}-[0-9A-F]{4}-[0-9A-F]{12}/i', $suffix)) {
            throw new InvalidArgumentException(sprintf('Invalid suffix "%s"', $suffix));
        }

        if (is_int($value)) {
            Assertion::greaterThan($value, 0, 'Provided "%s" for the IdentifierUuidHybrid::primary is not greater than "%s".');
            Assertion::greaterThan($value, 0, 'Provided "%s" for the IdentifierUuidHybrid::secondary is not greater than "%s".');
            Assertion::lessThan($value, 99999999, 'Provided "%s" for the IdentifierUuidHybrid::primary is not less than "%s".');
            Assertion::lessThan($secondaryValue, 9999, 'Provided "%s" for the IdentifierUuidHybrid::secondary is not less than "%s".');

            $value = sprintf('%s-%s-8%s',
                str_pad($value, 8, '0', STR_PAD_LEFT),
                str_pad($secondaryValue, 4, '0', STR_PAD_LEFT),
                $suffix
            );
        } else {
            Assertion::endsWith($value, $suffix, sprintf('%s should be end with "%s".', ClassType::short(static::class), $suffix));
        }

        parent::__construct($value, $validate);
    }

    /**
     * Возвращает уникальный суффикс для гибридного UUID
     *
     * Формат суффикса "aaa-bbbb-cccdddeeefff", каждый символ представляет собой представление шестнадцатеричной цифры ([0-9A-F]{1,2}).
     * Суффикс не проверяется на уникальность, вы сами должны гарантировать уникальность суффикса.
     */
    public abstract static function suffix(): string;

    /**
     * Извлекает целочисленное значение основного идентификатора закодированного в UUID
     */
    public function getPrimaryValue(): int
    {
        return (int) substr($this->value, 0, 8);
    }

    /**
     * Извлекает целочисленное значение опционального идентификатора закодированного в UUID
     */
    public function getSecondaryValue(): int
    {
        return (int) substr($this->value, 9, 4);
    }
}
