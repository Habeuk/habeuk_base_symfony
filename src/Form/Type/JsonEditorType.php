<?php
namespace Habeuk\HbkSymfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Json;

/**
 *
 * @author stephane
 * @extends AbstractType<null>
 */
class JsonEditorType extends AbstractType {

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'label' => 'JSON',
      'required' => false,
      'attr' => [
        'rows' => 6,
        'class' => 'w-full font-mono',
        'placeholder' => 'Saisissez du JSON...'
      ],
      'definitions' => null
    ]);

    $resolver->setAllowedTypes('definitions', [
      'array',
      'null'
    ]);
  }

  public function buildView(FormView $view, FormInterface $form, array $options): void {
    $view->vars['definitions'] = $options['definitions'];

    if (isset($options['definitions']) && is_array($options['definitions'])) {
      $view->vars['attr']['data-definitions'] = json_encode($options['definitions'], JSON_THROW_ON_ERROR);
    }
  }

  public function getParent(): string {
    return TextareaType::class;
  }

  public function getBlockPrefix(): string {
    return 'json_editor';
  }
}