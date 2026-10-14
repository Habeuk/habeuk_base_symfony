<?php
namespace Habeuk\HbkSymfony\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserChecker implements UserCheckerInterface {

  public function checkPreAuth(UserInterface $user): void {
    if (! $user instanceof User) {
      return;
    }
    // Email non validé
    if (! $user->isVerified()) {
      throw new CustomUserMessageAccountStatusException('Votre compte n’est pas encore validé. Veuillez consulter vos emails.');
    }
  }

  public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void {}
}
