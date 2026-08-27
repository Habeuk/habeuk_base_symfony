<?php
namespace Habeuk\HbkSymfony\Enum;

/**
 * Interface de base pour tous les enums
 */
interface RoleInterface extends BaseEnumInterface {

  /**
   *
   * @return array<static>
   */
  public function inherits(): array;
}