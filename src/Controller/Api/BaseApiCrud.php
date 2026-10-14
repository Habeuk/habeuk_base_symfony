<?php
namespace Habeuk\HbkSymfony\Controller\Api;

use Psr\Log\LoggerInterface;
use Habeuk\HbkSymfony\Service\ {
  ApiDatasMapper
};
use Habeuk\HbkSymfony\Service\EntityReferenceResolver;
use Habeuk\HbkSymfony\Service\FrontendFormConfig;
use Habeuk\HbkSymfony\DTO\BaseDto;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Habeuk\HbkSymfony\Enum\PermissionEnum;
use Habeuk\HbkSymfony\Contract\OwnerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Doctrine\ORM\EntityManagerInterface;
use Habeuk\HbkSymfony\Controller\Traits\BaseApiTrait;
use Habeuk\HbkSymfony\EntityPolicy\EntityAccessChecker;

abstract class BaseApiCrud extends BaseApi {
  use BaseApiTrait;

  function __construct(protected readonly EntityManagerInterface $entityManager, protected readonly FrontendFormConfig $frontendFormConfig,
    protected readonly EntityReferenceResolver $entityResolver, protected readonly ApiDatasMapper $apiDatasMapper,
    protected readonly EntityAccessChecker $entityAccessChecker, LoggerInterface $logger) {
    parent::__construct($logger);
  }

  /**
   *
   * @param User $user
   * @return array<mixed>
   */
  protected function baseEntityDefinition(int $companyId, User $user): array {
    if ($this->formTypeClass !== null && $this->entityClass !== null && $this->entityDtoClass !== null) {
      $groups = [
        PermissionEnum::LIST->value
      ];
      $columns = $this->frontendFormConfig->getColumnLabels($this->entityDtoClass, $user, $groups);
      $availableFileds = [
        'label',
        'order',
        'description',
        'type',
        'sortable',
        'display'
      ];
      $cleanColumns = [];
      foreach ($columns as $fieldname => $info) {
        $infoNew = [];
        foreach ($availableFileds as $key) {
          if (isset($info[$key])) {
            $infoNew[$key] = $info[$key];
          }
        }
        if ($infoNew !== [])
          $cleanColumns[$fieldname] = $infoNew;
      }
      return [
        'columns' => $cleanColumns,
        'entity_config' => $this->frontendFormConfig->getEntityConfig($this->entityClass, $user),
        'enums' => $this->frontendFormConfig->extractEnumsFromDto($this->entityDtoClass, $user)
      ];
    }
    throw new \Exception("Une erreur s'est produite");
  }

  /**
   *
   * @param string $action
   * @param User $user
   * @return array<mixed>
   */
  protected function baseFormEntityDefinition(string $action, User $user, int $companyId): array {
    if ($this->formTypeClass !== null && $this->entityClass !== null && $this->entityDtoClass !== null) {
      $groups = [
        PermissionEnum::createFromValue($action)->value
      ];
      if ($user->isAdmin()) {
        $groups[] = BaseDto::ADMIN;
      }

      $fields = $this->frontendFormConfig->getFormFields($this->formTypeClass, $user, $groups);
      $emptyEntity = new $this->entityDtoClass();
      return [
        'schema' => $fields,
        'formData' => $this->loadContentArray($emptyEntity, $this->entityDtoClass, true)
      ];
    }
    throw new \Exception("Une erreur s'est produite");
  }

  /**
   * Sauvegarde en masse d'entités du même type.
   *
   * Payload accepté :
   * - {"items": [ {...}, {...} ]}
   * - [ {...}, {...} ]
   *
   * La sauvegarde est atomique : en cas d'échec d'un élément,
   * aucune entité n'est persistée.
   *
   * @return JsonResponse
   */
  protected function saveEntities(int $companyId, string $entityType, Request $request, User $user, ObjectMapperInterface $objectMapper): JsonResponse {
    if (! $this->baseValidation($entityType))
      return $this->json([
        'error' => 'Entity/Dto not found : ' . $entityType
      ], Response::HTTP_PRECONDITION_FAILED);
    if ($this->entityClass === null || $this->entityDtoClass === null) {
      throw new \LogicException('Entity/Dto non résolus.');
    }

    $data = json_decode($request->getContent(), true);
    $items = $data['items'] ?? $data;
    if (! is_array($items) || $items === []) {
      return $this->json([
        'error' => 'Aucune donnée à sauvegarder'
      ], Response::HTTP_BAD_REQUEST);
    }

    try {
      /** @var list<object> $savedEntities */
      $savedEntities = $this->entityManager->wrapInTransaction(function () use ($companyId, $entityType, $items, $user): array {
        $results = [];
        foreach ($items as $index => $item) {
          if (! is_array($item)) {
            throw new \InvalidArgumentException(sprintf("L'élément %d n'est pas un objet valide.", $index));
          }
          $results[] = $this->saveOneEntity($companyId, $entityType, $item, $user);
        }
        return $results;
      });

      return $this->json(array_map(fn (object $entity): array => $this->loadContentArray($entity, $this->entityDtoClass), $savedEntities));
    }
    catch (ValidationFailedException $e) {
      return $this->json([
        'error' => 'Certains champs ne sont pas bien remplie',
        'form_errors' => $e->getViolations()
      ], Response::HTTP_PRECONDITION_FAILED);
    }
    catch (\Throwable $e) {
      return $this->error($e, $user, "Une erreur s'est produite lors de la sauvegarde en masse");
    }
  }

