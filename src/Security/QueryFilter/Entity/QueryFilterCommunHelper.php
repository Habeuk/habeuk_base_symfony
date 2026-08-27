<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\Security\QueryFilter\Entity;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;

trait QueryFilterCommunHelper {

  public function getAliasNameForJoin(string $entityName): string {
    return 'join__' . $entityName;
  }

  public function addInnerJoinOnce(QueryBuilder $qb, string $join, string $alias): void {
    $joins = $qb->getDQLPart('join');
    foreach ($joins as $rootJoins) {
      foreach ($rootJoins as $existingJoin) {
        if ($existingJoin->getAlias() === $alias) {
          return;
        }
      }
    }
    $qb->innerJoin($join, $alias);
  }
}