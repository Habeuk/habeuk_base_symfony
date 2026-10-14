<?php
namespace Habeuk\HbkSymfony\Shared\Doctrine\Traits;

use Habeuk\HbkSymfony\Shared\Doctrine\BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait OwnerTrait {

  /**
   * Le champs est automatiquement remplie lors de la premiere sauvegarde.
   */
  #[ORM\ManyToOne(targetEntity: BaseUser::class)]
  #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: true)]
  private ?BaseUser $owner = null;

  /**
   * Contient les données exposes aux fronts sans pour autant comprometre la bd.
   */
  private ?BaseUser $ownerData = null;

  public function getOwner(): ?BaseUser {
    return $this->owner;
  }

  public function setOwner(BaseUser $owner): self {
    $this->owner = $owner;
    return $this;
  }

  public function getOwnerData(): ?BaseUser {
    $this->ownerData = $this->getOwner();
    return $this->ownerData;
  }

  /**
   * Vérifie si un utilisateur est propriétaire de l'entreprise
   */
  public function isOwner(BaseUser $user): bool {
    return $this->getOwner() !== null && $this->getOwner()->getId() === $user->getId();
  }

  public function ownerIsDefinie(): bool {
    return $this->getOwner() !== null && $this->getOwner()->getId() !== null;
  }
}