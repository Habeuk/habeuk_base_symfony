<?php
namespace Habeuk\HbkSymfony\Contract;

use Habeuk\HbkSymfony\Enum\RoleInterface;

interface BaseUserInterface {

  public function hasRole(RoleInterface $role): bool;

  public function retriveRoleEnum(string $role): RoleInterface;

  /**
   *
   * @return array<string>
   */
  public function getRoles(): array;

  /**
   *
   * @param array<string> $roles
   */
  public function setRoles(array $roles): self;

  public function addRole(string $role_string): self;

  public function removeRole(string $role_string): self;
}