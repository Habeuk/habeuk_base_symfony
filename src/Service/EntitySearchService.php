<?php
namespace Habeuk\HbkSymfony\Service;

use App\Entity\User;
use Habeuk\HbkSymfony\Security\QueryFilter\EntityVisibilityFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class EntitySearchService {

  public function __construct(private EntityManagerInterface $entityManager, private readonly EntityTypeResolverService $entityTypeResolver,
    private EntityVisibilityFilter $entityVisibilityFilter) {}

  /**
   * Recherche des entités par champs textuels
   *
   * @param class-string $entityClass Nom complet de la classe (ex: App\Entity\User)
   * @param string $query Terme de recherche
   * @param array<string> $searchFields Champs à rechercher
   * @param array<string> $displayFields Champ d'affichage
   * @param int $limit Nombre maximum de résultats
   * @return array<int, array<string, mixed>>
   */
  public function search(string $entityClass, User $user, string $query, array $searchFields, array $displayFields = [
    'id'
  ], int $limit = 20): array {
    if ($query === '' || $searchFields === []) {
      return [];
    }

    $repository = $this->entityManager->getRepository($entityClass);
    $qb = $repository->createQueryBuilder('e');

    // Construction des conditions OR
    $conditions = [];
    foreach ($searchFields as $field) {
      $typeField = $this->entityTypeResolver->findTypeField($entityClass, $field);
      $paramName = 'search_' . str_replace('.', '_', $field);
      switch ($typeField) {
        case 'int':
          if (! ctype_digit($query)) {
            continue 2;
          }
          $conditions[] = "e.{$field} = :{$paramName}";
          $qb->setParameter($paramName, (int) $query);
          break;
        case 'float':
        case 'decimal':
          if (! is_numeric($query)) {
            continue 2;
          }
          $conditions[] = "e.{$field} = :{$paramName}";
          $qb->setParameter($paramName, (float) $query);
          break;
        case 'string':
        case 'text':
          $conditions[] = "LOWER(e.{$field}) LIKE :{$paramName}";
          $qb->setParameter($paramName, '%' . mb_strtolower($query) . '%');
          break;
        case 'object':
        case 'collection':
        case 'array':
          // Ignorer les relations pour la recherche simple
          continue 2;
        default:
          throw new \ErrorException(sprintf('Le type "%s" pour le champ "%s" n\'est pas encore pris en compte', $typeField, $field));
      }
    }
    $qb->andWhere(implode(' OR ', $conditions));
    $this->entityVisibilityFilter->apply($qb, $user, $entityClass, 'e');
    $qb->setMaxResults($limit);
    $results = $qb->getQuery()->getResult();
    // Formater les résultats
    // TODO il faut egalement limiter avec les champs definie dans le group.
    return array_map(function ($entity) use ($displayFields) {
      $result = [
        'id' => $this->getEntityId($entity)
      ];
      foreach ($displayFields as $displayField) {
        if ($displayField === 'id')
          continue;
        $result[$displayField] = $this->getEntityValue($entity, $displayField);
      }
      return $result;
    }, $results);
  }

  private function getEntityId(object $entity): int {
    // Utiliser la méthode getId() si disponible
    if (method_exists($entity, 'getId')) {
      return $entity->getId();
    }

    // Fallback via reflection
    $reflection = new \ReflectionClass($entity);
    $property = $reflection->getProperty('id');
    $property->setAccessible(true);
    return $property->getValue($entity);
  }

  private function getEntityValue(object $entity, string $field): string {
    // Construire le nom du getter
    $getter = 'get' . ucfirst($field);

    if (method_exists($entity, $getter)) {
      // @phpstan-ignore-next-line
      return (string) $entity->$getter();
    }

    // Fallback via reflection
    $reflection = new \ReflectionClass($entity);
    $property = $reflection->getProperty($field);
    $property->setAccessible(true);
    return (string) $property->getValue($entity);
  }
}