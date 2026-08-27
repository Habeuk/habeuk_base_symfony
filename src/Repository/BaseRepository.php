<?php
namespace Habeuk\HbkSymfony\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Habeuk\HbkSymfony\Repository\Interfaces\BaseRepositoryInterface;
use Doctrine\ORM\QueryBuilder;
use Habeuk\HbkSymfony\Contract\OwnerInterface;
use Habeuk\HbkSymfony\DTO\Results\CollectionResultsDto;
use App\Entity\User;
use Habeuk\HbkSymfony\Security\QueryFilter\EntityVisibilityFilter;
use Habeuk\HbkSymfony\Shared\Doctrine\AbstractBaseEntity;
use Doctrine\Persistence\ManagerRegistry;
use Habeuk\HbkSymfony\Security\QueryFilter\Entity\QueryFilterCommunHelper;

/**
 *
 * @template TEntityClass of AbstractBaseEntity
 * @extends ServiceEntityRepository<TEntityClass>
 * @implements BaseRepositoryInterface<TEntityClass>
 */
abstract class BaseRepository extends ServiceEntityRepository implements BaseRepositoryInterface {
  use QueryFilterCommunHelper;

  /**
   *
   * @param class-string<TEntityClass> $entityClass
   */
  public function __construct(ManagerRegistry $registry, string $entityClass, protected readonly EntityVisibilityFilter $entitySecurityFilter) {
    parent::__construct($registry, $entityClass);
  }

  protected string $alias = 'base_alias';

  /**
   *
   * @var array<string, bool>|null
   */
  private ?array $fields = null;

  /**
   *
   * @var array<string, bool>|null
   */
  private ?array $associations = null;

  private bool $debugSql = false;

  /**
   * Finds an entity by its id and filters by security.
   *
   * @return object|null
   * @phpstan-return TEntityClass|null
   */
  public function findWithSecurity(int $id, User $user): ?AbstractBaseEntity {
    $alias = $this->alias;
    $qb = $this->initialiseQueryFilter($alias);
    $qb->andWhere(sprintf('%s.id = :entity_identifiant_key', $alias))->setParameter('entity_identifiant_key', $id);
    $entityClass = $this->getClassName();
    $this->entitySecurityFilter->apply($qb, $user, $entityClass, $alias);
    /**
     *
     * @phpstan-var TEntityClass|null $result
     */
    $result = $qb->getQuery()->getOneOrNullResult();
    return $result;
  }

