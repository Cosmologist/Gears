<?php

namespace Cosmologist\Gears\Symfony\Security\Authorization;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @todo comment and readme and codestyle
 */
class ClassScopeAuthorizationChecker
{
    /**
     * Agreed value for class scope emulation object identifier
     */
    public const CLASS_SCOPE_OBJECT_IDENTIFIER_VALUE = 'class';

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $checker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param AuthorizationCheckerInterface $checker
     * @param TokenStorageInterface         $tokenStorage
     */
    public function __construct(AuthorizationCheckerInterface $checker, TokenStorageInterface $tokenStorage)
    {
        $this->checker      = $checker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param string $permission
     * @param string $type
     *
     * @return bool
     */
    public function hasAccessToType(string $permission, string $type): bool
    {
        return $this->checker->isGranted($permission, self::createObjectIdentity($type, self::CLASS_SCOPE_OBJECT_IDENTIFIER_VALUE));
    }

    /**
     * @param string $permission
     * @param string $type
     * @param string $field
     *
     * @return bool
     */
    public function hasAccessToTypeField(string $permission, string $type, string $field): bool
    {
        return $this->checker->isGranted($permission, new FieldVote(self::createObjectIdentity($type, self::CLASS_SCOPE_OBJECT_IDENTIFIER_VALUE), $field));
    }

    /**
     * @param string $permission
     * @param string $type
     * @param string $identifier
     *
     * @return bool
     */
    public function hasAccessToObject(string $permission, string $type, string $identifier)
    {
        return $this->checker->isGranted($permission, self::createObjectIdentity($type, $identifier));
    }

    /**
     * @param string $type
     * @param string $identifier
     *
     * @return ObjectIdentity
     */
    private static function createObjectIdentity(string $type, string $identifier): ObjectIdentity
    {
        return new ObjectIdentity($identifier, $type);
    }
}
