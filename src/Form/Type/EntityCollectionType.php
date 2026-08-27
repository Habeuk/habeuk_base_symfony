<?php
// src/Form/Type/EntityCollectionType.php
namespace Habeuk\HbkSymfony\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

class EntityCollectionType extends CollectionType {

  public function configureOptions(OptionsResolver $resolver): void {
    parent::configureOptions($resolver);

    $resolver->setDefaults([
      'entry_type' => null, // Type du formulaire enfant
      'allow_add' => true,
      'allow_delete' => true,
      'by_reference' => false,
      'prototype' => true,
      'prototype_name' => '__name__',
      'reference' => [
        'display_fields' => [
          'id'
        ],
        'search_fields' => [
          'id'
        ],
        'separator' => ' ~ ',
        'loading' => false,
        'empty_selection_message' => 'Aucun résultat trouvé',
        'empty_filter_message' => 'Aucun résultat trouvé pour votre recherche',
        'emptyMessage' => 'Aucun resultat disponible'
      ]
    ]);
    $resolver->setAllowedTypes('reference', [
      EntityCollectionReference::class,
      'array'
    ]);
    $resolver->setNormalizer('reference', function (Options $options, $value) {
      if (is_array($value)) {
        $data = new EntityCollectionReference($value);
        return $data->toArray();
      }
      return $value;
    });
  }

  public function getBlockPrefix(): string {
    return 'entity_collection';
  }

  public function buildForm(FormBuilderInterface $builder, array $options): void {
    parent::buildForm($builder, $options);
    //
  }
}