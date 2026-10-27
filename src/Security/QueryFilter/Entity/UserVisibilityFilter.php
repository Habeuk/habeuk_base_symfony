<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\Security\QueryFilter\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Habeuk\HbkSymfony\Shared\Doctrine\BaseUser;

final readonly class UserVisibilityFilter extends AbstractQueryVisibilityFilter {
  
  public function supports(string $entityClass): bool {
    return is_a($entityClass, BaseUser::class, true);
  }
  
  public function apply(QueryBuilder $qb, BaseUser $user, string $rootAlias, string $entityClass): void {
    if (! $user->isSuperAdmin()) {
      $qb->andWhere("$rootAlias.id = :user_id")->setParameter('user_id', $user->getId());
    }
  }
}