<?php
namespace Habeuk\HbkSymfony\Shared\Doctrine\Traits;

use Doctrine\ORM\Mapping as ORM;

trait StatusTrait {

  #[ORM\Column(type: 'boolean', options: [
    'default' => false
  ])]
  private bool $status = false;

  /**
   *
   * @return bool
   */
  public function getStatus(): bool {
    return $this->status;
  }

  /**
   *
   * @return static
   */
  public function setStatus(bool $status): static {
    $this->status = $status;
    return $this;
  }

  /**
   *
   * @return bool
   */
  public function isActive(): bool {
    return $this->status === true;
  }

  /**
   *
   * @return static
   */
  public function activate(): static {
    $this->status = true;
    return $this;
  }

  /**
   *
   * @return static
   */
  public function deactivate(): static {
    $this->status = false;
    return $this;
  }
}