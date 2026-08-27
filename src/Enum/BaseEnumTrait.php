<?php
namespace Habeuk\HbkSymfony\Enum;

trait BaseEnumTrait {

  /**
   * Retourne la valeur de l'enum
   */
  public function getValue(): string {
    return $this->value;
  }

  /**
   * Retourne tous les cas sous forme de tableau de valeurs
   *
   * @return array<string|int,string|int>
   */
  public static function values(): array {
    return array_column(self::cases(), 'value');
  }

  /**
   * Retourne tous les libellés
   *
   * @return array<string|int,string|int>
   */
  public static function labels(): array {
    return array_map(fn ($case) => $case->getLabel(), self::cases());
  }

  /**
   *
   * @return array<string,string|int>
   */
  public static function getAvailableRoles(): array {
    $a1 = array_map(fn ($case) => $case->getLabel(), self::cases());
    $a2 = array_map(fn ($case) => $case->value, self::cases());
    /**
     * Les options sont inversé dans symfony 'label'=>'value'
     */
    return array_combine($a1, $a2);
  }

  /**
   * Retourne un tableau [valeur => libellé]
   *
   * @return array<string|int,string|int>
   */
  public static function choices(): array {
    $choices = [];
    foreach (self::cases() as $case) {
      $choices[$case->getValue()] = $case->getLabel();
    }
    return $choices;
  }

  /**
   * Structure pour ChoiceType Symfony (avec instances Enum directement).
   *
   * @return array<string, self>
   */
  public static function getChoices(): array {
    $choices = [];

    foreach (self::cases() as $case) {
      $choices[$case->getLabel()] = $case;
    }

    return $choices;
  }

  /**
   * Crée une instance à partir d'une valeur technique
   *
   * @throws \InvalidArgumentException
   */
  public static function createFromValue(string $value): static {
    $instance = self::tryFrom($value);
    if ($instance === null) {
      throw new \InvalidArgumentException(sprintf('Valeur "%s" invalide. Valeurs acceptées : %s', $value, implode(', ', self::values())));
    }
    return $instance;
  }

  /**
   * recupere les enums à partir des string.
   * Elle peut aussi servir de validation.
   *
   * @param array<string> $values
   * @return static[]
   */
  public static function createFromValues(array $values): array {
    $instances = [];
    foreach ($values as $value) {
      $instances[] = self::createFromValue($value);
    }
    return $instances;
  }

  /**
   *
   * @param array<string> $values
   */
  public static function validatedEnums(array $values): void {
    self::createFromValues($values);
  }

  /**
   * Crée une instance à partir d'un libellé
   *
   * @throws \InvalidArgumentException
   */
  public static function createFromLabel(string $label): static {
    foreach (self::cases() as $case) {
      if ($case->getLabel() === $label) {
        return $case;
      }
    }

    throw new \InvalidArgumentException(sprintf('Libellé "%s" invalide. Libellés acceptés : %s', $label, implode(', ', self::labels())));
  }

  /**
   * Tente de créer une instance à partir d'une valeur
   */
  public static function tryFromValue(string $value): ?static {
    return self::tryFrom($value);
  }

  /**
   * Tente de créer une instance à partir d'un libellé
   */
  public static function tryFromLabel(string $label): ?static {
    foreach (self::cases() as $case) {
      if ($case->getLabel() === $label) {
        return $case;
      }
    }

    return null;
  }

  /**
   * Vérifie si une valeur existe
   */
  public static function isValid(string $value): bool {
    return self::tryFrom($value) !== null;
  }

  /**
   * Vérifie si un libellé existe
   */
  public static function isValidLabel(string $label): bool {
    return self::tryFromLabel($label) !== null;
  }
}