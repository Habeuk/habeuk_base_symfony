<?php
namespace Habeuk\HbkSymfony\Service;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Habeuk\HbkSymfony\Attribute\ {
  EntityReference,
  EntityCollectionReference
};
use App\Entity\ {
  User
};
use App\DTO\UserDto;

class EntityReferenceResolver {

  public function __construct(private readonly EntityManagerInterface $em, private UserPasswordHasherInterface $passwordHasher) {}

  /**
   * Résout toutes les références EntityReference dans un DTO
   *
   * @param object $dto
   * @param object $prepareEntity
   * @param array<string, mixed> $data
   */
  public function resolve(object $dto, object $prepareEntity, array $data): void {
    if ($prepareEntity instanceof User && $dto instanceof UserDto) {
      if ($dto->plainPassword !== null) {
        $hashed = $this->passwordHasher->hashPassword($prepareEntity, $dto->plainPassword);
        $prepareEntity->setHashedPassword($hashed);
      }
    }
    //
    $reflection = new \ReflectionClass($dto);
    foreach ($reflection->getProperties() as $property) {
      /** @var \ReflectionProperty $property */
      $attributes = $property->getAttributes(EntityReference::class);
      if ($attributes !== []) {
        $this->applyEntityReference($attributes, $property, $prepareEntity, $data);
        continue;
      }
      $attributes = $property->getAttributes(EntityCollectionReference::class);
      if ($attributes !== []) {
        $this->applyEntityCollectionReference($attributes, $property, $prepareEntity, $data);
        continue;
      }
    }
  }

  /**
   * Applique la résolution pour une collection d'entités.
   *
   * @param array<\ReflectionAttribute<object>> $attributes
   * @param \ReflectionProperty $property
   * @param object $prepareEntity
   * @param array<string, mixed> $data
   */
  private function applyEntityCollectionReference(array $attributes, \ReflectionProperty $property, object $prepareEntity, array $data): void {
    $fieldName = $property->getName();
    if (! isset($data[$fieldName]) || ! is_array($data[$fieldName])) {
      return;
    }
    $ids = array_map(function ($item) {
      return is_array($item) ? ($item['id'] ?? null) : ($item->id ?? null);
    }, $data[$fieldName]);
    $ids = array_filter($ids, fn ($id) => $id !== null);
    if ($ids === [])
      return;
    $reflectionEntity = new \ReflectionClass($prepareEntity);
    // La proprieté definit dans Dto doit exister dans l'entité :
    if (! $reflectionEntity->hasProperty($fieldName)) {
      throw new \ErrorException(sprintf('Property "%s" does not exist in entity "%s"', $fieldName, $reflectionEntity->getName()));
    }

    /** @var EntityCollectionReference $reference */
    $reference = $attributes[0]->newInstance();
    // Charger les entités
    /**
     *
     * @var array<int, \Habeuk\HbkSymfony\Shared\Doctrine\AbstractBaseEntity> $entities
     */
    $entities = $this->em->getRepository($reference->entityClass)->findBy([
      'id' => $ids
    ]);
    // Maintenir l'ordre original
    $entityMap = [];
    foreach ($entities as $entity) {
      $entityMap[$entity->getId()] = $entity;
    }
    $clearMethod = $this->getClearMethodName($fieldName);
    $addMethod = $this->getAddMethodName($fieldName);
    if (method_exists($prepareEntity, $clearMethod) && method_exists($prepareEntity, $addMethod)) {
      // @phpstan-ignore-next-line
      $prepareEntity->$clearMethod();
      foreach ($ids as $id) {
        if (isset($entityMap[$id]) && method_exists($prepareEntity, $addMethod)) {
          // @phpstan-ignore-next-line
          $prepareEntity->$addMethod($entityMap[$id]);
        }
      }
    }
    else {
      throw new \ErrorException(sprintf('Entity "%s" must have methods "%s()" and "%s()" for collection "%s"', get_class($prepareEntity), $clearMethod, $addMethod, $fieldName));
    }
  }

  /**
   * Applique la résolution pour une référence simple
   *
   * @param array<\ReflectionAttribute<object>> $attributes
   * @param \ReflectionProperty $property
   * @param object $prepareEntity
   * @param array<string, mixed> $data
   */
  private function applyEntityReference(array $attributes, \ReflectionProperty $property, object $prepareEntity, array $data): void {
    $fieldName = $property->getName();
    // Si la donnée n'existe pas ou est null, on ne fait rien (relation optionnelle)
    if (! isset($data[$fieldName])) {
      return;
    }
    $entityId = $data[$fieldName]['id'] ?? null;
    /** @var EntityReference $reference */
    $reference = $attributes[0]->newInstance();
    // Vérifier que la propriété existe dans l'entité
    $reflectionEntity = new \ReflectionClass($prepareEntity);
    if (! $reflectionEntity->hasProperty($fieldName)) {
      throw new \ErrorException(sprintf('Property "%s" does not exist in entity "%s"', $fieldName, $reflectionEntity->getName()));
    }
    $propertyEntity = $reflectionEntity->getProperty($property->getName());
    $entity = null;
    if ($entityId !== null) {
      $entity = $this->em->getRepository($reference->entityClass)->find($entityId);
    }
    $propertyEntity->setAccessible(true);
    $propertyEntity->setValue($prepareEntity, $entity);
  }

  private function getAddMethodName(string $fieldName): string {
    $singular = rtrim($fieldName, 's');
    return 'add' . ucfirst($singular);
  }

  private function getRemoveMethodName(string $fieldName): string {
    $singular = rtrim($fieldName, 's');
    return 'remove' . ucfirst($singular);
  }

  private function getClearMethodName(string $fieldName): string {
    return 'clear' . ucfirst($fieldName);
  }
}