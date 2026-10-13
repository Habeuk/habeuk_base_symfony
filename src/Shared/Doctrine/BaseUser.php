<?php
namespace Habeuk\HbkSymfony\Shared\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Habeuk\HbkSymfony\Shared\Doctrine\Traits\ {
  TimestampableTrait,
  StatusTrait,
  UuidTrait
};
use Habeuk\HbkSymfony\Contract\StatusEntityInterface;

#[ORM\HasLifecycleCallbacks]
abstract class BaseUser extends AbstractBaseEntity implements StatusEntityInterface, UserInterface, PasswordAuthenticatedUserInterface {
  use TimestampableTrait;
  use StatusTrait;
  use UuidTrait;

  #[ORM\Column(length: 180)]
  #[Assert\NotBlank]
  #[Assert\Email(message: 'Email invalide')]
  public string $email = '' {
    get => $this->email;
    set(string $value) {
      $this->email = trim($value);
    }
  }

  #[ORM\Column]
  public private(set) string $password = '' {
    get => $this->password;
    set(string $value) {
      $this->password = $value;
    }
  }

  #[ORM\Column]
  private bool $isVerified = false;

  // === Informations personnelles ===
  #[ORM\Column(length: 100, nullable: true)]
  public ?string $firstName = null;

  #[ORM\Column(length: 100, nullable: true)]
  public ?string $lastName = null;

  public string $fullName {
    get => trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
  }

  public function getTitle(): string {
    return $this->fullName;
  }

  // === Informations du studio ===
  #[ORM\Column(length: 255, nullable: true)]
  public ?string $logo = null;

  #[ORM\Column(length: 20, nullable: true)]
  public ?string $phone = null;

  // === Gestion du mot de passe oublié ===
  #[ORM\Column(length: 255, nullable: true)]
  public ?string $resetToken = null;

  #[ORM\Column(type: 'datetime', nullable: true)]
  public ?\DateTimeInterface $resetTokenExpiresAt = null;

  // === Timestamps ===
  #[ORM\Column(nullable: true)]
  public ?\DateTimeImmutable $lastLoginAt = null;

  function __construct() {
    $now = new \DateTimeImmutable();
    $this->createdAt = $now;
    $this->updatedAt = $now;
  }

  public function getUserIdentifier(): string {
    if ($this->email === '') {
      throw new \LogicException('User identifier (email) should not be empty.');
    }
    return $this->email;
  }

  public function setHashedPassword(string $hashedPassword): void {
    $this->password = $hashedPassword;
  }

  public function getEmail(): string {
    return $this->email;
  }

  public function getPassword(): string {
    return $this->password;
  }

  public function eraseCredentials(): void {}

  // === Méthodes métier ===
  public function isVerified(): bool {
    return $this->isVerified;
  }

  public function setIsVerified(bool $isVerified): static {
    $this->isVerified = $isVerified;
    return $this;
  }

  public function recordLogin(): void {
    $this->lastLoginAt = new \DateTimeImmutable();
  }

  public function generateResetToken(): string {
    $token = bin2hex(random_bytes(32));
    $this->resetToken = $token;
    $this->resetTokenExpiresAt = new \DateTimeImmutable('+1 hour');
    return $token;
  }

  public function clearResetToken(): void {
    $this->resetToken = null;
    $this->resetTokenExpiresAt = null;
  }

  public function isResetTokenValid(): bool {
    if ($this->resetToken === null || $this->resetTokenExpiresAt === null) {
      return false;
    }
    return $this->resetTokenExpiresAt > new \DateTimeImmutable();
  }

  // === Sérialisation pour session ===
  public function __serialize(): array {
    $data = (array) $this;
    unset($data["\0" . self::class . "\0resetToken"]);
    unset($data["\0" . self::class . "\0resetTokenExpiresAt"]);
    $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);
    return $data;
  }
}
