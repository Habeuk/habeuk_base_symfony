<?php
namespace Habeuk\HbkSymfony\Enum;

/**
 * Interface de base pour tous les enums
 */
interface ScopeEnumInterface extends BaseEnumInterface {

  /**
   * Vérifie si le scope est personnel
   */
  public function isPersonal(): bool;

  /**
   * Vérifie si le scope est restreint.
   * Unqiuement pour les données restreintes (admin).
   */
  public function isRestricted(): bool;

  /**
   * Vérifie si le scope est global.
   */
  public function isGlobal(): bool;

  public function getPersonal(): static;
}
