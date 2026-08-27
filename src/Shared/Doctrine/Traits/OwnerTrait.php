<?php
namespace Habeuk\HbkSymfony\Shared\Doctrine\Traits;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait OwnerTrait {

  /**
   * Le champs est automatiquement remplie lors de la premiere sauvegarde.
   */
  #[ORM\ManyToOne(targetEntity: User::class)]
  #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: true)]
  private ?User $owner = null;

  /**
   * Contient les données exposes aux fronts sans pour autant comprometre la bd.
   */
  private ?User $ownerData = null;

  public function getOwner(): ?User {
    return $this->owner;
  }

  public function setOwner(User $owner): self {
    $this->owner = $owner;
    return $this;
  }

  public function getOwnerData(): ?User {
    $this->ownerData = $this->getOwner();
    return $this->ownerData;
  }

  /**
   * Vérifie si un utilisateur est propriétaire de l'entreprise
   */
  public function isOwner(User $user): bool {
    return $this->getOwner() !== null && $this->getOwner()->getId() === $user->getId();
  }

  public function ownerIsDefinie(): bool {
    return $this->getOwner() !== null && $this->getOwner()->getId() !== null;
  }
}