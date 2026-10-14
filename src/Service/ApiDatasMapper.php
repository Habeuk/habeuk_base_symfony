<?php
namespace Habeuk\HbkSymfony\Service;

use Habeuk\HbkSymfony\DTO\BaseDto;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service de mapping des payloads JSON reçus par l'API
 * Convertit les données JSON en DTO ou Entités
 */
class ApiDatasMapper {

  public function __construct(private DenormalizerInterface $deNormalizer, private ValidatorInterface $validator, private readonly DtoMapper $dtoMapper) {}

  public function entityValidate(object $entity): object {
    $violations = $this->validator->validate($entity);
    if (count($violations) > 0) {
      throw new \Symfony\Component\Validator\Exception\ValidationFailedException($entity, $violations);
    }
    return $entity;
  }

  /**
   * Convertit un DTO en entité.
   *
   * @param object $dto
   * @param class-string|object $entityClass
   * @return object
   */
  public function toEntityWithValidate(object $dto, string|object $entityClass): object {
    $entity = $this->dtoMapper->toEntity($dto, $entityClass);
    // La validation permet de verifier les contraintes et aussi l'unicité des champs.
    // Cela se produit avant la sauvegarde ( reduis les erreurs de sauvegarde ).
    $violations = $this->validator->validate($entity);
    if (count($violations) > 0) {
      throw new \Symfony\Component\Validator\Exception\ValidationFailedException($entity, $violations);
    }
    return $entity;
  }

  /**
   * Convertit les entités en un Array en passant par les Dto.
   *
   * @param object $entity
   * @param class-string $dtoClass
   * @param array<int, string> $groups
   * @return array<mixed>
   */
  public function fromEntityToArray(object $entity, string $dtoClass, array $groups = [], bool $removeEmptyValue = true): array {
    $data = $this->dtoMapper->toArrayWithFilterGroups($entity, $dtoClass, $groups, $removeEmptyValue);
    return $data;
  }

  /**
   * Convertit un DTO en entité
   *
   * @param object $dto
   * @param class-string $entityClass
   * @return object
   */
  public function fromDtoToNewEntity(object $dto, string $entityClass): object {
    return $this->dtoMapper->toEntity($dto, $entityClass);
  }

  public function fromDtoMergeToEntity(object $dto, object $entityClass): object {
    return $this->dtoMapper->toEntity($dto, $entityClass);
  }

  /**
   * Met à jour une entité existante avec les données d'un DTO
   *
   * @param object $dto
   * @param object $existingEntity
   * @param array<int, string> $groups
   * @return object
   */
  public function updateEntity(object $dto, object $existingEntity, array $groups = []): object {
    return $this->dtoMapper->updateEntity($dto, $existingEntity, $groups);
  }

  /**
   *
   * @param array<mixed> $data
   * @param string $dtoClass
   * @param array<mixed> $groups
   * @return object
   */
  public function fromArrayToDto(array $data, string $dtoClass, array $groups): object {
    $context = [
      AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true
    ];
    if ($groups !== []) {
      $context['groups'] = $groups;
    }
    $dtoEntity = $this->deNormalizer->denormalize($data, $dtoClass, null, $context);
    $violations = $this->validator->validate($dtoEntity, null, $groups);
    if (count($violations) > 0) {
      throw new \Symfony\Component\Validator\Exception\ValidationFailedException($dtoEntity, $violations);
    }
    return $dtoEntity;
  }
}