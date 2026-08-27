<?php
namespace Habeuk\HbkSymfony\Form\Schema;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ {
  CountryType,
  CollectionType
};
use Symfony\Component\Intl\Countries;
use Habeuk\HbkSymfony\Enum\PermissionEnum;
use Habeuk\HbkSymfony\Utils\EntityClassHelper;
use App\Form\Type\EntityCollectionType;
use App\Form\Type\ {
  EntitySelectType,
  TreeType
};
use Habeuk\HbkSymfony\Form\Type\JsonEditorType;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\ManyToMany;

class FormSchemaBuilder {

  public function __construct(private FormFactoryInterface $formFactory, private FieldMapperPrimeVue $fieldMapper,
    private ValidationExtractor $validationExtractor) {}

  /**
   *
   * @param class-string<FormTypeInterface<null>> $formTypeClass
   * @param array<int, string> $groupsValidation
   * @param object $entityDtoClass
   * @param array<string, mixed> $availablesFields
   * @param array<mixed> $formOptions
   * @return array<mixed>
   */
  public function buildSchema(string $formTypeClass, object $entityDtoClass, array $groupsValidation, array $availablesFields, array $formOptions = []): array {
    $form = $this->formFactory->create($formTypeClass, $entityDtoClass, $formOptions);
    $dtoClass = EntityClassHelper::getDtoClassFromFormType($formTypeClass);
    $validations = $dtoClass !== null ? $this->validationExtractor->extractConstraints($dtoClass, $groupsValidation) : [];
    return [
      'fields' => $this->buildFields($form, $validations, $availablesFields)
    ];
  }

  /**
   *
   * @param array<mixed> $validations
   * @param FormInterface<object|null> $form
   * @param array<string, mixed> $availablesFields
   * @return array<mixed>
   */
  private function buildFields(FormInterface $form, array $validations, array $availablesFields): array {
    $fields = [];
    $entityDtoClass = $form->getConfig()->getOption('data_class');
    foreach ($form->all() as $child) {
      /**
       *
       * @var \Symfony\Component\Form\FormBuilder $config
       */
      $config = $child->getConfig();
      $fieldName = $config->getName();
      if (! isset($availablesFields[$fieldName]) && $fieldName !== 'submit') {
        continue;
      }

      $type = $config->getType()->getInnerType()::class;
      $options = $config->getOptions();
      $field = [
        'name' => $fieldName,
        'type' => isset($options['attr']['data-primevui']) ? $options['attr']['data-primevui'] : $this->fieldMapper->mapFormTypeToPrimeVue($type),
        'label' => $options['label'] ?? ucfirst($config->getName()),
        'required' => $options['required'] ?? false,
        'placeholder' => $options['attr']['placeholder'] ?? null,
        'maxlength' => $options['attr']['maxlength'] ?? null,
        'description' => $options['attr']['description'] ?? null,
        'scale' => $options['scale'] ?? false,
        'rows' => $options['attr']['rows'] ?? null,
        'mask' => $options['attr']['data-mask'] ?? null,
        'slotChar' => $options['attr']['data-slotchar'] ?? null,
        'filter' => $options['attr']['data-filter'] ?? false,
        'cardinality' => 1 // on verra apres comment traiter les champs multiple.
      ];
      if (isset($validations[$fieldName])) {
        $field['validations'] = $validations[$fieldName];
      }
      switch ($type) {
        case CountryType::class:
          $locale = $options['choice_translation_locale'] ?? 'fr';
          $countries = Countries::getNames($locale);
          $countryOptions = [];
          foreach ($countries as $code => $name) {
            $countryOptions[] = [
              'label' => $name,
              'value' => $code
            ];
          }
          usort($countryOptions, fn ($a, $b) => strcmp($a['label'], $b['label']));
          $field['options'] = $countryOptions;
          break;
        case ChoiceType::class:
          $field['options'] = $this->fieldMapper->getPrimeVueOptions($options);
          break;
        case EntitySelectType::class:
          $field = $this->enhanceEntitySelectField($field, $options);
          break;
        case TreeType::class:
          $field['tree_data'] = $options['tree_data'];
          break;
        case RepeatedType::class:
          $field['type'] = 'password';
          $field['confirm'] = true;
          $field['firstLabel'] = $options['first_options']['label'] ?? 'Mot de passe';
          $field['secondLabel'] = $options['second_options']['label'] ?? 'Confirmer le mot de passe';
          $field['firstPlaceholder'] = $options['first_options']['attr']['placeholder'] ?? null;
          $field['secondPlaceholder'] = $options['second_options']['attr']['placeholder'] ?? null;
          break;
        case EntityCollectionType::class:
          $formReferenceTypeClass = $options['entry_type'] ?? null;
          if ($formReferenceTypeClass === null) {
            throw new \Exception("entry_type n'est pas definit");
          }
          $entityReferenceClass = EntityClassHelper::getEntityClassFromFormType($formReferenceTypeClass);
          if ($entityReferenceClass === null) {
            throw new \ErrorException("La classe '$formReferenceTypeClass' n'est pas valide");
          }
          $entityClass = EntityClassHelper::getEntityClassFromDto($entityDtoClass);
          if ($entityClass === null) {
            throw new \ErrorException("La classe '$formReferenceTypeClass' n'est pas valide");
          }
          $reflectionReference = new \ReflectionClass($entityReferenceClass);
          $entityName = $reflectionReference->getShortName();
          [
            $mappedBy,
            $typeORM
          ] = $this->extractMappedBy($entityClass, $fieldName);
          $field['reference'] = [
            ...$options['reference'],
            'entity' => $entityName,
            'mapped_by' => $mappedBy,
            'type_orm' => $typeORM,
            'loading' => false
          ];
          break;
        case JsonEditorType::class:
          $field['definitions'] = $options['definitions'] ?? [];
          break;
      }

      $fields[] = $field;
    }
    return $fields;
  }

