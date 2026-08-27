<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\Security\QueryFilter\Entity;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;

interface QueryVisibilityFilterInterface {

  public function supports(string $entityClass): bool;

  public function apply(QueryBuilder $qb, User $user, string $rootAlias, string $entityClass): void;
}