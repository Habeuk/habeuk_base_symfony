<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\EntityPolicy;

use Habeuk\HbkSymfony\DTO\Security\AccessCheckResult;
use App\Entity\User;
use Habeuk\HbkSymfony\EntityPolicy\Contract\EntityAccessCheckerInterface;
use Habeuk\HbkSymfony\Enum\ {
  PermissionEnum
};

final readonly class EntityAccessChecker extends BaseEntityAccessChecker implements EntityAccessCheckerInterface {

  /**
   *
   * @param object|class-string $entity
   * @param User $user
   * @return AccessCheckResult
   */
  public function canView(object|string $entity, User $user): AccessCheckResult {
    return $this->check($entity, $user, PermissionEnum::VIEW);
  }

  /**
   *
   * @param object|class-string $entity
   * @param User $user
   * @return AccessCheckResult
   */
  public function canCreate(object|string $entity, User $user): AccessCheckResult {
    return $this->check($entity, $user, PermissionEnum::CREATE);
  }

  /**
   *
   * @param object|class-string $entity
   * @param User $user
   * @return AccessCheckResult
   */
  public function canEdit(object|string $entity, User $user): AccessCheckResult {
    return $this->check($entity, $user, PermissionEnum::EDIT);
  }

  /**
   *
   * @param object|class-string $entity
   * @param User $user
   * @return AccessCheckResult
   */
  public function canDelete(object|string $entity, User $user): AccessCheckResult {
    return $this->check($entity, $user, PermissionEnum::DELETE);
  }

  /**
   *
   * @param class-string $entityClass
   * @param User $user
   * @return AccessCheckResult
   */
  public function canViewDefinition(string $entityClass, User $user): AccessCheckResult {
    return $this->checkEntityDefinition($entityClass, $user, PermissionEnum::VIEW);
  }

  /**
   *
   * @param class-string $entityClass
   * @param User $user
   * @return AccessCheckResult
   */
  public function canCreateDefinition(string $entityClass, User $user): AccessCheckResult {
    return $this->checkEntityDefinition($entityClass, $user, PermissionEnum::CREATE);
  }

  /**
   *
   * @param class-string $entityClass
   * @param User $user
   * @return AccessCheckResult
   */
  public function canEditDefinition(string $entityClass, User $user): AccessCheckResult {
    return $this->checkEntityDefinition($entityClass, $user, PermissionEnum::EDIT);
  }

  /**
   *
   * @param class-string $entityClass
   * @param User $user
   * @return AccessCheckResult
   */
  public function canDeleteDefinition(string $entityClass, User $user): AccessCheckResult {
    return $this->checkEntityDefinition($entityClass, $user, PermissionEnum::DELETE);
  }
}