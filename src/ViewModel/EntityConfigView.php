<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\ViewModel;

use Habeuk\HbkSymfony\Enum\PermissionEnum;
use Habeuk\HbkSymfony\Enum\ScopeEnum;

final readonly class EntityConfigView {

  /**
   *
   * @param array<string> $roles
   * @param array<PermissionEnum> $actions
   */
  public function __construct(private bool $enabled, private string $label, private string $entity, private ?string $icon, private int $order,
    private bool $display, private array $actions = [], private array $roles = [], private int $cardinality = - 1,
    private ScopeEnum $scope = ScopeEnum::PERSONAL, private bool $requireOwnership = true, private ?string $parentEntity = null, private bool $auditable = false,
    private bool $revisionable = false) {}

  public function isEnabled(): bool {
    return $this->enabled;
  }

  public function getLabel(): string {
    return $this->label;
  }

  public function getEntity(): string {
    return $this->entity;
  }

  public function getIcon(): ?string {
    return $this->icon;
  }

  public function getOrder(): int {
    return $this->order;
  }

  public function getDisplay(): bool {
    return $this->display;
  }

  /**
   *
   * @return array<PermissionEnum>
   */
  public function getActions(): array {
    return $this->actions;
  }

  /**
   *
   * @return array<string>
   */
  public function getRoles(): array {
    return $this->roles;
  }

  public function getCardinality(): int {
    return $this->cardinality;
  }

  public function getScope(): ScopeEnum {
    return $this->scope;
  }

  public function requiresOwnership(): bool {
    return $this->requireOwnership;
  }

  public function getParentEntity(): ?string {
    return $this->parentEntity;
  }

  public function isAuditable(): bool {
    return $this->auditable;
  }

  public function isRevisionable(): bool {
    return $this->revisionable;
  }

  public function can(PermissionEnum $action): bool {
    return in_array($action, $this->actions, true);
  }

  public function canView(): bool {
    return $this->can(PermissionEnum::VIEW);
  }

  public function canList(): bool {
    return $this->can(PermissionEnum::LIST);
  }

  public function canCreate(): bool {
    return $this->can(PermissionEnum::CREATE);
  }

  public function canEdit(): bool {
    return $this->can(PermissionEnum::EDIT);
  }

  public function canDelete(): bool {
    return $this->can(PermissionEnum::DELETE);
  }

  public function canExport(): bool {
    return $this->can(PermissionEnum::EXPORT);
  }

  public function canShare(): bool {
    return $this->can(PermissionEnum::SHARE);
  }

  public function canArchive(): bool {
    return $this->can(PermissionEnum::ARCHIVE);
  }

  public function canRestore(): bool {
    return $this->can(PermissionEnum::RESTORE);
  }

  /**
   *
   * @return array{ enabled: bool,
   *         label: string,
   *         entity: string,
   *         icon: string|null,
   *         order: int,
   *         actions: array<string>,
   *         roles: array<string>,
   *         display: bool,
   *         cardinality: int,
   *         scope: string,
   *         requireOwnership: bool,
   *         parentEntity: string|null,
   *         auditable: bool,
   *         revisionable: bool
   *         }
   */
  public function toArray(): array {
    return [
      'enabled' => $this->enabled,
      'label' => $this->label,
      'entity' => $this->entity,
      'icon' => $this->icon,
      'order' => $this->order,
      'actions' => array_map(static fn (PermissionEnum $permission): string => $permission->value, $this->actions),
      'roles' => $this->roles,
      'display' => $this->display,
      'cardinality' => $this->cardinality,
      'scope' => $this->scope->value,
      'requireOwnership' => $this->requireOwnership,
      'parentEntity' => $this->parentEntity,
      'auditable' => $this->auditable,
      'revisionable' => $this->revisionable
    ];
  }

  /**
   *
   * @param list<PermissionEnum> $actions
   */
  public function withActions(array $actions): self {
    return new self($this->enabled, $this->label, $this->entity, $this->icon, $this->order, $this->display, $actions, $this->roles, $this->cardinality, $this->scope, $this->requireOwnership, $this->parentEntity, $this->auditable, $this->revisionable);
  }
}