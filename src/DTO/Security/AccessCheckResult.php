<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\DTO\Security;

final readonly class AccessCheckResult {

  public function __construct(public bool $granted, public string $message, public int $statusCode = 403) {}

  public static function granted(): self {
    return new self(true, 'Ok');
  }

  public static function denied(string $message, int $statusCode = 403): self {
    return new self(false, $message, $statusCode);
  }
}