<?php
namespace Habeuk\HbkSymfony\Service;

use Habeuk\HbkSymfony\Attribute\ColumnLabel;
use Habeuk\HbkSymfony\Attribute\MenuFrontendConfig;
use App\Entity\User;
use Habeuk\HbkSymfony\Enum\PermissionEnum;
use Habeuk\HbkSymfony\Form\Schema\FormSchemaBuilder;
use Habeuk\HbkSymfony\ViewModel\EntityConfigView;
use Habeuk\HbkSymfony\Utils\EntityClassHelper;
use Symfony\Component\Serializer\Attribute\Groups;
use Habeuk\HbkSymfony\Service\Traits\ExtractEnumsFromDtoTrait;
use Habeuk\HbkSymfony\EntityPolicy\EntityAccessChecker;
use Habeuk\HbkSymfony\EntityPolicy\EntityConfigProvider;
use Habeuk\HbkSymfony\Service\Traits\ExtractColumnLabel;

class FrontendFormConfig {
  use ExtractEnumsFromDtoTrait;
  use ExtractColumnLabel;

  public function __construct(private readonly CacheManager $cacheManager, private readonly EntityAccessChecker $entityAccessChecker,
    private readonly FormSchemaBuilder $formSchemaBuilder, private readonly DtoMapper $dtoMapper, private readonly EntityTypeResolverService $entityTypeResolver) {}

  /**
   *
   * @param class-string $formTypeClass
   * @param User $user
   * @param array<string> $groups
   * @return array<mixed>
   */
  public function getFormFields(string $formTypeClass, User $user, array $groups): array {
    $cacheKey = $this->cacheManager->key('entity_form_fields', [
      'key' => $formTypeClass,
      'user_roles' => $user->getRoles(),
      'group' => $groups
    ]);
    // le cache doit etre uniquement à la creation.
    $fields = $this->cacheManager->get($cacheKey, fn () => $this->doGetFormFields($formTypeClass, $user, $groups));
    if ($fields !== null)
      return $fields;
    $group = $groups[0];
    if ($user->isAdmin())
      throw new \ErrorException(sprintf("Vous n'avez pas les droits suffisants pour afficher le formulaire de : %s (groupe: %s)", $formTypeClass, $group));
    else throw new \ErrorException("Vous n'avez pas les droits suffisants pour afficher le formulaire.");
  }

  /**
   *
   * @param class-string $formTypeClass
   * @param User $user
   * @param array<string> $groups
   * @return array<mixed>
   */
  public function doGetFormFields(string $formTypeClass, User $user, array $groups): ?array {
    PermissionEnum::validatedEnums($groups);
    $formTypeClass = EntityClassHelper::assertFormType($formTypeClass);
    $entityClass = EntityClassHelper::getEntityClassFromFormType($formTypeClass);
    if ($entityClass === null)
      throw new \ErrorException("L'entité $formTypeClass n'est pas valide");
    $canCreate = $this->entityAccessChecker->canCreate($entityClass, $user);
    $canEdit = $this->entityAccessChecker->canEdit($entityClass, $user);
    if (! ($canCreate->granted || $canEdit->granted)) {
      return null;
    }

    // On cree une entite par defaut ( permettant de valider la concordance du formulaire avec le Dto ).
    $entityDtoClass = EntityClassHelper::getDtoClassFromFormType($formTypeClass);
    if ($entityDtoClass === null)
      throw new \ErrorException("L'entité $entityDtoClass n'est pas valide");
    $entity = new $entityClass();
    $entityDto = $this->dtoMapper->toDto($entity, $entityDtoClass);
    $dtoClass = EntityClassHelper::getDtoClassFromFormType($formTypeClass);
    if ($dtoClass === null)
      throw new \ErrorException("L'entité $dtoClass n'est pas valide");
    $availablesFields = $this->getColumnLabels($dtoClass, $user, $groups);
    $formOptions = [
      'current_user' => $user
    ];
    $fields = $this->formSchemaBuilder->buildSchema($formTypeClass, $entityDto, $groups, $availablesFields, $formOptions);
    return $fields;
  }

