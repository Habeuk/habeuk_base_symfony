<?php
// src/Service/EntityTypeResolver.php
namespace Habeuk\HbkSymfony\Service;

use Doctrine\ORM\Mapping as ORM;

class EntityTypeResolverService {

  function __construct(private readonly CacheManager $cacheManager) {}

  /**
   *
   * @param class-string $entityClass
   * @param string $propertyName
   * @return string
   */
  public function findTypeField(string $entityClass, string $propertyName): string {
    $cacheKey = $this->cacheManager->key('entity_form_fields', [
      'entity' => $entityClass,
      'field' => $propertyName
    ]);
    return $this->cacheManager->get($cacheKey, fn () => $this->doFindTypeField($entityClass, $propertyName));
  }

  /**
   * Vérifie si la propriété est de type string
   *
   * @param class-string $entityClass
   * @param string $propertyName
   * @return bool
   */
  public function isString(string $entityClass, string $propertyName): bool {
    return $this->findTypeField($entityClass, $propertyName) === 'string';
  }

  /**
   * Vérifie si la propriété est de type int
   *
   * @param class-string $entityClass
   * @param string $propertyName
   * @return bool
   */
  public function isInt(string $entityClass, string $propertyName): bool {
    return $this->findTypeField($entityClass, $propertyName) === 'int';
  }

  /**
   * Vérifie si la propriété est de type float
   *
   * @param class-string $entityClass
   * @param string $propertyName
   * @return bool
   */
  public function isFloat(string $entityClass, string $propertyName): bool {
    return $this->findTypeField($entityClass, $propertyName) === 'float';
  }

  /**
   * Vérifie si la propriété est de type bool
   *
   * @param class-string $entityClass
   * @param string $propertyName
   * @return bool
   */
  public function isBool(string $entityClass, string $propertyName): bool {
    return $this->findTypeField($entityClass, $propertyName) === 'bool';
  }

  /**
   * Vérifie si la propriété est un objet (relation ManyToOne/OneToOne)
   *
   * @param class-string $entityClass
   * @param string $propertyName
   * @return bool
   */
  public function isObject(string $entityClass, string $propertyName): bool {
    return $this->findTypeField($entityClass, $propertyName) === 'object';
  }

  /**
   * Vérifie si la propriété est une collection (OneToMany/ManyToMany)
   *
   * @param class-string $entityClass
   * @param string $propertyName
   * @return bool
   */
  public function isCollection(string $entityClass, string $propertyName): bool {
    return $this->findTypeField($entityClass, $propertyName) === 'collection';
  }

  /**
   * Vérifie si la propriété est un array (json, array)
   *
   * @param class-string $entityClass
   * @param string $propertyName
   * @return bool
   */
  public function isArray(string $entityClass, string $propertyName): bool {
    return $this->findTypeField($entityClass, $propertyName) === 'array';
  }

  /**
   * Vérifie si la propriété est une date (objet DateTime)
   *
   * @param class-string $entityClass
   * @param string $propertyName
   * @return bool
   */
  public function isDateTime(string $entityClass, string $propertyName): bool {
    $type = $this->findTypeField($entityClass, $propertyName);
    return $type === 'object' && $this->isDateTimeType($entityClass, $propertyName);
  }

  /**
   * Vérifie si c'est un champ date/DateTime
   *
   * @param class-string $entityClass
   * @param string $propertyName
   * @return bool
   */
  private function isDateTimeType(string $entityClass, string $propertyName): bool {
    $reflectionEntity = new \ReflectionClass($entityClass);
    $property = $reflectionEntity->getProperty($propertyName);

    $type = $property->getType();
    if ($type !== null) {
      /** @var \ReflectionNamedType $type */
      $typeName = $type->getName();
      return $typeName === \DateTimeInterface::class || $typeName === \DateTimeImmutable::class || $typeName === \DateTime::class;
    }

    return false;
  }

  /**
   * Détermine le type d'une propriété d'entité
   *
   * @param class-string $entityClass
   * @param string $propertyName
   * @return string
   */
  private function doFindTypeField(string $entityClass, string $propertyName): string {
    $reflectionEntity = new \ReflectionClass($entityClass);

    if (! $reflectionEntity->hasProperty($propertyName)) {
      throw new \ErrorException(sprintf('Property "%s" does not exist in entity "%s"', $propertyName, $reflectionEntity->getName()));
    }

    $property = $reflectionEntity->getProperty($propertyName);

    // Vérifier les attributs Doctrine
    $columnAttrs = $property->getAttributes(ORM\Column::class);
    if ($columnAttrs !== []) {
      /**
       *
       * @var \Doctrine\ORM\Mapping\Column $column
       */
      $column = $columnAttrs[0]->newInstance();
      if ($column->type !== null)
        return $this->mapDoctrineTypeToPhp($column->type);
    }

    // Vérifier les associations
    if ($property->getAttributes(ORM\ManyToOne::class) !== [] || $property->getAttributes(ORM\OneToOne::class) !== []) {
      return 'object';
    }

    // Vérifier les collections
    if ($property->getAttributes(ORM\OneToMany::class) !== [] || $property->getAttributes(ORM\ManyToMany::class) !== []) {
      return 'collection';
    }

    // Vérifier le typehint PHP
    $type = $property->getType();
    if ($type !== null) {
      /** @var \ReflectionNamedType $type */
      $typeName = $type->getName();
      if (in_array($typeName, [
        'int',
        'float',
        'string',
        'bool'
      ], true)) {
        return $typeName;
      }
      if (class_exists($typeName)) {
        return 'object';
      }
    }
    return 'mixed';
  }

  private function mapDoctrineTypeToPhp(string $doctrineType): string {
    return match ($doctrineType) {
      'integer', 'bigint', 'smallint' => 'int',
      'decimal', 'float' => 'float',
      'boolean' => 'bool',
      'string', 'text', 'guid' => 'string',
      'datetime', 'datetimetz', 'date', 'time', 'datetime_immutable' => 'object',
      'json', 'array', 'simple_array' => 'array',
      default => 'mixed'
    };
  }
}