<?php
namespace Habeuk\HbkSymfony\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

/**
 *
 * @author stephane
 * @extends AbstractType<null>
 */
class EntitySelectType extends AbstractType {

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'reference' => [
        // colonne qui serront affiché
        'display_fields' => [
          'id'
        ],
        // Colonne de recherche.
        'search_fields' => [
          'id'
        ],
        'min_length' => 3,
        'attr' => [
          'data-primevui' => 'entity_select'
        ],
        'multiple' => false,
        'loading' => false,
        'separator' => ' ~ ',
        'emptySelectionMessage' => 'Aucun résultat trouvé',
        'emptyFilterMessage' => 'Aucun résultat trouvé pour votre recherche',
        'emptyMessage' => 'Aucun resultat disponible'
      ]
    ]);
    $resolver->setAllowedTypes('reference', [
      EntitySelectReference::class,
      'array'
    ]);
    $resolver->setNormalizer('reference', function (Options $options, $value) {
      if (is_array($value)) {
        $data = new EntitySelectReference($value);
        return $data->toArray();
      }
      return $value;
    });
  }

  public function getParent(): string {
    return EntityType::class;
  }

  public function getBlockPrefix(): string {
    return 'entity_select';
  }
}