  /**
   * Recupere les noms des proprietes en function du groupe.
   *
   * @param class-string $dtoClass
   * @param array<string> $groups
   * @return array<string, array<string,int|string|null>>
   */
  public function getColumnLabels(string $dtoClass, User $user, array $groups): array {
    // Le cache c'est en fonction des roles.
    $cacheKey = $this->cacheManager->key('column_labels', [
      'key' => $dtoClass,
      'user_roles' => $user->getRoles(),
      'groups' => $groups
    ]);
    return $this->cacheManager->get($cacheKey, function () use ($dtoClass, $user, $groups) {
      return $this->doGetColumnLabels($dtoClass, $user, $groups);
    });
  }

  /**
   *
   * @param class-string $dtoClass
   * @param array<string> $groups
   * @return array<mixed>
   */
  private function doGetColumnLabels(string $dtoClass, User $user, array $groups): array {
    PermissionEnum::validatedEnums($groups);
    $entityClass = EntityClassHelper::getEntityClassFromDto($dtoClass);
    $labels = [];
    if ($entityClass === null)
      return $labels;
    if (! $this->entityAccessChecker->canView($entityClass, $user)->granted)
      return $labels;
    $reflection = new \ReflectionClass($dtoClass);
    foreach ($reflection->getProperties() as $property) {
      $this->extractFromProperty($property, $groups, $labels);
    }
    foreach ($reflection->getMethods() as $method) {
      // Vérifier si c'est un getter (getXxx, isXxx, hasXxx)
      $methodName = $method->getName();
      if (str_starts_with($methodName, 'get') || str_starts_with($methodName, 'is') || str_starts_with($methodName, 'has')) {
        $this->extractColumnLabelFromMethod($method, $groups, $labels);
      }
    }
    // il est important de genener le type de champs et de le sauvegarder en cache.
    $reflectionEntity = new \ReflectionClass($entityClass);
    foreach ($labels as $propertyName => $info) {
      // il faudra retirer cette information pour l'affichage public. ( utile uniquement pour le serveur, et sert à facilement acceder à l'information ).
      $info['type_orm'] = $reflectionEntity->hasProperty($propertyName) ? $this->entityTypeResolver->findTypeField($entityClass, $propertyName) : null;
      $labels[$propertyName] = $info;
    }
    // Trier par order
    uasort($labels, fn ($a, $b) => $a['order'] <=> $b['order']);
    return $labels;
  }

  /**
   * Extrait les infos d'une méthode (getter)
   *
   * @param \ReflectionMethod $method
   * @param array<mixed> $groups
   * @param array<mixed> $labels
   */
  private function extractColumnLabelFromMethod(\ReflectionMethod $method, array $groups, array &$labels): void {
    $attributes = $method->getAttributes(ColumnLabel::class);
    $groupsAttributes = $method->getAttributes(Groups::class);

    if ($attributes === [] || $groupsAttributes === []) {
      return;
    }

    /** @var Groups $groupProperty */
    $groupProperty = $groupsAttributes[0]->newInstance();
    if (array_intersect($groups, $groupProperty->groups) === []) {
      return;
    }

    /** @var ColumnLabel $columnLabel */
    $columnLabel = $attributes[0]->newInstance();

    $propertyName = EntityClassHelper::normalizeMethodName($method->getName());
    $labels[$propertyName] = [
      'label' => $columnLabel->label,
      'order' => $columnLabel->order,
      'description' => $columnLabel->description,
      'type' => $columnLabel->type->value,
      'sortable' => $columnLabel->sortable,
      'display' => $columnLabel->display
    ];
  }

  /**
   * Extrait automatiquement tous les enums présents dans le DTO
   *
   * @param class-string $dtoClass
   * @param User $user
   * @return array<mixed>
   */
  public function extractEnumsFromDto(string $dtoClass, User $user): array {
    $cacheKey = $this->cacheManager->key('all_enum_from_dto', [
      'key' => $dtoClass,
      'user_roles' => $user->getRoles()
    ]);
    return $this->cacheManager->get($cacheKey, function () use ($dtoClass) {
      return $this->doExtractEnumsFromDto($dtoClass);
    });
  }

