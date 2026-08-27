<?php
namespace Habeuk\HbkSymfony\Service;

use App\DTO\UserDto;
use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Service de mapping entre entités et DTO
 *
 * @see https://symfony.com/doc/current/object_mapper.html
 */
class DtoMapper {

  public function __construct(private SerializerInterface $serializer, private NormalizerInterface $normalizer, private DenormalizerInterface $deNormalizer,
    private ObjectMapperInterface $objectMapper, private ValidatorInterface $validator, private UserPasswordHasherInterface $passwordHasher) {}

  /**
   * Convertit une entité en DTO
   *
   * @param object $entity L'entité source
   * @param class-string|object $dtoClass La classe DTO cible
   * @return object Le DTO rempli
   */
  public function toDto(object $entity, string|object $dtoClass): object {
    return $this->objectMapper->map($entity, $dtoClass);
  }

  /**
   * Convertie une entité (doctrine) en Array en passant par sont Dto.
   *
   * @param object $entity
   * @param class-string $dtoClass
   * @param array<string> $groups
   * @return array<mixed>
   */
  public function toArrayWithFilterGroups(object $entity, string $dtoClass, array $groups = [], bool $removeEmptyValue = true): array {
    $context = [
      AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true,
      AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($obj) {
        return $obj ? $obj->getId() : null;
      }
    ];
    if ($removeEmptyValue) {
      $context[AbstractObjectNormalizer::SKIP_NULL_VALUES] = true;
    }
    if ($groups !== []) {
      $context[AbstractNormalizer::GROUPS] = $groups;
    }

    // Conversion en Dto.
    $entityDto = $this->objectMapper->map($entity, $dtoClass);
    // On filtre les données.
    $data = $this->normalizer->normalize($entityDto, null, $context);
    if (is_array($data)) {
      return $data;
    }
    throw new \ErrorException("Le type de retour doit etre un array");
  }

  /**
   *
   * @param object $entity
   * @param class-string $dtoClass
   * @param array<int, string> $groups
   * @return object
   */
  public function toDtoWithFilterGroups(object $entity, string $dtoClass, array $groups = []): object {
    $context = [
      AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true,
      AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => fn ($obj) => $obj->getId() ?? null,
      AbstractObjectNormalizer::SKIP_NULL_VALUES => true
    ];
    if ($groups !== []) {
      $context[AbstractNormalizer::GROUPS] = $groups;
    }
    /**
     * On a besoin de cette etape afin de remplir/recuperer certains champs via setter...
     * ou getter...
     */
    $data = $this->objectMapper->map($entity, $dtoClass);
    /**
     * Le champs date pose problemes, il faut normalize avant de denormalisé.
     */
    $data = $this->normalizer->normalize($data, null, $context);
    $result = $this->deNormalizer->denormalize($data, $dtoClass, null, $context);
    // $dbg = [
    // '$data' => $data,
    // '$entity' => $entity,
    // '$result' => $result,
    // '$context' => $context
    // ];
    // \Stephane888\Debug\debugLog::symfonyDebug($dbg, 'toDtoWithFilterGroups', true);
    return $result;
  }

  /**
   *
   * @deprecated pas tres utile (à voir).
   * @template T of object
   * @param object $entity
   * @param class-string<T> $dtoClass
   * @param string[] $groups
   * @return object
   */
  public function toDtoWithFilterGroups2(object $entity, string $dtoClass, array $groups = []): object {
    $context = [
      AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
      AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => fn ($obj) => $obj->getId()
    ];
    if ($groups !== []) {
      $context[AbstractNormalizer::GROUPS] = $groups;
    }
    /**
     *
     * @var \Symfony\Component\Serializer\Debug\TraceableSerializer $serialiser
     */
    $serialiser = $this->serializer;
    $data = $serialiser->normalize($entity, null, []);
    // On dénormalize le tableau vers le DTO
    $results = $serialiser->denormalize($data, $dtoClass, null, $context);
    return $results;
  }

  /**
   * Convertit un DTO en entité
   *
   * @param object $dto
   * @param class-string|object $entityClass
   * @return object
   */
  public function toEntity(object $dto, string|object $entityClass): object {
    if ($dto instanceof UserDto && is_string($entityClass) && $entityClass === User::class) {
      return $this->toUserEntity($dto, $entityClass);
    }
    return $this->objectMapper->map($dto, $entityClass);
  }

