<?php
namespace Habeuk\HbkSymfony\Controller\Traits;

use Habeuk\HbkSymfony\DTO\BaseDto;
use App\Entity\User;
use Habeuk\HbkSymfony\Repository\Interfaces\BaseRepositoryInterface;
use Habeuk\HbkSymfony\Shared\Doctrine\AbstractBaseEntity;
use Habeuk\HbkSymfony\Service\ {
  ApiDatasMapper
};
use Webmozart\Assert\Assert;

trait BaseApiTrait {

  /**
   * Charge un contenu de maniere serisé.
   *
   * @param object $entity
   * @param class-string $entityDtoClass
   * @param bool $removeEmptyValue
   * @param array<string> $groups
   * @return array<mixed>
   */
  protected function loadContentArray(object $entity, string $entityDtoClass, bool $removeEmptyValue = true, array $groups = []): array {
    Assert::subclassOf($entityDtoClass, BaseDto::class, 'Le paramètre $entityDtoClass doit être une classe DTO (héritant de BaseDto). Reçu : %s');
    if ($groups === []) {
      $groups = [
        BaseDto::REFERENCE,
        BaseDto::EDIT,
        BaseDto::LIST,
        BaseDto::VIEW
      ];
    }
    /** @var ApiDatasMapper $apiDatasMapper */
    $apiDatasMapper = $this->apiDatasMapper;
    return $apiDatasMapper->fromEntityToArray($entity, $entityDtoClass, $groups, $removeEmptyValue);
  }

  /**
   *
   * @template T of AbstractBaseEntity
   * @param BaseRepositoryInterface<T> $baseRepository
   * @param int $page
   * @param int $limit
   * @param User $user
   * @param array<mixed> $params
   * @param class-string $entityDtoClass
   * @param array<string> $groups
   * @return array<string, mixed>
   */
  protected function baseLoadEntities(BaseRepositoryInterface $baseRepository, int $page, int $limit, User $user, array $params, string $entityDtoClass,
    array $groups = []): array {
    $collectionResultDto = $baseRepository->loadAll($page, $limit, $user, $params);
    if ($groups === [])
      $groups = [
        BaseDto::REFERENCE,
        BaseDto::LIST
      ];
    /** @var ApiDatasMapper $apiDatasMapper */
    $apiDatasMapper = $this->apiDatasMapper;
    return $collectionResultDto->toArray(fn ($item) => $apiDatasMapper->fromEntityToArray($item, $entityDtoClass, $groups));
  }
}