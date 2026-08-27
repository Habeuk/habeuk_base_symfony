<?php
// src/Enum/PermissionEnum.php
namespace Habeuk\HbkSymfony\Enum;

use Habeuk\HbkSymfony\DTO\BaseDto;

/**
 * Permissions disponibles.
 * Validation des permissions definis au niveau de l'entité.
 */
enum PermissionEnum: string implements BaseEnumInterface {
  use BaseEnumTrait;

  # #########
  # # On utilise uniformise les groupes et les permissions.
  # # Cela permet à chaque permissions d'avoir un groupe.
  # #########

  // Voir les données
  case VIEW = BaseDto::VIEW;

  case LIST = BaseDto::LIST;

  // Créer des données
  case CREATE = BaseDto::CREATE;

  // Modifier les données.
  case EDIT = BaseDto::EDIT;

  // Acces total aux données.
  case ADMIN = BaseDto::ADMIN;

  // Supprimer les données.
  case DELETE = BaseDto::DELETE;

  // Exporter les données.
  case EXPORT = BaseDto::EXPORT;

  // Partager les données.
  case SHARE = BaseDto::SHARE;

  // Archiver les données.
  case ARCHIVE = BaseDto::ARCHIVE;

  case RESTORE = BaseDto::RESTORE;

  case CHANGE_PASSWORD = BaseDto::CHANGE_PASSWORD;

  // Restaurer les données
  public function getLabel(): string {
    return match ($this) {
      self::VIEW => 'Voir',
      self::LIST => 'Liste',
      self::CREATE => 'Créer',
      self::EDIT => 'Modifier',
      self::ADMIN => 'admin',
      self::DELETE => 'Supprimer',
      self::EXPORT => 'Exporter',
      self::SHARE => 'Partager',
      self::ARCHIVE => 'Archiver',
      self::RESTORE => 'Restaurer',
      self::CHANGE_PASSWORD => 'Changer le mot de passe'
    };
  }

  /**
   * Permissions de base (CRUD)
   *
   * @return array<int, \Habeuk\HbkSymfony\Enum\PermissionEnum::CREATE|\Habeuk\HbkSymfony\Enum\PermissionEnum::DELETE|\Habeuk\HbkSymfony\Enum\PermissionEnum::EDIT|\Habeuk\HbkSymfony\Enum\PermissionEnum::VIEW>
   */
  public static function basic(): array {
    return [
      self::VIEW,
      self::CREATE,
      self::EDIT,
      self::DELETE
    ];
  }

  /**
   * Lecture seule
   *
   * @return array<int, \Habeuk\HbkSymfony\Enum\PermissionEnum::VIEW>
   */
  public static function readOnly(): array {
    return [
      self::VIEW
    ];
  }

  /**
   * Permissions complètes
   *
   * @return array<string|int, mixed>
   */
  public static function full(): array {
    return self::cases();
  }
}