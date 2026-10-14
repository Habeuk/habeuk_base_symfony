<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\EntityPolicy\Contract;

use Habeuk\HbkSymfony\DTO\Security\AccessCheckResult;
use Habeuk\HbkSymfony\Shared\Doctrine\BaseUser;
use Habeuk\HbkSymfony\Enum\PermissionEnum;

interface EntityAccessCheckerInterface {

  /**
   * Verification complete.
   *
   * @param object|class-string $entity
   * @param BaseUser $user
   * @param PermissionEnum $action
   * @return AccessCheckResult
   */
  public function check(object|string $entity, BaseUser $user, PermissionEnum $action): AccessCheckResult;

  /**
   * Verifie uniquement les definitions de l'entité.
   *
   * @param class-string $entity
   * @param BaseUser $user
   * @param PermissionEnum $action
   * @return AccessCheckResult
   */
  public function checkEntityDefinition(string $entity, BaseUser $user, PermissionEnum $action): AccessCheckResult;

  /**
   * Verification de l'entité.
   *
   * @param object $entity
   * @param BaseUser $user
   * @param PermissionEnum $action
   * @return AccessCheckResult
   */
  public function checkEntityData(object $entity, BaseUser $user, PermissionEnum $action): AccessCheckResult;
}