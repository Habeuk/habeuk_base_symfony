<?php
namespace Habeuk\HbkSymfony\Shared\Doctrine\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

trait UuidTrait {
  
  /**
   * --
   */
  #[ORM\Column(type: 'uuid', unique: true)]
  private ?Uuid $uuid = null;
  
  /**
   * Propriété miroir exposée au mapper DTO.
   * N'est jamais assignée : getShowUuid() lit directement $uuid.
   * Sa présence est requise pour que le mapper résolve le chemin "showUuid".
   */
  private ?Uuid $showUuid = null;
  
  /**
   * Callback exécuté avant la persistance en base
   */
  #[ORM\PrePersist]
  public function initializeUuid(): void {
    if ($this->uuid === null)
      $this->uuid = Uuid::v4();
  }
  
  public function getUuid(): ?Uuid {
    return $this->uuid;
  }
  
  public function getShowUuid(): ?Uuid {
    return $this->getUuid();
  }
  
  public function setUuid(Uuid $uuid): static {
    $this->uuid = $uuid;
    return $this;
  }
}