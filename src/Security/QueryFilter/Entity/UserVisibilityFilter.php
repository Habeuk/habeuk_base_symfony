<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\Security\QueryFilter\Entity;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final readonly class UserVisibilityFilter extends AbstractQueryVisibilityFilter {

  public function supports(string $entityClass): bool {
    return $entityClass === User::class;
  }

  public function apply(QueryBuilder $qb, User $user, string $rootAlias, string $entityClass): void {
    if (! $user->isSuperAdmin()) {
      $qb->andWhere("$rootAlias.id = :user_id")->setParameter('user_id', $user->getId());
    }
  }
}