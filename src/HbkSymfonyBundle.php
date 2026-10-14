<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony;

use Habeuk\HbkSymfony\Enum\RoleInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class HbkSymfonyBundle extends AbstractBundle {

  public function configure(DefinitionConfigurator $definition): void {
    $definition->rootNode()
      ->children()
      ->scalarNode('role_class')
      ->defaultValue('App\Enum\Role')
      ->info('FQCN de la classe implémentant RoleInterface')
      ->validate()
      ->ifTrue(static fn (string $v): bool => ! is_subclass_of($v, RoleInterface::class))
      ->thenInvalid('La classe "%s" est introuvable ou n\'implémente pas ' . RoleInterface::class)
      ->end()
      ->end()
      ->end();
  }

  /**
   *
   * @param array{role_class: class-string<RoleInterface>} $config
   */
  public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void {
    $container->parameters()->set('hbk_symfony.role_class', $config['role_class']);

    // Une seule source de vérité : services.yaml
    $container->import('../config/services.yaml');
  }
}