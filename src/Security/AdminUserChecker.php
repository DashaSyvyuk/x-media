<?php

namespace App\Security;

use App\Entity\AdminUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AdminUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (! $user instanceof AdminUser) {
            return;
        }

        if (! $user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Акаунт деактивовано.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
