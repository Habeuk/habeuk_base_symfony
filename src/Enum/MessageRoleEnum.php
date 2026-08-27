<?php
declare(strict_types = 1);
namespace App\Enum;

enum MessageRoleEnum: string implements BaseEnumInterface {
  use BaseEnumTrait;

  case USER = 'user';

  case ASSISTANT = 'assistant';

  case SYSTEM = 'system';

  case TOOL = 'tool';

  public const DEFAULT = self::USER;

  /**
   * Retourne le libellé lisible du rôle.
   */
  public function getLabel(): string {
    return match ($this) {
      self::USER => 'Utilisateur',
      self::ASSISTANT => 'Assistant IA',
      self::SYSTEM => 'Système',
      self::TOOL => 'Outil'
    };
  }

  /**
   *
   * @return array<string>
   */
  public static function values(): array {
    return array_map(fn (self $case): string => $case->value, self::cases());
  }
}