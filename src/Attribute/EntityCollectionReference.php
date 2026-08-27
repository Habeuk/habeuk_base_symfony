<?php
// src/Attribute/EntityReference.php
namespace Habeuk\HbkSymfony\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class EntityCollectionReference {

  /**
   *
   * @param class-string $entityClass
   */
  public function __construct(public string $entityClass) {}
}