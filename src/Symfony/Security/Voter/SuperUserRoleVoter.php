<?php

namespace Cosmologist\Gears\Symfony\Security\Voter;

use Deprecated;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * A SuperUserRoleVoter brings a ROLE_SUPER_USER, which effectively bypasses any, and all security checks
 *
 * Enable the ROLE_SUPER_USER
 * <code>
 * # config/services.yaml
 * services:
 *     _defaults:
 *         autowire: true
 *         autoconfigure: true
 *
 *     Cosmologist\Gears\Symfony\Security\Voter\SuperUserRoleVoter:
 * </code>
 *
 * Check if ROLE_SUPER_USER granted (e.g. inside a controller)
 * <code>
 * class FooController extends AbstractController
 * {
 *     public function barAction(): Response
 *    {
 *        $this->denyAccessUnlessGranted(SuperUserRoleVoter::ROLE_SUPER_USER);
 *        ...
 *    }
 * }
 * </code>
 */
class SuperUserRoleVoter implements VoterInterface
{
    public const string ROLE_SUPER_USER = 'ROLE_SUPER_USER';

    #[Override]
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        return $this->hasSuperUserRole($token) ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_ABSTAIN;
    }

    protected function hasSuperUserRole(TokenInterface $token): bool
    {
        // Symfony 5+
        if (method_exists($token, 'getRoleNames')) {
            return in_array(self::ROLE_SUPER_USER, $token->getRoleNames());
        }

        // DEPRECATED
        // Symfony old versions
        foreach ($token->getRoles() as $role) {
            if ($role->getRole() === self::ROLE_SUPER_USER) {
                return true;
            }
        }

        return false;
    }

    #[Deprecated]
    public function supportsAttribute($attribute)
    {
        return true;
    }

    #[Deprecated]
    public function supportsClass($class)
    {
        return true;
    }
}
