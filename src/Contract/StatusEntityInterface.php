<?php
namespace Habeuk\HbkSymfony\Contract;

interface StatusEntityInterface {

  public function getStatus(): bool;

  public function setStatus(bool $status): static;

  public function isActive(): bool;

  public function activate(): static;

  public function deactivate(): static;
}