<?php

namespace Cosmologist\Gears\Doctrine;

use Assert\Assertion;
use Cosmologist\Gears\ObjectType;
use Cosmologist\Gears\StringType;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\ClasUtils;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Proxy;

readonly class DoctrineUtils
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    public static function create(ManagerRegistry $doctrine): DoctrineUtils
    {
        return new self($doctrine);
    }

    /**
     * Get metadata for a persistent object or a persistent object class
     *
     * <code>
     * $doctrineUtils->getClassMetadata(new App\Entity\User()); // object(ClassMetadata)
     * $doctrineUtils->getClassMetadata(App\Entity\User::class); // object(ClassMetadata)
     * $doctrineUtils->getClassMetadata(new App\Controller\FooController()); // null
     * $doctrineUtils->getClassMetadata(App\Controller\FooController::class); // null
     * </code>
     *
     * @template T of object
     *
     * @param class-string<T>|Proxy<T>|T $objectOrClass
     *
     * @return ?ClassMetadata<T>
     */
    public function getClassMetadata(object|string $objectOrClass): ?ClassMetadata
    {
        if (null === $fqcn = ObjectType::toClassName($objectOrClass)) {
            return null;
        }
        if (null === $manager = $this->doctrine->getManagerForClass($fqcn)) {
            return null;
        }

        return $manager->getClassMetadata($fqcn);
    }

    /**
     * Get real class of a persistent object (resolve a proxy class)
     *
     * <code>
     * $doctrineUtils->getRealClass(Proxies\__CG__\App\Entity\User::class); // 'App\Entity\User'
     * $doctrineUtils->getRealClass(new Proxies\__CG__\App\Entity\User()); // 'App\Entity\User'
     * $doctrineUtils->getRealClass(App\Entity\User::class); // 'App\Entity\User'
     * $doctrineUtils->getRealClass(new App\Entity\User()); // 'App\Entity\User'
     * $doctrineUtils->getRealClass(new App\Controller\FooController()); // null
     * $doctrineUtils->getRealClass(App\Controller\FooController::class); // null
     * </code>
     *
     * @template T of object
     *
     * @param class-string<T>|Proxy<T>|T $objectOrClass
     *
     * @return class-string<T>
     */
    public function getRealClass(object|string $objectOrClass): ?string
    {
        if (null === $metadata = $this->getClassMetadata($objectOrClass)) {
            return null;
        }

        return $metadata->getReflectionClass()->getName();
    }

    /**
     * Check if an object, or an object class persistent (managed by the Doctrine)
     *
     * <code>
     * $doctrineUtils->isManaged(new MyEntity()); // true
     * $doctrineUtils->isManaged(new stdClass()); // false
     * </code>
     *
     * @template T of object
     *
     * @param class-string<T>|Proxy<T>|T $objectOrClass
     */
    public function isManaged(object|string $objectOrClass): bool
    {
        return null !== $this->getClassMetadata($objectOrClass);
    }

    /**
     * Get an identifier field name of the Doctrine object
     *
     * <code>
     * $doctrineUtils->getSingleIdentifierField(new MyEntityWithSingleIdentifier(id: 1000)); // 'id'
     * $doctrineUtils->getSingleIdentifierField(new MyEntityWithMultipleIdentifiers()); // \Assert\InvalidArgumentException
     * $doctrineUtils->getSingleIdentifierField(new stdClass); // \Assert\InvalidArgumentException
     * </code>
     *
     * @template T of object
     *
     * @param class-string<T>|Proxy<T>|T $objectOrClass
     *
     * @throws \Assert\InvalidArgumentException If the target has multiple identifiers or does not under Doctrine management
     */
    public function getSingleIdentifierField(object|string $objectOrClass): ?string
    {
        $metadata = $this->getClassMetadata($objectOrClass);
        Assertion::notNull($metadata, sprintf('No metadata found for the "%s" class', ObjectType::toClassName($objectOrClass)));

        $identifierFieldNames = $metadata->getIdentifierFieldNames();
        Assertion::count($identifierFieldNames, 1, sprintf('Doctrine object "%s" has multiple identifiers', $metadata->getName()));

        return current($identifierFieldNames);
    }

    /**
     * Get an identifier value of the Doctrine object
     *
     * <code>
     * $doctrineUtils->getSingleIdentifierValue(new MyEntityWithSingleIdentifier(id: 1000)); // 1000
     * $doctrineUtils->getSingleIdentifierValue(new MyEntityWithMultipleIdentifiers()); // \Assert\InvalidArgumentException
     * $doctrineUtils->getSingleIdentifierValue(new stdClass); // \Assert\InvalidArgumentException
     * </code>
     *
     * @template T of object
     *
     * @param Proxy<T>|T $object
     *
     * @throws \Assert\InvalidArgumentException If the object has multiple identifiers or does not under Doctrine management
     */
    public function getSingleIdentifierValue(object $object): null|string|int
    {
        $metadata = $this->getClassMetadata($object);
        Assertion::notNull($metadata, sprintf('No metadata found for the "%s" class', ObjectType::toClassName($object)));

        $identifiers = $metadata->getIdentifierValues($object);
        Assertion::count($identifiers, 1, sprintf('Doctrine object "%s" has multiple identifiers', $metadata->getName()));

        return current($identifiers);
    }

    /**
     * Merge multiple Doctrine\Common\Collections\Criteria into a new one
     *
     * <code>
     * use Doctrine\Common\Collections\Criteria;
     * use Doctrine\Common\Collections\Expr;
     *
     * DoctrineUtils::mergeCriteria(
     *     new Criteria(new Expr\Comparison('status', Expr\Comparison::EQ, 'new')),
     *     new Criteria(new Expr\Comparison('type', Expr\Comparison::NEQ, 'foo'))
     * );
     * </code>
     */
    public static function mergeCriteria(Criteria ...$oneOrMoreCriteria): Criteria
    {
        $resultCriteria = new Criteria();

        foreach ($oneOrMoreCriteria as $criteria) {
            $resultCriteria->andWhere($criteria->getWhereExpression());
        }

        return $resultCriteria;
    }

    /**
     * Add a join to a QueryBuilder with support of the nested join (e.g. "contact.user.type")
     *
     * <code>
     * $qb = $entityManager->getRepository(Company::class)->createQueryBuilder('company');
     *
     * DoctrineUtils::join($qb, 'contact.user.type');
     * // equivalent to
     * $qb
     *   ->join('company.contact', 'contact')
     *   ->join('contact.user', 'user')
     *   ->join('user.type', 'type');
     * </code>
     *
     * Attention: method doesn't care about alias uniqueness
     */
    public static function join(QueryBuilder $queryBuilder, string $join, string $joinTo = null): void
    {
        // Join doesn't require
        if (!StringType::contains($join, '.')) {
            return;
        }

        $joinTo = $joinToAlias ?? current($queryBuilder->getRootAliases());

        [$current, $left] = explode('.', $join, 2);
        $joinCurrent = sprintf('%s.%s', $joinTo, $current);

        $joinCurrentAlias = self::joinOnce($queryBuilder, $joinCurrent, $current);

        self::join($queryBuilder, $left, $joinCurrentAlias);
    }

    /**
     * Add a join to a QueryBuilder once and returns an alias of join
     *
     * <code>
     *
     * // Adds a join and returns an alias of added join
     * DoctrineUtils::joinOnce($qb, 'contact.user', 'u1'); // "u1"
     *
     * // If a join with specified parameters exists then only returns an alias of existed join
     * DoctrineUtils::joinOnce($qb, 'contact.user', 'u2'); // "u1"
     * </code>
     *
     * See arguments description at the {@link QueryBuilder::add()}.
     *
     * @return string Alias of the existed join or $alias
     */
    public static function joinOnce(QueryBuilder $queryBuilder, string $join, string $alias, string $conditionType = null, string $condition = null, string $indexBy = null): string
    {
        if (null !== $existedJoinAlias = self::getJoinAlias($queryBuilder, $join, $conditionType, $condition, $indexBy)) {
            return $existedJoinAlias;
        }

        $queryBuilder->join($join, $alias, $conditionType, $condition, $indexBy);

        return $alias;
    }

    /**
     * Return an alias of a QueryBuilder join with specified parameters if exists
     *
     * See arguments description at the {@link QueryBuilder::add()}.
     */
    private static function getJoinAlias(QueryBuilder $queryBuilder, string $join, string $conditionType = null, string $condition = null, string $indexBy = null): ?string
    {
        $joinTo = StringType::strBefore($join, '.');

        foreach ($queryBuilder->getDQLPart('join') as $dqlJoinsTo => $dqlJoins) {
            if ($dqlJoinsTo === $joinTo) {
                /** @var Join $dqlJoin */
                foreach ($dqlJoins as $dqlJoin) {
                    $joinTypeEqual      = $dqlJoin->getJoinType() === Join::INNER_JOIN;
                    $joinEqual          = $dqlJoin->getJoin() === $join;
                    $conditionTypeEqual = $dqlJoin->getConditionType() === $conditionType;
                    $conditionEqual     = $dqlJoin->getCondition() == $condition;
                    $indexByEqual       = $dqlJoin->getIndexBy() === $indexBy;

                    if ($joinTypeEqual && $joinEqual && $conditionTypeEqual && $conditionEqual && $indexByEqual) {
                        return $dqlJoin->getAlias();
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get a target class name of a given association path recursively (e.g. "contact.user")
     *
     * <code>
     * $doctrineUtils->getAssociationTargetClassRecursive('AppBundle/Entity/Company', 'contact.user'); // 'AppBundle/Entity/User'
     * </code>
     *
     *
     * @template T of object
     *
     * @param class-string<T>|Proxy<T>|T $objectOrClass
     */
    public function getAssociationTargetClassRecursive(string|object $objectOrClass, string $path): string
    {
        $metadata = $this->getClassMetadata($objectOrClass);
        Assertion::notNull($metadata, sprintf('No metadata found for the "%s" class', ObjectType::toClassName($objectOrClass)));

        if (!StringType::contains($path, '.')) {
            return $metadata->getAssociationTargetClass($path);
        }

        list($current, $left) = explode('.', $path, 2);

        return $this->getAssociationTargetClassRecursive($metadata->getAssociationTargetClass($current), $left);
    }
}
