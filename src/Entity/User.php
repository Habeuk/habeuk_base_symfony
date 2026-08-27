<?php
namespace Habeuk\HbkSymfony\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Habeuk\HbkSymfony\Enum\Role;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Habeuk\HbkSymfony\Enum\PermissionEnum;
use Habeuk\HbkSymfony\Shared\Doctrine\AbstractBaseEntity;
use Habeuk\HbkSymfony\Shared\Doctrine\Traits\ {
  TimestampableTrait,
  StatusTrait,
  UuidTrait
};
use App\Contract\StatusEntityInterface;

class User extends AbstractBaseEntity implements StatusEntityInterface, UserInterface, PasswordAuthenticatedUserInterface {
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

  /**
   *
   * @var array<string> Les rôles stockés en base
   */
  #[ORM\Column]
  private array $roles = [];

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

  /**
   *
   * @return array<string>
   */
  public function getRoles(): array {
    return $this->roles;
  }

  /**
   *
   * @param array<string> $roles
   */
  public function setRoles(array $roles): static {
    $validRoles = array_filter($roles, function ($role) {
      return in_array($role, Role::getAvailableRoles(), true);
    });
    $this->roles = array_unique($validRoles);
    return $this;
  }

  public function addRole(string $role_string): self {
    $role = $this->retriveRoleEnum($role_string);
    if (! $this->hasRole($role)) {
      $this->roles[] = $role->value;
    }
    return $this;
  }

  public function removeRole(string $role_string): self {
    $role = $this->retriveRoleEnum($role_string);
    $this->roles = array_filter($this->roles, function ($r) use ($role) {
      return $r !== $role->value;
    });
    return $this;
  }

  private function retriveRoleEnum(string $role_string): Role {
    try {
      $role = Role::createFromValue($role_string);
      return $role;
    }
    catch (\Exception $e) {}
    try {
      $role = Role::createFromLabel($role_string);
      return $role;
    }
    catch (\Exception $e) {}
    throw new \InvalidArgumentException(sprintf('Valeur/libellé "%s" invalide. Valeurs acceptées : %s', $role_string, implode(', ', Role::values())));
  }

  public function isModerator(): bool {
    return $this->hasRole(Role::ROLE_MODERATOR) || $this->hasRole(Role::ROLE_ADMIN) || $this->hasRole(Role::ROLE_SUPER_ADMIN);
  }

  public function isAdmin(): bool {
    return $this->hasRole(Role::ROLE_ADMIN) || $this->hasRole(Role::ROLE_SUPER_ADMIN);
  }

  public function isSuperAdmin(): bool {
    return $this->hasRole(Role::ROLE_SUPER_ADMIN);
  }

  public function hasRole(Role $role): bool {
    return in_array($role->value, $this->roles, true);
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