  /**
   *
   * {@inheritdoc}
   * @see \App\Repository\Interfaces\BaseRepositoryInterface::loadAll()
   */
  public function loadAll(int $page, int $limit, User $user, array $filters = []): CollectionResultsDto {
    if ($page < 1) {
      $page = 1;
    }
    if ($filters === []) {
      return new CollectionResultsDto(items: [], total: 0, page: $page, limit: $limit);
    }

    $key = $this->alias;
    $entityClass = $this->getClassName();
    $qb = $this->initialiseQueryFilter($key);
    $this->search($qb, $filters, $key);
    $this->entitySecurityFilter->apply($qb, $user, $entityClass, $key);
    $this->filters($qb, $filters['filters'], $key);
    $this->sorts($qb, $filters['sorts'], $key);
    $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);
    if ($this->debugSql) {
      $dbg = [
        'sql' => $qb->getQuery()->getSQL(),
        'dsql' => $qb->__toString()
      ];
      \Stephane888\Debug\debugLog::symfonyDebug($dbg, 'loadAll_query___' . $this->getEntityName() . '___', true);
    }
    /** @var array<int, TEntityClass> $items */
    $items = $qb->getQuery()->getResult();
    //
    $totalQueryBuilder = $this->initialiseQueryFilter($key);
    $this->entitySecurityFilter->apply($totalQueryBuilder, $user, $entityClass, $key);
    $this->filters($totalQueryBuilder, $filters['filters'], $key);
    $total = $totalQueryBuilder->select('COUNT(' . $key . '.id)')
      ->getQuery()
      ->getSingleScalarResult();
    return new CollectionResultsDto(items: $items, total: (int) $total, page: $page, limit: $limit);
  }

  /**
   * Effectue une recherche.
   *
   * @param QueryBuilder $qb
   * @param array<string, mixed> $filters
   * @param string $alias
   */
  private function search(QueryBuilder $qb, array $filters, string $alias): void {
    $search = trim((string) ($filters['search'] ?? ''));
    if ($search === '' || ($filters['search_fields'] ?? []) === []) {
      return;
    }
    $expressions = [];
    $textParamName = $alias . '_custom_search_text';
    $idParamName = $alias . '_custom_search_id';
    foreach ($filters['search_fields'] as $fieldname => $info) {
      $fieldname = "$alias.$fieldname";
      if (($info['sortable'] ?? false) !== true) {
        continue;
      }
      $typeField = $info['type_orm'] ?? 'string';
      switch ($typeField) {
        case 'int':
        case 'integer':
        case 'bigint':
          if (! ctype_digit($search)) {
            continue 2;
          }
          $expressions[] = $qb->expr()->eq($fieldname, ':' . $idParamName);
          break;
        case 'float':
        case 'decimal':
          if (! is_numeric($search)) {
            continue 2;
          }
          $numberParamName = $alias . '_custom_search_number';
          $expressions[] = $qb->expr()->eq($fieldname, ':' . $numberParamName);
          $qb->setParameter($numberParamName, (float) $search);
          break;
        case 'string':
        case 'text':
          $expressions[] = $qb->expr()->like('LOWER(' . $fieldname . ')', ':' . $textParamName);
          break;

        default:
          continue 2;
      }
    }
    if ($expressions === []) {
      return;
    }
    $qb->andWhere($qb->expr()
      ->orX(...$expressions));
    $qb->setParameter($textParamName, '%' . mb_strtolower($search) . '%');
    if (ctype_digit($search)) {
      $qb->setParameter($idParamName, (int) $search);
    }
  }

  public function countByOwner(User $owner): int {
    $entityClass = $this->getClassName();
    if (! is_a($entityClass, OwnerInterface::class, true)) {
      throw new \LogicException(sprintf('La classe "%s" doit implémenter OwnerInterface pour utiliser countByOwnerWithSecurityFilter().', $entityClass));
    }
    $rootAlias = $this->alias;
    $qb = $this->createQueryBuilder($rootAlias)
      ->select(sprintf('COUNT(%s.id)', $rootAlias))
      ->andWhere(sprintf('%s.owner = :owner', $rootAlias))
      ->setParameter('owner', $owner);
    $this->entitySecurityFilter->apply($qb, $owner, $entityClass, $rootAlias);
    $result = $qb->getQuery()->getSingleScalarResult();
    return (int) $result;
  }

  /**
   *
   * @param \Doctrine\ORM\QueryBuilder $qb
   * @param array<int, array<string, mixed>> $filters
   * @param string $key
   */
  private function filters(QueryBuilder $qb, array $filters, string $key): void {
    if ($filters === []) {
      return;
    }
    $allowedOperators = [
      '=' => '=',
      '!=' => '!=',
      '>' => '>',
      '>=' => '>=',
      '<' => '<',
      '<=' => '<=',
      'LIKE' => 'LIKE'
    ];
    foreach ($filters as $index => $field) {
      $fieldName = $field['fieldname'];
      if ($fieldName === null || $fieldName === '') {
        throw new \InvalidArgumentException('Le champ de filtre est obligatoire.');
      }
      //
      $operator = strtoupper((string) ($field['operateur'] ?? '='));
      if (! isset($allowedOperators[$operator])) {
        throw new \InvalidArgumentException(sprintf('L\'opérateur "%s" est invalide.', $operator));
      }
      [
        $alias,
        $resolvedField
      ] = $this->resolveFilterField($qb, $key, $fieldName);
      $value = $field['value'] ?? null;
      $paramName = 'ft_' . $index . '_' . str_replace('.', '_', $fieldName);

      if ($operator === 'LIKE') {
        $value = '%' . (string) $value . '%';
      }
      $qb->andWhere($alias . '.' . $resolvedField . ' ' . $allowedOperators[$operator] . ' :' . $paramName)->setParameter($paramName, $value);
    }
  }

  /**
   * Résout un champ de filtre dynamique.
   *
   * Exemples :
   * - "name" => ["company", "name"]
   * - "companyUsers" => ["filter_companyUsers", "id"]
   * - "companyUsers.user" => ["filter_companyUsers", "user"]
   *
   * @return array{0: string, 1: string}
   */
  protected function resolveFilterField(QueryBuilder $qb, string $rootAlias, string $fieldName): array {
    // Cas 1 : champ simple sans point, exemple "name"
    if (! str_contains($fieldName, '.')) {
      if ($this->hasField($fieldName)) {
        return [
          $rootAlias,
          $fieldName
        ];
      }
      // Cas 2 : association seule, exemple "companyUsers"
      // On l'interprète comme "companyUsers.id"
      if ($this->hasAssociation($fieldName)) {
        $targetMetadata = $this->getEntityManager()->getClassMetadata($this->getClassMetadata()
          ->getAssociationTargetClass($fieldName));
        $identifierFields = $targetMetadata->getIdentifierFieldNames();
        if (count($identifierFields) !== 1) {
          throw new \InvalidArgumentException(sprintf('L\'association "%s" utilise une clé primaire composite non supportée.', $fieldName));
        }
        $associationField = $identifierFields[0];
        $joinAlias = $this->getAliasNameForJoin($fieldName);
        $this->addInnerJoinOnce($qb, $rootAlias . '.' . $fieldName, $joinAlias);
        return [
          $joinAlias,
          $associationField
        ];
      }
      throw new \InvalidArgumentException(sprintf('Le champ "%s" n\'existe pas sur l\'entité "%s".', $fieldName, $this->getClassName()));
    }
    // Cas 3 : association.champ, exemple "companyUsers.user"
    $parts = explode('.', $fieldName);
    if (count($parts) !== 2) {
      throw new \InvalidArgumentException(sprintf('Le champ "%s" est invalide. Un seul niveau d\'association est supporté.', $fieldName));
    }
    [
      $associationName,
      $associationField
    ] = $parts;
    if (! $this->hasAssociation($associationName)) {
      throw new \InvalidArgumentException(sprintf('L\'association "%s" n\'existe pas sur l\'entité "%s".', $associationName, $this->getClassName()));
    }
    $targetMetadata = $this->getEntityManager()->getClassMetadata($this->getClassMetadata()
      ->getAssociationTargetClass($associationName));

    if (! $targetMetadata->hasField($associationField) && ! $targetMetadata->hasAssociation($associationField)) {
      throw new \InvalidArgumentException(sprintf('Le champ "%s" n\'existe pas sur l\'association "%s".', $associationField, $associationName));
    }
    $joinAlias = $this->getAliasNameForJoin($associationName);
    $this->addInnerJoinOnce($qb, $rootAlias . '.' . $associationName, $joinAlias);
    return [
      $joinAlias,
      $associationField
    ];
  }

  /**
   *
   * @param array<int, mixed> $sorts
   */
  protected function sorts(QueryBuilder $qb, array $sorts, string $key): void {
    foreach ($sorts as $sort) {
      $fieldName = $sort['fieldname'];
      if ($fieldName === null || $fieldName === '') {
        throw new \InvalidArgumentException('Le champ de tri est obligatoire.');
      }
      if (! $this->hasField($fieldName)) {
        throw new \InvalidArgumentException(sprintf('Le champ "%s" n\'existe pas sur l\'entité "%s".', $fieldName, $this->getClassName()));
      }
      $order = strtoupper((string) ($sort['order'] ?? 'DESC'));
      if (! in_array($order, [
        'ASC',
        'DESC'
      ], true)) {
        throw new \InvalidArgumentException(sprintf('L\'ordre "%s" est invalide. Valeurs autorisées : ASC, DESC.', $order));
      }
      $qb->addOrderBy(sprintf('%s.%s', $key, $fieldName), $order);
    }
  }

  protected function hasField(string $fieldName): bool {
    if ($this->fields === null) {
      $this->fields = array_fill_keys($this->getClassMetadata()->getFieldNames(), true);
    }
    return isset($this->fields[$fieldName]);
  }

  protected function hasAssociation(string $associationName): bool {
    if ($this->associations === null) {
      $this->associations = array_fill_keys($this->getClassMetadata()->getAssociationNames(), true);
    }
    return isset($this->associations[$associationName]);
  }

  /**
   * Vérifie si une propriété existe dans une classe
   */
  protected function propertyExists(string $className, string $property): bool {
    return property_exists($className, $property);
  }

  /**
   * Toutes les methodes devraient integrer ce filtre, sauf des cas specifique.
   *
   * @param string $alias
   * @return \Doctrine\ORM\QueryBuilder
   */
  protected function initialiseQueryFilter(string $alias = 'usr') {
    $qb = $this->createQueryBuilder($alias);
    return $qb;
  }
}