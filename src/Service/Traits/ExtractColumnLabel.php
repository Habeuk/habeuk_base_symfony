<?php
namespace Habeuk\HbkSymfony\Service\Traits;

use Habeuk\HbkSymfony\Attribute\ColumnLabel;
use Habeuk\HbkSymfony\Attribute\EntityCollectionReference;
use Habeuk\HbkSymfony\Attribute\EntityReference;
use Symfony\Component\Serializer\Attribute\Groups;

trait ExtractColumnLabel {

  protected function extractFromProperty(\ReflectionProperty $property, array $groups, array &$labels): void {
    $this->extractColumnLabel($property, $groups, $labels);
    $this->addExtractEntityReference($property, $groups, $labels);
    $this->addExtractEntityCollectionReference($property, $groups, $labels);
  }

  private function addExtractEntityReference(\ReflectionProperty $property, array $groups, array &$labels): void {
    $attributes = $property->getAttributes(EntityReference::class);
    $groupsAttributes = $property->getAttributes(Groups::class);
    if ($attributes === [] || $groupsAttributes === []) {
      return;
    }
    /** @var \Symfony\Component\Serializer\Attribute\Groups $groupProperty */
    $groupProperty = $groupsAttributes[0]->newInstance();
    if (array_intersect($groups, $groupProperty->groups) === []) {
      return;
    }

    /** @var EntityReference $entityReference */
    $entityReference = $attributes[0]->newInstance();
    $fieldname = $property->getName();
    $labels[$fieldname]['entity_class'] = $entityReference->entityClass;
    $labels[$fieldname]['is_collection'] = false;
    $labels[$fieldname]['target_field'] = $entityReference->target_field;
  }

  private function addExtractEntityCollectionReference(\ReflectionProperty $property, array $groups, array &$labels): void {
    $attributes = $property->getAttributes(EntityCollectionReference::class);
    $groupsAttributes = $property->getAttributes(Groups::class);
    if ($attributes === [] || $groupsAttributes === []) {
      return;
    }
    /** @var \Symfony\Component\Serializer\Attribute\Groups $groupProperty */
    $groupProperty = $groupsAttributes[0]->newInstance();
    if (array_intersect($groups, $groupProperty->groups) === []) {
      return;
    }

    /** @var EntityCollectionReference $entityReference */
    $entityReference = $attributes[0]->newInstance();
    $fieldname = $property->getName();
    $labels[$fieldname]['entity_class'] = $entityReference->entityClass;
    $labels[$fieldname]['is_collection'] = true;
    $labels[$fieldname]['target_field'] = $entityReference->target_field;
  }

  /**
   * Extrait les infos d'une propriété
   *
   * @param \ReflectionProperty $property
   * @param array<mixed> $groups
   * @param array<mixed> $labels
   */
  private function extractColumnLabel(\ReflectionProperty $property, array $groups, array &$labels): void {
    $attributes = $property->getAttributes(ColumnLabel::class);
    $groupsAttributes = $property->getAttributes(Groups::class);

    if ($attributes === [] || $groupsAttributes === []) {
      return;
    }

    /** @var \Symfony\Component\Serializer\Attribute\Groups $groupProperty */
    $groupProperty = $groupsAttributes[0]->newInstance();
    if (array_intersect($groups, $groupProperty->groups) === []) {
      return;
    }

    /** @var ColumnLabel $columnLabel */
    $columnLabel = $attributes[0]->newInstance();
    $labels[$property->getName()] = [
      'label' => $columnLabel->label,
      'order' => $columnLabel->order,
      'description' => $columnLabel->description,
      'type' => $columnLabel->type->value,
      'sortable' => $columnLabel->sortable,
      'display' => $columnLabel->display
    ];
  }
}
