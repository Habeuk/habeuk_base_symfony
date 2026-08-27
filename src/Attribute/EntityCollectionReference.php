<?php
// src/Attribute/EntityReference.php
namespace Habeuk\HbkSymfony\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class EntityCollectionReference {

  /**
   *
   * @param class-string $entityClass
   * @param class-string $target_field Champ de reference lors du trie ou de la recherche.
   */
  public function __construct(public string $entityClass, public string $target_field = "name") {}
}