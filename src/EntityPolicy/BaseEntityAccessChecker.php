<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\EntityPolicy;

use Habeuk\HbkSymfony\Contract\BaseEntityInterface;
use Habeuk\HbkSymfony\Contract\OwnerInterface;
use Habeuk\HbkSymfony\DTO\Security\AccessCheckResult;
use App\Entity\User;
use Habeuk\HbkSymfony\EntityPolicy\Contract\EntityAccessCheckerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Habeuk\HbkSymfony\Enum\PermissionEnum;
use Habeuk\HbkSymfony\Enum\BaseEnumInterface;
use Habeuk\HbkSymfony\Enum\RoleInterface;
use Habeuk\HbkSymfony\Enum\ScopeEnum;
use Habeuk\HbkSymfony\Repository\BaseRepository;
use Habeuk\HbkSymfony\ViewModel\EntityConfigView;

abstract readonly class BaseEntityAccessChecker implements EntityAccessCheckerInterface {

  public function __construct(private EntityConfigProvider $entityConfigProvider, private readonly EntityConfigurationValidator $configurationValidator,
    private readonly EntityManagerInterface $entityManager, private readonly RoleInterface $userRole) {}

  /**
   *
   * {@inheritdoc}
   * @see \Habeuk\HbkSymfony\EntityPolicy\Contract\EntityAccessCheckerInterface::check()
   */
  public function check(object|string $entity, User $user, PermissionEnum $action): AccessCheckResult {
    $entityClass = $this->resolveEntityClass($entity);
    $accessEntityDefinition = $this->checkEntityDefinition($entityClass, $user, $action);
    if (! $accessEntityDefinition->granted) {
      return $accessEntityDefinition;
    }
    if (is_object($entity)) {
      $accessEntityData = $this->checkEntityData($entity, $user, $action);
      if (! $accessEntityData->granted) {
        return $accessEntityData;
      }
    }
    return AccessCheckResult::granted();
  }

  /**
   *
   * {@inheritdoc}
   * @see \Habeuk\HbkSymfony\EntityPolicy\Contract\EntityAccessCheckerInterface::checkEntityDefinition()
   */
  public function checkEntityDefinition(string $entityClass, User $user, PermissionEnum $action): AccessCheckResult {
    $entityConfig = $this->entityConfigProvider->get($entityClass);
    if ($entityConfig === null) {
      return AccessCheckResult::denied('Cette ressource n’est pas disponible.');
    }
    // Validation de la definition de l'entité.
    $this->configurationValidator->validate($entityClass, $entityConfig);
    // 1
    $enabledAccess = $this->checkEnabled($entityConfig);
    if ($enabledAccess !== null) {
      return $enabledAccess;
    }
    // 2
    $roleAccess = $this->checkRoles($entityConfig, $user);
    if ($roleAccess !== null) {
      return $roleAccess;
    }
    // 3
    $permissionAccess = $this->checkDeclarativePermission($entityConfig, $action);
    if ($permissionAccess !== null) {
      return $permissionAccess;
    }
    return AccessCheckResult::granted();
  }

  /**
   *
   * {@inheritdoc}
   * @see \Habeuk\HbkSymfony\EntityPolicy\Contract\EntityAccessCheckerInterface::checkEntityData()
   */
  public function checkEntityData(object $entity, User $user, PermissionEnum $action): AccessCheckResult {
    $entityClass = $this->resolveEntityClass($entity);
    $entityConfig = $this->entityConfigProvider->get($entityClass);
    if ($entityConfig === null) {
      return AccessCheckResult::denied('Cette ressource n’est pas disponible.');
    }

    // 4
    $cardinalityAccess = $this->checkCardinality($entityClass, $entityConfig, $user, $action);
    if ($cardinalityAccess !== null) {
      return $cardinalityAccess;
    }
    // 5
    $scopeAccess = $this->checkScope($entity, $entityConfig, $user, $action);
    if ($scopeAccess !== null) {
      return $scopeAccess;
    }

    return AccessCheckResult::granted();
  }

  private function checkScope(object|string $entity, EntityConfigView $entityConfig, User $user, PermissionEnum $action): ?AccessCheckResult {
    if (is_string($entity))
      return null;
    if ($entity instanceof BaseEntityInterface) {
      return null;
    }
    return match ($entityConfig->getScope()) {
      ScopeEnum::PERSONAL => $this->checkPersonalScope($entity, $user),
      ScopeEnum::GLOBAL => $this->checkGlobalScope($entity, $entityConfig, $user, $action),
      ScopeEnum::RESTRICTED => AccessCheckResult::denied('L’accès restreint n’est pas encore disponible.')
    };
  }

  private function checkPersonalScope(object $entityInstance, User $user): ?AccessCheckResult {
    return $this->checkOwnership($entityInstance, $user);
  }

  /**
   * Vérifie les règles d'accès pour les entités en scope GLOBAL.
   *
   * ============================================================
   * RÈGLE MÉTIER
   * ============================================================
   *
   * Le scope GLOBAL signifie que tous les utilisateurs peuvent
   * consulter les données.
   *
   * Exemples :
   * - Catalogue public
   * - Bibliothèque de modèles
   * - Référentiels partagés
   * - Paramètres visibles par tous
   *
   * ============================================================
   * REQUIRE OWNERSHIP
   * ============================================================
   *
   * requireOwnership permet de restreindre les opérations de
   * modification et de suppression à l'auteur de la ressource.
   *
   * Cas possibles :
   *
   * GLOBAL + requireOwnership = false
   * --------------------------------
   * - VIEW : autorisé
   * - EDIT : autorisé
   * - DELETE : autorisé
   *
   * GLOBAL + requireOwnership = true
   * --------------------------------
   * - VIEW : autorisé pour tous
   * - EDIT : propriétaire uniquement
   * - DELETE : propriétaire uniquement
   *
   * ============================================================
   * POURQUOI checkOwnership() ?
   * ============================================================
   *
   * La vérification de propriété est mutualisée avec d'autres
   * scopes (TEAM notamment).
   *
   * Cette méthode délègue donc la vérification à
   * checkOwnership() afin d'éviter la duplication du code.
   *
   * ============================================================
   * NOTE
   * ============================================================
   *
   * requireOwnership n'a aucun impact sur VIEW.
   *
   * Même lorsque requireOwnership=true, tous les utilisateurs
   * peuvent consulter la ressource.
   */
  private function checkGlobalScope(object $entityInstance, EntityConfigView $entityConfig, User $user, PermissionEnum $action): ?AccessCheckResult {
    if (! $this->actionRequiresOwnership($action, $entityConfig)) {
      return null;
    }
    return $this->checkOwnership($entityInstance, $user);
  }

  /**
   * Détermine si l'action courante nécessite une vérification
   * de propriété.
   *
   * La propriété n'est vérifiée que lorsque :
   *
   * - requireOwnership=true
   * - l'action est EDIT ou DELETE
   *
   * VIEW n'est jamais concerné.
   * CREATE n'est jamais concerné.
   */
  private function actionRequiresOwnership(PermissionEnum $action, EntityConfigView $entityConfig): bool {
    return $entityConfig->requiresOwnership() && in_array($action, [
      PermissionEnum::EDIT,
      PermissionEnum::DELETE
    ], true);
  }

  private function checkOwnership(object $entityInstance, User $user): ?AccessCheckResult {
    /** @var OwnerInterface $entityInstance */
    if ($entityInstance->isOwner($user)) {
      return null;
    }
    return AccessCheckResult::denied('Vous n’êtes pas propriétaire de cette ressource.');
  }

  /**
   *
   * @param class-string $entityClass
   */
  private function checkCardinality(string $entityClass, EntityConfigView $entityConfig, User $user, PermissionEnum $action): ?AccessCheckResult {
    if ($action !== PermissionEnum::CREATE) {
      return null;
    }
    $cardinality = $entityConfig->getCardinality();
    if ($cardinality === - 1) {
      return null;
    }
    $count = $this->countUserEntities($entityClass, $user);
    if ($count < $cardinality) {
      return null;
    }
    return AccessCheckResult::denied('Vous avez atteint la limite maximale autorisée pour cette ressource.');
  }

  /**
   *
   * @param class-string $entityClass
   */
  private function countUserEntities(string $entityClass, User $user): int {
    $repository = $this->entityManager->getRepository($entityClass);
    if (! ($repository instanceof BaseRepository)) {
      throw new \ErrorException("Erreur de configuration");
    }
    return $repository->countByOwner($user);
  }

  private function checkDeclarativePermission(EntityConfigView $entityConfig, PermissionEnum $action): ?AccessCheckResult {
    if ($entityConfig->can($action)) {
      return null;
    }
    return AccessCheckResult::denied('Cette action n’est pas autorisée sur cette ressource.');
  }

  private function checkRoles(EntityConfigView $entityConfig, User $user): ?AccessCheckResult {
    $requiredRoles = $entityConfig->getRoles();
    if ($requiredRoles === []) {
      return AccessCheckResult::denied('Aucun rôle n’est autorisé pour cette ressource.');
    }
    if ($this->userHasCompatibleRole($user->getRoles(), $requiredRoles)) {
      return null;
    }
    return AccessCheckResult::denied('Votre rôle ne permet pas d’accéder à cette ressource.');
  }

  /**
   *
   * @param array<string> $userRoles
   * @param array<string> $requiredRoles
   */
  private function userHasCompatibleRole(array $userRoles, array $requiredRoles): bool {
    /** @var array<\Habeuk\HbkSymfony\Enum\RoleInterface> $entityAccessRoles */
    $entityAccessRoles = $this->userRole::createFromValues($requiredRoles);
    foreach ($userRoles as $userRole) {
      $userAllRoles = $this->userRole::createFromValue($userRole)->inherits();
      foreach ($userAllRoles as $role) {
        if (in_array($role, $entityAccessRoles, true)) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   *
   * @return class-string
   */
  private function resolveEntityClass(object|string $entity): string {
    if (is_object($entity)) {
      return $entity::class;
    }
    /** @var class-string $entity */
    return $entity;
  }

  private function checkEnabled(EntityConfigView $entityConfig): ?AccessCheckResult {
    if ($entityConfig->isEnabled()) {
      return null;
    }
    return AccessCheckResult::denied('Cette ressource est désactivée.');
  }
}