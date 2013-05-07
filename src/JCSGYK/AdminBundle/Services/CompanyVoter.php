<?php

namespace JCSGYK\AdminBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Prevent users to log in to a different companies host (Except SUPER_ADMINs)
 */
class CompanyVoter implements VoterInterface {

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function supportsAttribute($attribute) {
        // you won't check against a user attribute, so return true
        return true;
    }

    public function supportsClass($class) {
        // your voter supports all type of token classes, so return true
        return true;
    }

    function vote(TokenInterface $token, $object, array $attributes) {

        $user = $token->getUser();
        // we don't check unauthenticated users
        if ('anon.' === $user) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $company_id = $this->container->get('jcs.ds')->getCompanyId();


        if ($user->hasRole('ROLE_SUPER_ADMIN') || $user->getCompanyId() == $company_id) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        return VoterInterface::ACCESS_DENIED;
    }

}