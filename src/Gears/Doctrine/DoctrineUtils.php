<?php

namespace Cosmologist\Gears\Doctrine;

use Cosmologist\Gears\ObjectType;
use Doctrine\Common\Util\ClasUtils;
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
}
