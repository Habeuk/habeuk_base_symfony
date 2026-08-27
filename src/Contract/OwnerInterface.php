<?php
namespace Habeuk\HbkSymfony\Contract;

use App\Entity\User;

interface OwnerInterface {

  public function getOwner(): ?User;

  public function setOwner(User $user): self;

  public function isOwner(User $user): bool;

  public function ownerIsDefinie(): bool;
}