  /**
   * Enrichit le champ pour EntityType
   *
   * @param array<mixed> $field
   * @param array<mixed> $options
   * @return array<mixed>
   */
  private function enhanceEntitySelectField(array $field, array $options): array {
    $entityClass = $options['class'] ?? null;

    if ($entityClass === null) {
      return $field;
    }

    $reflection = new \ReflectionClass($entityClass);
    $entityName = $reflection->getShortName();
    $field['options'] = $options['options'] ?? [];
    $field['reference'] = [
      ...$options['reference'],
      'entity' => $entityName,
      'api_endpoint' => "/api/crud/search/{$entityName}",
      'loading' => false
    ];
    return $field;
  }

  /**
   *
   * @param class-string $entityClass
   * @param string $propertyName
   * @return array<int,string>
   */
  private function extractMappedBy(string $entityClass, string $propertyName): array {
    $mappedBy = null;
    $typeORM = null;
    $reflectionClass = new \ReflectionClass($entityClass);
    $property = $reflectionClass->getProperty($propertyName);
    $attributes = $property->getAttributes(OneToMany::class);
    // La proprieté pourrait ne pas avoir OneToMany::class.
    if ($attributes !== []) {
      /** @var OneToMany $oneToMany */
      $oneToMany = $attributes[0]->newInstance();
      $mappedBy = $oneToMany->mappedBy;
      $typeORM = 'OneToMany';
    }
    else {
      $attributes = $property->getAttributes(ManyToMany::class);
      if ($attributes !== []) {
        /** @var ManyToMany $manyToMany */
        $manyToMany = $attributes[0]->newInstance();
        $mappedBy = $manyToMany->mappedBy;
        $typeORM = 'ManyToMany';
      }
    }
    // $dbg = [
    // '$entityClass' => $entityClass,
    // '$propertyName' => $propertyName,
    // '$attributes' => $attributes
    // ];
    // \Stephane888\Debug\debugLog::symfonyDebug($dbg, 'extractMappedBy', true);
    if ($mappedBy === null || $typeORM === null) {
      throw new \Exception("Mauvaise configuration de l'entité " . $entityClass);
    }
    return [
      $mappedBy,
      $typeORM
    ];
  }
}