  /**
   *
   * @param object $dto
   * @param string $entityClass
   * @param array<int, string> $groups
   * @return object
   */
  public function toEntityWithFilterGroups(object $dto, string $entityClass, array $groups = []): object {
    $context = [];
    if ($groups !== []) {
      $context['groups'] = $groups;
    }
    $context[AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES] = false;
    $entityentity = $this->normalizer->normalize($dto, null, $context);
    return $this->deNormalizer->denormalize($entityentity, $entityClass, null, $context);
  }

  /**
   * Convertit un DTO et valide le résultat
   *
   * @param object $dto
   * @param string $entityClass
   * @param array<int, string> $groups
   * @return object
   */
  public function toEntityAndValidate(object $dto, string $entityClass, array $groups = []): object {
    $entity = $this->toEntityWithFilterGroups($dto, $entityClass, $groups);
    $violations = $this->validator->validate($entity);
    if (count($violations) > 0) {
      throw new \Symfony\Component\Validator\Exception\ValidationFailedException($entity, $violations);
    }
    return $entity;
  }

  /**
   *
   * @param object $entity
   * @param class-string $dtoClass
   * @param array<int, string> $groups
   * @return object
   */
  public function toDtoAndValidate(object $entity, string $dtoClass, array $groups = []): object {
    $dto = $this->toDtoWithFilterGroups($entity, $dtoClass, $groups);

    $violations = $this->validator->validate($dto);
    if (count($violations) > 0) {
      throw new \Symfony\Component\Validator\Exception\ValidationFailedException($dto, $violations);
    }

    return $dto;
  }

  /**
   * Met à jour une entité existante avec les données d'un DTO
   *
   * @deprecated ne fonctionne pas.
   * @param object $dto
   * @param object $existingEntity
   * @param array<int, string> $groups
   * @return object
   */
  public function updateEntity(object $dto, object $existingEntity, array $groups = []): object {
    $context = [
      AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true,
      AbstractNormalizer::OBJECT_TO_POPULATE => $existingEntity,
      AbstractNormalizer::IGNORED_ATTRIBUTES => [
        'createdAt',
        'updatedAt',
        'owner'
      ]
    ];
    if ($groups !== []) {
      $context['groups'] = $groups;
    }
    /**
     * Le Dto peut contenir des champs donc on ne souhaite pas modifier, car ces champs permettent juste de transporter les données pour l'affichage.
     * On les retire à nouveau et on recupere exclussiment les champs autorisé à l'edition.
     */
    $data = $this->normalizer->normalize($dto, null, $context);
    if ($dto instanceof \App\DTO\QuoteLineDto)
      \Stephane888\Debug\debugLog::symfonyDebug([
        '$dto' => $dto,
        '$data' => $data,
        '$groups' => $groups
      ], 'afterNormalise', true);
    $entity = $this->deNormalizer->denormalize($data, get_class($existingEntity), null, $context);
    if ($dto instanceof \App\DTO\QuoteLineDto)
      \Stephane888\Debug\debugLog::symfonyDebug($entity, 'afterMergeEntity', true);
    return $entity;
  }

  /**
   * Convertit une collection d'entités en collection de DTO
   *
   * @param iterable<object> $entities
   * @param class-string $dtoClass
   * @param array<int, string> $groups
   * @return array<object>
   */
  public function toDtoCollection(iterable $entities, string $dtoClass, array $groups = []): array {
    $result = [];
    foreach ($entities as $entity) {
      $result[] = $this->toDtoWithFilterGroups($entity, $dtoClass, $groups);
    }
    return $result;
  }

  /**
   * Convertit un DTO en entité User
   *
   * @param UserDto $dto
   * @param class-string|User $entityClass
   * @return User
   */
  public function toUserEntity(UserDto $dto, string|User $entityClass): User {
    $user = $this->objectMapper->map($dto, $entityClass);
    if ($user instanceof User) {
      if ($dto->plainPassword !== null) {
        $hashed = $this->passwordHasher->hashPassword($user, $dto->plainPassword);
        $user->setHashedPassword($hashed);
      }
      return $user;
    }
    throw new \RuntimeException('Expected User, got ' . get_class($user));
  }

  /**
   * helper function.
   *
   * @param User $entity
   * @param class-string|object $dtoClass
   * @return UserDto
   */
  public function toUserDto(User $entity, string|object $dtoClass): UserDto {
    $result = $this->toDto($entity, $dtoClass);
    if ($result instanceof UserDto) {
      return $result;
    }
    throw new \RuntimeException('Expected UserDto, got ' . get_class($result));
  }
}