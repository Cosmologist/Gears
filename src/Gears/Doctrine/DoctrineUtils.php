<?php

namespace Cosmologist\Gears\Doctrine;

use Cosmologist\Gears\ObjectType;
use Doctrine\Common\Util\ClasUtils;
use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;

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
     * $doctrineUtils->getClassMetadata(new App\Controller\FooController())); // null
     * $doctrineUtils->getClassMetadata(App\Controller\FooController::class); // null
     * </code>
     *
     * @template T of object
     *
     * @param class-string<T>|T $entityOrClass
     *
     * @return ?ClassMetadata<T>
     */
    public function getClassMetadata(object|string $entityOrClass): ?ClassMetadata
    {
        if (null === $fqcn = ObjectType::toClassName($entityOrClass)) {
            return null;
        }
        if (null === $manager = $this->doctrine->getManagerForClass($fqcn)) {
            return null;
        }

        return $manager->getClassMetadata($fqcn);
    }
}
