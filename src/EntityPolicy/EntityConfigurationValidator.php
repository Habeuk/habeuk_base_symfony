<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\EntityPolicy;

use Habeuk\HbkSymfony\Attribute\MenuFrontendConfig;
use Habeuk\HbkSymfony\ViewModel\EntityConfigView;
use App\Contract\BaseEntityInterface;
use App\Contract\OwnerInterface;
use App\EntityPolicy\Exception\InvalidEntityConfigurationException;
use Habeuk\HbkSymfony\Enum\ScopeEnumInterface;
use ReflectionClass;

/**
 * Permet de validé la definition de MenuFrontendConfig au niveau de l'entité.
 *
 * @author stephane
 *
 */
final readonly class EntityConfigurationValidator {

  function __construct(private readonly ScopeEnumInterface $scopeEnum) {}

  /**
   *
   * @param class-string $entityClass
   */
  public function validate(string $entityClass, EntityConfigView $config): void {
    if (! class_exists($entityClass)) {
      throw new InvalidEntityConfigurationException(sprintf('La classe "%s" n’existe pas.', $entityClass));
    }
    if (! is_a($entityClass, BaseEntityInterface::class, true)) {
      throw new InvalidEntityConfigurationException("La classe doit impleter BaseEntityInterface : $entityClass");
    }
    $reflection = new ReflectionClass($entityClass);
    if (! $reflection->isInstantiable()) {
      throw new InvalidEntityConfigurationException(sprintf('La classe "%s" doit être une entité instanciable.', $entityClass));
    }
    $this->validateRoles($entityClass, $config);
    $this->validatePermissions($entityClass, $config);
    $this->validateOwnerRequirement($entityClass, $config);
    $this->validateCardinalityRequirement($entityClass, $config);
  }

  private function validateRoles(string $entityClass, EntityConfigView $config): void {
    if ($config->getRoles() === []) {
      throw new InvalidEntityConfigurationException(sprintf('L’entité "%s" ne définit aucun rôle autorisé dans MenuFrontendConfig.', $entityClass));
    }
  }

  private function validatePermissions(string $entityClass, EntityConfigView $config): void {
    if ($config->getActions() === []) {
      throw new InvalidEntityConfigurationException(sprintf('L’entité "%s" ne définit aucune permission dans MenuFrontendConfig.', $entityClass));
    }
  }

  private function validateOwnerRequirement(string $entityClass, EntityConfigView $config): void {
    $requiresOwner = $config->getScope() === $this->scopeEnum->isPersonal() || $config->requiresOwnership();
    if (! $requiresOwner) {
      return;
    }
    if (! is_a($entityClass, OwnerInterface::class, true)) {
      throw new InvalidEntityConfigurationException(sprintf('L’entité "%s" nécessite OwnerInterface car elle utilise scope PERSONAL ou requireOwnership=true.', $entityClass));
    }
  }

  private function validateCardinalityRequirement(string $entityClass, EntityConfigView $config): void {
    if ($config->getCardinality() <= 0) {
      return;
    }
    if (! is_a($entityClass, OwnerInterface::class, true)) {
      throw new InvalidEntityConfigurationException(sprintf('L’entité "%s" utilise cardinality > 0 mais n’implémente pas OwnerInterface.', $entityClass));
    }
  }
}
