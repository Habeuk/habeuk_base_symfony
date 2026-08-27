<?php
// src/DTO/CollectionResultDto.php
namespace Habeuk\HbkSymfony\DTO\Results;

use Habeuk\HbkSymfony\Shared\Doctrine\AbstractBaseEntity;

/**
 * DTO pour le résultat paginé d'une collection
 *
 * @template T of AbstractBaseEntity
 */
class CollectionResultsDto {

  /**
   *
   * @param array<int, T> $items
   * @param int $total
   * @param int $page
   * @param int $limit
   */
  public function __construct(public readonly array $items, public readonly int $total, public readonly int $page = 1, public readonly int $limit = 20) {}

  /**
   *
   * @return int
   */
  public function getTotalPages(): int {
    return (int) ceil($this->total / $this->limit);
  }

  /**
   * Retourne les items mappés en DTO
   *
   * @template U of AbstractBaseEntity
   * @param callable(T): U $mapper
   * @return array<int, U>
   */
  public function getItems(callable $mapper): array {
    return array_map($mapper, $this->items);
  }

  /**
   *
   * @return bool
   */
  public function hasNextPage(): bool {
    return $this->page < $this->getTotalPages();
  }

  /**
   *
   * @return bool
   */
  public function hasPreviousPage(): bool {
    return $this->page > 1;
  }

  /**
   *
   * @return int
   */
  public function getFrom(): int {
    return ($this->page - 1) * $this->limit + 1;
  }

  /**
   *
   * @return int
   */
  public function getTo(): int {
    return min($this->page * $this->limit, $this->total);
  }

  /**
   *
   * @return array<string, mixed>
   */
  public function toArray(callable $mapper): array {
    return [
      'items' => array_map($mapper, $this->items),
      'total' => $this->total,
      'page' => $this->page,
      'limit' => $this->limit,
      'totalPages' => $this->getTotalPages(),
      'hasNextPage' => $this->hasNextPage(),
      'hasPreviousPage' => $this->hasPreviousPage(),
      'from' => $this->getFrom(),
      'to' => $this->getTo()
    ];
  }
}