  /**
   * Sauvegarde une seule entité à partir d'un payload brut.
   * Retourne l'entité persistée.
   *
   * @param array<mixed> $data
   */
  protected function saveOneEntity(int $companyId, string $entityType, array $data, User $user): object {
    if ($this->entityClass === null || $this->entityDtoClass === null) {
      throw new \LogicException('Entity/Dto non résolus.');
    }
    $entityDtoClass = $this->entityDtoClass;
    $entityClass = $this->entityClass;
    //
    $id = $data['id'] ?? null;
    if ($id !== null) {
      $accessDefinition = $this->entityAccessChecker->canEditDefinition($entityClass, $user);
      if (! $accessDefinition->granted) {
        throw new \RuntimeException($accessDefinition->message);
      }
      $action = PermissionEnum::EDIT;
      $groups = [
        $action->value,
        BaseDto::REFERENCE
      ];
    }
    else {
      $accessDefinition = $this->entityAccessChecker->canCreateDefinition($entityClass, $user);
      if (! $accessDefinition->granted) {
        throw new \RuntimeException($accessDefinition->message);
      }
      $action = PermissionEnum::CREATE;
      $groups = [
        $action->value
      ];
    }
    if ($user->isAdmin()) {
      $groups[] = BaseDto::ADMIN;
    }
    // Convertion en Dto avec validations.
    $dto = $this->apiDatasMapper->fromArrayToDto($data, $entityDtoClass, $groups);
    /**
     * passage à l'entite doctrine.
     */
    if ($action === PermissionEnum::CREATE) {
      /** @var BaseDto $prepareEntity */
      $prepareEntity = $this->apiDatasMapper->fromDtoToNewEntity($dto, $entityClass);
      $this->entityResolver->resolve($dto, $prepareEntity, $data);

      if ($prepareEntity instanceof OwnerInterface) {
        $prepareEntity->setOwner($user);
      }
      $entity = $this->apiDatasMapper->entityValidate($prepareEntity);
      // avant la creation on effectue la validation.
      $accessData = $this->entityAccessChecker->checkEntityData($entity, $user, PermissionEnum::CREATE);
      if (! $accessData->granted) {
        throw new \RuntimeException($accessData->message);
      }

      $this->entityManager->persist($entity);
      $this->performEntity($entity);
    }
    else { // PermissionEnum::EDIT
      $entityOld = $this->entityManager->getRepository($entityClass)->find($data['id']);
      if ($entityOld === null)
        throw new \Exception("L'entité n'existe plus");
      // on remplace les champs simples.( texte, number ... ).
      $prepareEntity = $this->apiDatasMapper->fromDtoMergeToEntity($dto, $entityOld);
      // on remplace les champs Reference.
      $this->entityResolver->resolve($dto, $prepareEntity, $data);
      $entity = $this->apiDatasMapper->entityValidate($prepareEntity);
      //
      if ($entity instanceof OwnerInterface && ! $entity->ownerIsDefinie()) {
        $entity->setOwner($user);
      }
      //

      $accessData = $this->entityAccessChecker->checkEntityData($entity, $user, PermissionEnum::EDIT);
      if (! $accessData->granted) {
        throw new \RuntimeException($accessData->message);
      }

      // On applique des logiques metiers.
      $this->performEntity($entity, $entityOld);
    }

    return $entity;
  }

  /**
   *
   * @param int $companyId
   * @param string $entityType
   * @param Request $request
   * @param User $user
   * @param ObjectMapperInterface $objectMapper
   * @return JsonResponse
   */
  protected function save(int $companyId, string $entityType, Request $request, User $user, ObjectMapperInterface $objectMapper): JsonResponse {
    if (! $this->baseValidation($entityType))
      return $this->json([
        'error' => 'Entity/Dto not found : ' . $entityType
      ], Response::HTTP_PRECONDITION_FAILED);
    if ($this->entityClass === null || $this->entityDtoClass === null) {
      throw new \LogicException('Entity/Dto non résolus.');
    }

    $data = json_decode($request->getContent(), true);
    $messageError = ($data['id'] ?? null) !== null ? "Une erreur s'est produite lors de mise à jour de votre contenu" : "Une erreur s'est produite lors de la mise à jour de votre contenu";

    try {
      $entity = $this->saveOneEntity($companyId, $entityType, $data, $user);
      return $this->json($this->loadContentArray($entity, $this->entityDtoClass));
    }
    catch (ValidationFailedException $e) {
      return $this->json([
        'error' => 'Certains champs ne sont pas bien remplie',
        'form_errors' => $e->getViolations()
      ], Response::HTTP_PRECONDITION_FAILED);
    }
    catch (\Throwable $e) {
      return $this->error($e, $user, $messageError);
    }
  }

