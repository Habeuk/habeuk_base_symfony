<?php
declare(strict_types = 1);
namespace App\Enum;

use App\Entity\User;

/**
 * Permet de gerer les permissions pour les fonctionnalitées.
 * Validation des permissions definie par un administrateur ( en interface admin ).
 *
 * @author stephane
 *
 */
enum PermissionFeatures: string implements BaseEnumInterface {
  use BaseEnumTrait;

  /**
   * Definition des permissions globale.
   */
  //
  case MANAGE_COMPANY = 'manage_company';

  case MANAGE_INVOICES = 'manage_invoices';

  case MANAGE_CLIENTS = 'manage_clients';

  case MANAGE_QUOTES = 'manage_quotes';

  case MANAGE_PROJECTS = 'manage_projects';

  case MANAGE_USERS = 'manage_users';

  case MANAGE_ADMIN = 'manage_admin';

  /**
   * Permissions génériques (action sur ressource)
   * Voir App\Enum\PermissionEnum
   *
   * @todo il faudra voir comment inherit de App\Enum\PermissionEnum
   */
  //
  case VIEW = 'view';

  case CREATE = 'create';

  case EDIT = 'edit';

  case DELETE = 'delete';

  case EXPORT = 'export';

  /**
   *
   * @param string $entityClass
   * @param self $action
   * @return string
   */
  public static function permissionForEntity(string $entityClass, self $action): string {
    $parent = match ($entityClass) {
      User::class => self::MANAGE_USERS,
      default => throw new \LogicException(sprintf('Aucune permission configurée pour l\'entité "%s".', $entityClass))
    };
    return $parent->buildChildKey($action);
  }

  /**
   * Retourne les enfants (sous-permissions) pour une permission principale
   *
   * @return array<self>
   */
  public function getChildren(): array {
    return match ($this) {
      self::MANAGE_INVOICES => [
        self::VIEW,
        self::CREATE,
        self::EDIT,
        self::DELETE,
        self::EXPORT
      ],
      self::MANAGE_QUOTES => [
        self::VIEW,
        self::CREATE,
        self::EDIT,
        self::DELETE
      ],
      self::MANAGE_PROJECTS => [
        self::VIEW,
        self::CREATE,
        self::EDIT,
        self::DELETE
      ],
      self::MANAGE_CLIENTS => [
        self::VIEW,
        self::CREATE,
        self::EDIT,
        self::DELETE
      ],
      self::MANAGE_COMPANY => [
        self::VIEW,
        self::EDIT,
        self::CREATE,
        self::EDIT,
        self::DELETE
      ],
      self::MANAGE_USERS => [
        self::VIEW,
        self::CREATE,
        self::EDIT,
        self::DELETE
      ],
      self::MANAGE_ADMIN => [
        self::VIEW,
        self::CREATE,
        self::EDIT,
        self::DELETE
      ],
      default => []
    };
  }

  /**
   * Retourne la permission principale associée
   *
   * @return array<string, self>
   */
  public static function getBasePermissions(): array {
    $parents = [];
    foreach (self::cases() as $permission) {
      if ($permission->getChildren() === [])
        continue;
      $parents[$permission->value] = $permission;
    }
    return $parents;
  }

  /**
   * Génère la structure de l'arbre pour le composant Tree de PrimeVue.
   *
   * @return array<int, array{key: string, label: string, data: string, children?: array<int, array{key: string, label: string, data: string}>}>
   */
  public static function getTreeStructure(User $user): array {
    $tree = [];
    foreach (self::getValuesByRole($user) as $permission) {
      $childrens = $permission->getChildren();
      if ($childrens === [])
        continue;
      $childrenNodes = [];
      foreach ($childrens as $child) {
        $childrenNodes[] = [
          'key' => $permission->buildChildKey($child),
          'label' => $child->getLabel(),
          'data' => $child->value
        ];
      }
      $tree[] = [
        'key' => $permission->value,
        'label' => $permission->getLabel(),
        'data' => $permission->value,
        'children' => $childrenNodes
      ];
    }
    return $tree;
  }

  /**
   * Permet de recuperer les valeurs en function du role.
   *
   * @param User $user
   * @return array<self>
   */
  public static function getValuesByRole(User $user): array {
    if ($user->isAdmin()) {
      return self::cases();
    }
    return [
      self::MANAGE_COMPANY,
      self::MANAGE_CLIENTS,
      self::MANAGE_INVOICES,
      self::MANAGE_PROJECTS,
      self::MANAGE_QUOTES
    ];
  }

  public function getLabel(): string {
    return match ($this) {
      self::VIEW => 'Voir',
      self::CREATE => 'Créer',
      self::EDIT => 'Modifier',
      self::DELETE => 'Supprimer',
      self::MANAGE_USERS => 'Gérer les utilisateurs',
      self::EXPORT => 'Exporter',
      self::MANAGE_COMPANY => 'Gérer la société',
      self::MANAGE_CLIENTS => 'Gérer les clients',
      self::MANAGE_INVOICES => 'Gérer les factures',
      self::MANAGE_PROJECTS => 'Gérer les projets',
      self::MANAGE_QUOTES => 'Gérer les devis',
      self::MANAGE_ADMIN => 'Gérer les données sensibles'
    };
  }

  public function isParent(): bool {
    return $this->getChildren() !== [];
  }

  public function isAction(): bool {
    return $this->getChildren() === [] && ! str_starts_with($this->value, 'manage_');
  }

  public function buildChildKey(self $child): string {
    if (! $this->isParent()) {
      throw new \LogicException(sprintf('"%s" is not a parent permission.', $this->value));
    }
    if (! in_array($child, $this->getChildren(), true)) {
      throw new \LogicException(sprintf('"%s" is not allowed for "%s".', $child->value, $this->value));
    }
    return $this->value . '__' . $child->value;
  }

  public static function isValidPermissionKey(string $key): bool {
    [
      $parentValue,
      $childValue
    ] = array_pad(explode('__', $key, 2), 2, null);
    if (! is_string($parentValue) || ! is_string($childValue)) {
      return false;
    }
    $parent = self::tryFrom($parentValue);
    $child = self::tryFrom($childValue);
    if (! $parent instanceof self || ! $child instanceof self) {
      return false;
    }
    return $parent->isParent() && $child->isAction() && in_array($child, $parent->getChildren(), true);
  }
}