  /**
   * Recuperer les informations pour l'affichage de l'entite comme lien dans le B.O.
   * NB: Si un role est definie au niveau de l'entité alors tous les utilisateurs ayant
   * ce role voient le menu car ils peuvent effectuer une action.
   *
   * @param class-string $entityClass
   * @return EntityConfigView|null
   */
  public function getEntityConfig(string $entityClass, User $user): EntityConfigView|null {
    // Le cache c'est en fonction des roles.
    $cacheKey = $this->cacheManager->key('entity_config', [
      'key' => $entityClass,
      'user_roles' => $user->getRoles()
    ]);
    return $this->cacheManager->get($cacheKey, function () use ($entityClass, $user) {
      return $this->doGetEntityConfig($entityClass, $user);
    });
  }

  /**
   * Récupère la configuration de l'entité
   */
  public static function getConfig(object|string $entity): ?MenuFrontendConfig {
    return EntityConfigProvider::getConfig($entity);
  }

  /**
   *
   * @param class-string $entityClass
   */
  private function doGetEntityConfig(string $entityClass, User $user): ?EntityConfigView {
    $config = self::getConfig($entityClass);
    if ($config === null) {
      return null;
    }
    if (! $this->entityAccessChecker->canView($entityClass, $user)->granted) {
      return null;
    }
    /** @var list<PermissionEnum> $actions */
    $actions = [];
    $actions[] = PermissionEnum::VIEW;
    if ($this->entityAccessChecker->canCreate($entityClass, $user)->granted) {
      $actions[] = PermissionEnum::CREATE;
    }
    if ($this->entityAccessChecker->canEdit($entityClass, $user)->granted) {
      $actions[] = PermissionEnum::EDIT;
    }
    if ($this->entityAccessChecker->canDelete($entityClass, $user)->granted) {
      $actions[] = PermissionEnum::DELETE;
    }
    return new EntityConfigView(enabled: $config->enabled, label: $config->label, entity: $config->entity, icon: $config->icon, order: $config->order, display: $config->display, actions: $actions, roles: $config->roles, cardinality: $config->cardinality, scope: $config->scope, requireOwnership: $config->requireOwnership, parentEntity: $config->parentEntity, auditable: $config->auditable, revisionable: $config->revisionable);
  }

  /**
   * Recuperer tous les entities qui doivent s'afficher dans le BO.
   * NB: Si un role est definie au niveau de l'entité alors tous les utilisateurs ayant
   * ce role voient le menu car ils peuvent au moins affectuer une action.
   *
   * @return array<int, array<string, bool|string|int|array<string>|null>>
   */
  public function getAllEntitiesConfig(User $user, string $entityDirectory = __DIR__ . '/../Entity'): array {
    // le cache c'est en fonction des roles.
    $cacheKey = $this->cacheManager->key('all_entities_config', [
      'key' => $entityDirectory,
      'user_roles' => $user->getRoles()
    ]);
    return $this->cacheManager->get($cacheKey, function () use ($entityDirectory, $user) {
      return $this->doGetAllEntitiesConfig($entityDirectory, $user);
    });
  }

  /**
   *
   * @param string $entityDirectory
   * @return array<EntityConfigView>
   */
  private function doGetAllEntitiesConfig(string $entityDirectory, User $user): array {
    $finder = new \Symfony\Component\Finder\Finder();
    $finder->files()
      ->in($entityDirectory)
      ->name('*.php');

    $configs = [];
    foreach ($finder as $file) {
      /** @var class-string $className */
      $className = 'App\\Entity\\' . $file->getBasename('.php');
      /**
       *
       * @var EntityConfigView|null $config
       */
      $config = $this->getEntityConfig($className, $user);
      if ($config !== null) {
        if ($config->isEnabled())
          $configs[] = $config;
      }
    }
    if ($configs !== [])
      usort($configs, fn ($a, $b) => $a->getOrder() <=> $b->getOrder());
    return $configs;
  }
}