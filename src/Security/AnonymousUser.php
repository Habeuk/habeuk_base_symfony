<?php
namespace Habeuk\HbkSymfony\Security;

use App\Entity\User;

/**
 * Utilisateur anonyme virtuel (non persisté en base)
 */
class AnonymousUser {

  private static ?User $instance = null;

  /**
   * Retourne l'utilisateur anonyme (singleton)
   */
  public static function getUser(): User {
    if (self::$instance === null) {
      self::$instance = new User();
      self::$instance->email = 'anonymous@system.local';
      self::$instance->firstName = 'Anonyme';
      self::$instance->lastName = 'Anonyme';
      self::$instance->setRoles([
        'ROLE_ANONYMOUS'
      ]);
    }
    return self::$instance;
  }

  /**
   * Vérifie si un utilisateur est anonyme
   */
  public static function isAnonymous(?User $user): bool {
    return $user !== null && $user->getId() === 0;
  }
}