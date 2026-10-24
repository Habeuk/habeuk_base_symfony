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
    ->arrayNode('email_notification')
    ->info('Configuration de l\'expéditeur des emails')
    ->addDefaultsIfNotSet()
    ->children()
    ->scalarNode('email_value')
    ->defaultValue('no-reply@example.com')
    ->cannotBeEmpty()
    ->end()
    ->scalarNode('email_name')
    ->defaultValue('Mon Application')
    ->cannotBeEmpty()
    ->end()
    ->end()
    ->end()
    ->end();
  }

  /**
   *
   * @param array<mixed> $config
   */
  public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void {
    $container->parameters()->set('hbk_symfony.role_class', $config['role_class']);
    $container->parameters()->set('email_notification', $config['email_notification']);

    // Une seule source de vérité : services.yaml
    $container->import('../config/services.yaml');
  }
}