<?php

namespace Cosmologist\Gears\Broadway\EventStore\DbalSelectable;

use Broadway\EventStore\Dbal\DBALEventStore;
use Broadway\Serializer\Serializer;
use Broadway\UuidGenerator\Converter\BinaryUuidConverterInterface;
use Cosmologist\Gears\Broadway\EventStore\SelectableEventStoreInterface;
use Cosmologist\Gears\Doctrine\DbalExpressionVisitor;
use Cosmologist\Gears\ObjectType;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Override;

/**
 * The DbalEventStoreSelectable, inherited from {@link DBALEventStore}, allows you to select messages from the store based on specified criteria.
 *
 * The criteria specified using `Doctrine\Common\Collections\Criteria`.
 *
 * ```
 * $criteria = new Criteria();
 * $criteria->where(new Expr\Comparison('uuid', Expr\Comparison::EQ, $order->getValue()));
 * $criteria->orWhere(new Expr\Comparison('_related->"$[*]"', Expr\Comparison::MEMBER_OF, $order->getValue()));
 *
 * $this->eventStore->walk(
 *     $criteria,
 *     function (DomainMessage $domainMessage) {
 *         ...
 *     }
 * );
 * ```
 */
class DbalEventStoreSelectable extends DBALEventStore implements SelectableEventStoreInterface
{
    public function __construct(private readonly Connection   $connection,
                                Serializer                    $payloadSerializer,
                                Serializer                    $metadataSerializer,
                                private string                $tableName,
                                bool                          $useBinary,
                                ?BinaryUuidConverterInterface $binaryUuidConverter = null)
    {
        parent::__construct($connection, $payloadSerializer, $metadataSerializer, $tableName, $useBinary, $binaryUuidConverter);
    }

    #[Override]
    public function walk(Criteria $criteria, callable $callback): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $visitor      = new DbalExpressionVisitor($queryBuilder);

        $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->andWhere($criteria->getWhereExpression()->visit($visitor));

        $result = $queryBuilder->executeQuery();

        while (($row = $result->fetchAssociative()) !== false) {
            // I don't want to copy-paste the private method of the parent class, so I access it through a closure.
            $callback(ObjectType::callInternal($this, 'deserializeEvent', $row));
        }
    }
}
