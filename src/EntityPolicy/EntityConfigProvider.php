<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\EntityPolicy;

use Habeuk\HbkSymfony\Service\CacheManager;
use Habeuk\HbkSymfony\Service\FrontendFormConfig;
use Habeuk\HbkSymfony\ViewModel\EntityConfigView;
use Habeuk\HbkSymfony\Attribute\MenuFrontendConfig;
use Habeuk\HbkSymfony\Enum\PermissionEnum;

final readonly class EntityConfigProvider {

  public function __construct(private readonly CacheManager $cacheManager) {}

  /**
   *
   * @param class-string $entityClass
   */
  public function get(string $entityClass): ?EntityConfigView {
    $cacheKey = $this->cacheManager->key('entity_config_provider', [
      'key' => $entityClass
    ]);
    return $this->cacheManager->get($cacheKey, function () use ($entityClass) {
      return $this->doGet($entityClass);
    });
  }

  /**
   *
   * @param class-string $entityClass
   */
  private function doGet(string $entityClass): ?EntityConfigView {
    $config = self::getConfig($entityClass);
    if ($config === null) {
      return null;
    }
    return new EntityConfigView(enabled: $config->enabled, label: $config->label, entity: $config->entity, icon: $config->icon, order: $config->order, display: $config->display, actions: $config->permissions, roles: $config->roles, cardinality: $config->cardinality, scope: $config->scope, requireOwnership: $config->requireOwnership, parentEntity: $config->parentEntity, auditable: $config->auditable, revisionable: $config->revisionable);
  }

  /**
   * Récupère la configuration de l'entité
   */
  public static function getConfig(object|string $entity): ?MenuFrontendConfig {
    if (is_string($entity)) {
      if (! class_exists($entity))
        throw new \Exception("La classe '$entity' n'existe pas");
    }
    $reflection = new \ReflectionClass($entity);
    $attributes = $reflection->getAttributes(MenuFrontendConfig::class);
    if ($attributes === []) {
      return null;
    }
    return $attributes[0]->newInstance();
  }
}