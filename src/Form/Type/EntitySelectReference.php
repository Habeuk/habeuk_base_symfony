<?php
// src/Form/Type/EntitySelectReference.php
namespace Habeuk\HbkSymfony\Form\Type;

class EntitySelectReference {

  /**
   *
   * @var array<string,mixed>
   */
  private array $displayFields;

  /**
   *
   * @var array<string,mixed>
   */
  private array $searchFields;

  private int $minLength;

  private bool $multiple;

  private string $separator;

  private string $emptyMessage;

  private string $emptySelectionMessage;

  private string $emptyFilterMessage;

  /**
   *
   * @param array<string,mixed> $config
   */
  public function __construct(array $config = []) {
    $this->displayFields = $config['display_fields'] ?? [
      'id'
    ];
    $this->searchFields = $config['search_fields'] ?? [
      'id'
    ];
    $this->minLength = $config['min_length'] ?? 3;
    $this->multiple = $config['multiple'] ?? false;
    $this->emptyMessage = $config['empty_message'] ?? 'Aucun resultat disponible';
    $this->emptyFilterMessage = $config['emptyFilterMessage'] ?? 'Aucun résultat trouvé pour votre recherche';
    $this->emptySelectionMessage = $config['emptySelectionMessage'] ?? 'Aucun résultat trouvé';
    $this->separator = $config['separator'] ?? ' ~ ';
  }

  /**
   *
   * @return array<string,mixed>
   */
  public function getDisplayFields(): array {
    return $this->displayFields;
  }

  /**
   *
   * @return array<string,mixed>
   */
  public function getSearchFields(): array {
    return $this->searchFields;
  }

  public function getMinLength(): int {
    return $this->minLength;
  }

  public function isMultiple(): bool {
    return $this->multiple;
  }

  public function getEmptyMessage(): string {
    return $this->emptyMessage;
  }

  /**
   *
   * @return array<string,mixed>
   */
  public function toArray(): array {
    return [
      'display_fields' => $this->displayFields,
      'search_fields' => $this->searchFields,
      'min_length' => $this->minLength,
      'multiple' => $this->multiple,
      'empty_message' => $this->emptyMessage,
      'emptyFilterMessage' => $this->emptyFilterMessage,
      'emptySelectionMessage' => $this->emptySelectionMessage,
      'separator' => $this->separator
    ];
  }
}