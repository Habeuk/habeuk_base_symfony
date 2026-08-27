<?php
namespace Habeuk\HbkSymfony\Form\Schema;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ {
  CheckboxType,
  TelType,
  UrlType,
  FileType
};
use Habeuk\HbkSymfony\Form\Type\ {
  EntitySelectType,
  TreeType,
  EntityCollectionType
};
use Habeuk\HbkSymfony\Form\Type\JsonEditorType;

/**
 * Mapping pour primeVue
 *
 * @author stephane
 *
 */
class FieldMapperPrimeVue {

  /**
   * Mappe un type Symfony vers un type PrimeVue
   */
  public function mapFormTypeToPrimeVue(string $symfonyType): string {
    return match ($symfonyType) {
      TextType::class => 'text',
      UrlType::class => 'text',
      EmailType::class => 'email',
      PasswordType::class => 'password',
      RepeatedType::class => 'password_repeated',
      TextareaType::class => 'textarea',
      ChoiceType::class => 'select',
      NumberType::class => 'number',
      CheckboxType::class => 'toggle_switch',
      SubmitType::class => 'submit',
      EntitySelectType::class => 'entity_select',
      TreeType::class => 'tree',
      TelType::class => 'inputmask',
      FileType::class => 'file_upload',
      EntityCollectionType::class => 'entities_select',
      JsonEditorType::class => 'form_json',
      default => $symfonyType
    };
  }

  /**
   * Génère les options PrimeVue à partir de la configuration d'un champ.
   *
   * @param array<string, mixed> $config Configuration du champ
   * @return array<mixed>
   */
  public function getPrimeVueOptions(array $config): array {
    $options = [];
    if (isset($config['choices'])) {
      foreach ($config['choices'] as $label => $value) {
        $options[] = [
          'label' => $label,
          'value' => $value
        ];
      }
    }
    return $options;
  }
}