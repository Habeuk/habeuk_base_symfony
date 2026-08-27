<?php
// src/Enum/ScopeEnum.php
namespace Habeuk\HbkSymfony\Enum;

/**
 * Portée des données
 */
enum ScopeEnum: string implements BaseEnumInterface {
  use BaseEnumTrait;

  case PERSONAL = 'personal';

  // Données de l'équipe
  case GLOBAL = 'global';

  // Toutes les données (admin)
  case RESTRICTED = 'restricted';

  // Accès restreint spécifique
  public function getLabel(): string {
    return match ($this) {
      self::PERSONAL => 'Personnel',
      self::GLOBAL => 'Global',
      self::RESTRICTED => 'Restreint'
    };
  }

  public function getDescription(): string {
    return match ($this) {
      self::PERSONAL => 'L\'utilisateur ne voit que ses propres données',
      self::GLOBAL => 'Tous les utilisateurs voient toutes les données',
      self::RESTRICTED => 'Accès basé sur des règles spécifiques'
    };
  }
}