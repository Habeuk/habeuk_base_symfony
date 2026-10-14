<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\Contract;

/**
 * Contrat pour les entités gérant automatiquement leurs timestamps
 * de création et de mise à jour.
 *
 * Les entités implémentant cette interface doivent :
 * - utiliser le trait TimestampableTrait (ou fournir une implémentation équivalente)
 * - être annotées avec #[ORM\HasLifecycleCallbacks] pour que les callbacks
 * PrePersist et PreUpdate soient déclenchés par Doctrine.
 */
interface TimestampableInterface {

  /**
   * Retourne la date de création de l'entité.
   */
  public function getCreatedAt(): \DateTimeImmutable;

  /**
   * Retourne la date de dernière mise à jour de l'entité.
   */
  public function getUpdatedAt(): \DateTimeImmutable;

  /**
   * Force la mise à jour du timestamp updatedAt à maintenant.
   *
   * @return static L'instance courante, pour chaînage fluide.
   */
  public function touch(): static;

  /**
   * Retourne l'âge de l'entité en secondes (updatedAt - createdAt).
   */
  public function getAge(): int;

  /**
   * Indique si l'entité a été modifiée depuis sa création.
   */
  public function hasBeenModified(): bool;

  /**
   * Retourne le temps écoulé depuis la dernière modification.
   */
  public function getTimeSinceLastUpdate(): \DateInterval;

  /**
   * Retourne le temps écoulé depuis la création.
   */
  public function getTimeSinceCreation(): \DateInterval;
}