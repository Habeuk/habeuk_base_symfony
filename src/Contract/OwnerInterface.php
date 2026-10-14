<?php
namespace Habeuk\HbkSymfony\Contract;

use Habeuk\HbkSymfony\Shared\Doctrine\BaseUser;

interface OwnerInterface {

  public function getOwner(): ?BaseUser;

  public function setOwner(BaseUser $user): self;

  public function isOwner(BaseUser $user): bool;

  public function ownerIsDefinie(): bool;
}