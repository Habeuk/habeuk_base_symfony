<?php
namespace Habeuk\HbkSymfony\Repository\Interfaces;

use Habeuk\HbkSymfony\DTO\Results\CollectionResultsDto;
use Habeuk\HbkSymfony\Shared\Doctrine\BaseUser;
use Habeuk\HbkSymfony\Shared\Doctrine\AbstractBaseEntity;

/**
 *
 * @template T of AbstractBaseEntity
 */
interface BaseRepositoryInterface {

  /**
   *
   * @param int $page
   * @param int $limit
   * @param array<string, mixed> $filters
   * @return CollectionResultsDto<T>
   */
  public function loadAll(int $page, int $limit, BaseUser $user, array $filters = []): CollectionResultsDto;

  public function countByOwner(BaseUser $user): int;

  /**
   * Finds an entity by its id and filters by security.
   *
   * @return AbstractBaseEntity|null
   * @phpstan-return T|null
   */
  public function findWithSecurity(int $id, BaseUser $user): ?AbstractBaseEntity;
}