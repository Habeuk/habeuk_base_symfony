<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\DTO;

/**
 * DTO pour la gestion des utilisateurs
 */
interface BaseDtoInterface {

  /**
   * Cette methode est utile au niveau de front.
   *
   * @return string
   */
  public function getTitle(): string;
}
