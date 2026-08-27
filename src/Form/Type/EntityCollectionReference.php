<?php
namespace Habeuk\HbkSymfony\Form\Type;

class EntityCollectionReference {

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

  private string $emptySelectionMessage;

  private string $emptyFilterMessage;

  private string $emptyMessage;

  private int $minLength;

  private bool $multiple;

  private string $separator;

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
    $this->emptySelectionMessage = $config['empty_selection_message'] ?? 'Aucun résultat trouvé';
    $this->emptyFilterMessage = $config['empty_filter_message'] ?? 'Aucun résultat trouvé pour votre recherche';
    $this->emptyMessage = $config['emptyMessage'] ?? 'Aucun resultat disponible';
    $this->minLength = $config['min_length'] ?? 2;
    $this->multiple = $config['multiple'] ?? false;
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

  public function getEmptySelectionMessage(): string {
    return $this->emptySelectionMessage;
  }

  public function getEmptyFilterMessage(): string {
    return $this->emptyFilterMessage;
  }

  public function getEmptyMessage(): string {
    return $this->emptyMessage;
  }

  public function getMinLength(): int {
    return $this->minLength;
  }

  public function isMultiple(): bool {
    return $this->multiple;
  }

  /**
   *
   * @return array<string,mixed>
   */
  public function toArray(): array {
    return [
      'display_fields' => $this->displayFields,
      'search_fields' => $this->searchFields,
      'empty_selection_message' => $this->emptySelectionMessage,
      'empty_filter_message' => $this->emptyFilterMessage,
      'emptyMessage' => $this->emptyMessage,
      'min_length' => $this->minLength,
      'multiple' => $this->multiple,
      'loading' => false,
      'separator' => $this->separator
    ];
  }
}