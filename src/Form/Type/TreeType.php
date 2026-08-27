<?php
// src/Form/Type/TreeType.php
namespace Habeuk\HbkSymfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 *
 * @author stephane
 * @extends AbstractType<null>
 */
class TreeType extends AbstractType {

  public function buildForm(FormBuilderInterface $builder, array $options): void {
    // Champ hidden pour stocker les valeurs sélectionnées
    $builder->add('values', HiddenType::class, [
      'mapped' => false,
      'required' => false
    ]);
  }

  public function buildView(FormView $view, FormInterface $form, array $options): void {
    parent::buildView($view, $form, $options);

    // Passer la configuration au template
    $view->vars['tree_data'] = $options['tree_data'];
    $view->vars['selection_mode'] = $options['selection_mode'];
    $view->vars['initial_values'] = $options['initial_values'];
    $view->vars['placeholder'] = $options['placeholder'];
    $view->vars['filter'] = $options['filter'];
    $view->vars['filter_by'] = $options['filter_by'];
  }

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'mapped' => false,
      'compound' => true,
      'tree_data' => [], // Structure de l'arbre
      'selection_mode' => 'checkbox', // checkbox, single, multiple
      'initial_values' => [], // Valeurs initiales sélectionnées
      'placeholder' => 'Sélectionner...',
      'filter' => false, // Activer le filtre
      'filter_by' => 'label', // Champ utilisé pour le filtre
      'attr' => [
        'data-primevui' => 'tree'
      ]
    ]);

    $resolver->setAllowedTypes('tree_data', 'array');
    $resolver->setAllowedTypes('initial_values', 'array');
    $resolver->setAllowedValues('selection_mode', [
      'checkbox',
      'single',
      'multiple'
    ]);
    $resolver->setAllowedTypes('filter', 'bool');
  }

  public function getBlockPrefix(): string {
    return 'tree';
  }
}