  /**
   *
   * @deprecated use new save.
   * @param int $companyId
   * @param string $entityType
   * @param Request $request
   * @param User $user
   * @param ObjectMapperInterface $objectMapper
   * @return JsonResponse
   */
  protected function saveOld(int $companyId, string $entityType, Request $request, User $user, ObjectMapperInterface $objectMapper): JsonResponse {
    if (! $this->baseValidation($entityType))
      return $this->json([
        'error' => 'Entity/Dto not found : ' . $entityType
      ], Response::HTTP_PRECONDITION_FAILED);

    if ($this->entityClass !== null && $this->entityDtoClass !== null) {
      $entityDtoClass = $this->entityDtoClass;
      $entityClass = $this->entityClass;
      //
      $data = json_decode($request->getContent(), true);
      $id = $data['id'] ?? null;
      if ($id !== null) {
        $accessDefinition = $this->entityAccessChecker->canEditDefinition($entityClass, $user);
        if (! $accessDefinition->granted) {
          return $this->json([
            'error' => $accessDefinition->message
          ], $accessDefinition->statusCode);
        }
        $action = PermissionEnum::EDIT;
        $groups = [
          $action->value,
          BaseDto::REFERENCE
        ];
        $messageError = "Une erreur s'est produite lors de mise à jour de votre contenu";
      }
      else {
        $accessDefinition = $this->entityAccessChecker->canCreateDefinition($entityClass, $user);
        if (! $accessDefinition->granted) {
          return $this->json([
            'error' => $accessDefinition->message
          ], $accessDefinition->statusCode);
        }
        $action = PermissionEnum::CREATE;
        $groups = [
          $action->value
        ];
        $messageError = "Une erreur s'est produite lors de la mise à jour de votre contenu";
      }
      try {
        if ($user->isAdmin()) {
          $groups[] = BaseDto::ADMIN;
        }
        // Convertion en Dto avec validations.
        $dto = $this->apiDatasMapper->fromArrayToDto($data, $entityDtoClass, $groups);
        /**
         * passage à l'entite doctrine.
         */
        if ($action === PermissionEnum::CREATE) {
          /** @var BaseDto $prepareEntity */
          $prepareEntity = $this->apiDatasMapper->fromDtoToNewEntity($dto, $entityClass);
          // $prepareEntity = $objectMapper->map($dto);
          $this->entityResolver->resolve($dto, $prepareEntity, $data);

          if ($prepareEntity instanceof OwnerInterface) {
            $prepareEntity->setOwner($user);
          }
          $entity = $this->apiDatasMapper->entityValidate($prepareEntity);
          // avant la creation on effectue la validation.

          $accessData = $this->entityAccessChecker->checkEntityData($entity, $user, PermissionEnum::CREATE);
          if (! $accessData->granted) {
            return $this->json([
              'error' => $accessData->message
            ], $accessData->statusCode);
          }

          $this->entityManager->persist($entity);
          $this->performEntity($entity);
        }
        elseif ($action === PermissionEnum::EDIT) {
          $entityOld = $this->entityManager->getRepository($this->entityClass)->find($data['id']);
          if ($entityOld === null)
            throw new \Exception("L'entité n'existe plus");
          // $cloneEntityOld = clone $entityOld;
          // on remplace les champs simple.( texte, number ... ).
          // $prepareEntity = $objectMapper->map($dto, $entityOld);
          $prepareEntity = $this->apiDatasMapper->fromDtoMergeToEntity($dto, $entityOld);
          // on remplace les champs Reference.
          $this->entityResolver->resolve($dto, $prepareEntity, $data);
          $entity = $this->apiDatasMapper->entityValidate($prepareEntity);
          //
          if ($entity instanceof OwnerInterface && ! $entity->ownerIsDefinie()) {
            $entity->setOwner($user);
          }
          //

          $accessData = $this->entityAccessChecker->checkEntityData($entity, $user, PermissionEnum::EDIT);
          if (! $accessData->granted) {
            return $this->json([
              'error' => $accessData->message
            ], $accessData->statusCode);
          }

          // On applique des logiques metiers.
          $this->performEntity($entity, $entityOld);
        }

        return $this->json($this->loadContentArray($entity, $entityDtoClass));
      }
      catch (ValidationFailedException $e) {
        $messageError = "Certains champs ne sont pas bien remplie";
        return $this->json([
          'error' => $messageError,
          'form_errors' => $e->getViolations()
        ], Response::HTTP_PRECONDITION_FAILED);
      }
      catch (\Throwable $e) {
        return $this->error($e, $user, $messageError);
      }
    }
    throw new \Exception("Une erreur s'est produite");
  }

  /**
   *
   * @param object $entity ( la nouvelle entité)
   * @param object|null $entityOld (L'entité existant)
   */
  protected function performEntity(object $entity, object|null $entityOld = null): void {
    $this->entityManager->flush();
  }
}
