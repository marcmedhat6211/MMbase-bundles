<?php

namespace App\UserBundle\Security;

use App\UserBundle\Entity\User as AppUser;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        if (!$user->getEnabled()) {
            throw new AccessDeniedException("Your account has been blocked for some reason, please contact administrator");
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        // user account is expired, the user may be notified
        if (!$user->getEnabled()) {
            throw new AccessDeniedException("Your account has been blocked for some reason, please contact administrator");
        }
    }
}