<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\Security\QueryFilter\Entity;

use Doctrine\ORM\QueryBuilder;
use Habeuk\HbkSymfony\Shared\Doctrine\BaseUser;

interface QueryVisibilityFilterInterface {

  public function supports(string $entityClass): bool;

  public function apply(QueryBuilder $qb, BaseUser $user, string $rootAlias, string $entityClass): void;
}