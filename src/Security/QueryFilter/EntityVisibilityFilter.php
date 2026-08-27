<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\Security\QueryFilter;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Habeuk\HbkSymfony\Security\QueryFilter\Entity\QueryVisibilityFilterInterface;

final readonly class EntityVisibilityFilter {

  /**
   *
   * @param iterable<QueryVisibilityFilterInterface> $filters
   */
  public function __construct(#[AutowireIterator('app.query_visibility_filter')] private iterable $filters) {}

  /**
   * Permet de proteger les contenus renvoyer.
   * à ce stade les droits ont deja été verifier et valider.
   *
   * @param QueryBuilder $qb
   * @param User $user
   * @param string $entityClass
   * @param string $rootAlias
   */
  public function apply(QueryBuilder $qb, User $user, string $entityClass, string $rootAlias): void {
    foreach ($this->filters as $filter) {
      if ($filter->supports($entityClass)) {
        $filter->apply($qb, $user, $rootAlias, $entityClass);
        return;
      }
    }
    throw new \LogicException(sprintf('Aucun filtre de visibilité trouvé pour "%s".', $entityClass));
  }
}