<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\Security\QueryFilter\Entity;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Doctrine\ORM\EntityManagerInterface;
use Habeuk\HbkSymfony\Contract\OwnerInterface;
use Habeuk\HbkSymfony\Shared\Doctrine\BaseUser;

#[AutoconfigureTag('app.query_visibility_filter')]
abstract readonly class AbstractQueryVisibilityFilter implements QueryVisibilityFilterInterface {
  use QueryFilterCommunHelper;

  public function __construct(private EntityManagerInterface $em) {}

  protected function getEntityManager(): EntityManagerInterface {
    return $this->em;
  }

  /**
   * Cette verion par defaut est pour les contenus de Type OwnerInterface.
   *
   * {@inheritdoc}
   * @see \Habeuk\HbkSymfony\Security\QueryFilter\Entity\QueryVisibilityFilterInterface::apply()
   */
  public function apply(QueryBuilder $qb, BaseUser $user, string $rootAlias, string $entityClass): void {
    $expressions = [];
    if (is_a($entityClass, OwnerInterface::class, true)) {
      // L'utilisateur courant a créé la ressource.
      $expressions[] = $qb->expr()->eq("$rootAlias.owner", ':security_user');
    }

    if ($expressions === []) {
      throw new \LogicException(sprintf('Aucun filtre de visibilité applicable pour "%s".', $entityClass));
    }
    $qb->andWhere($qb->expr()
      ->orX(...$expressions))
      ->setParameter('security_user', $user);
  }
}