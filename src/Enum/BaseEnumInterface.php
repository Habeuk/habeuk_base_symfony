<?php
namespace Habeuk\HbkSymfony\Enum;

/**
 * Interface de base pour tous les enums
 */
interface BaseEnumInterface {

  /**
   * Retourne la valeur technique (ex: 'ROLE_USER')
   */
  public function getValue(): string;

  /**
   * Retourne le libellé lisible (ex: 'Utilisateur standard')
   */
  public function getLabel(): string;

  /**
   * Retourne toutes les valeurs possibles
   *
   * @return array<string|int,string|int>
   */
  public static function values(): array;

  /**
   * Retourne tous les libellés
   *
   * @return array<string|int,string|int>
   */
  public static function labels(): array;

  /**
   * Retourne un tableau associatif [valeur => libellé]
   *
   * @return array<string|int,string|int>
   */
  public static function choices(): array;

  /**
   * Crée une instance à partir d'une valeur technique
   *
   * @throws \InvalidArgumentException si la valeur n'existe pas
   */
  public static function createFromValue(string $value): static;

  /**
   * recupere les enums à partir des string.
   * Elle peut aussi servir de validation.
   *
   * @param array<string> $values
   * @return static[]
   */
  public static function createFromValues(array $values): array;

  /**
   * Crée une instance à partir d'un libellé
   *
   * @throws \InvalidArgumentException si le libellé n'existe pas
   */
  public static function createFromLabel(string $label): static;

  /**
   * Tente de créer une instance à partir d'une valeur, retourne null si échec
   */
  public static function tryFromValue(string $value): ?static;

  /**
   * Tente de créer une instance à partir d'un libellé, retourne null si échec
   */
  public static function tryFromLabel(string $label): ?static;

  /**
   * Vérifie si une valeur existe
   */
  public static function isValid(string $value): bool;

  /**
   * Vérifie si un libellé existe
   */
  public static function isValidLabel(string $label): bool;
}