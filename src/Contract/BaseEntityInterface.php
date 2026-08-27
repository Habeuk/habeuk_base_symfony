<?php
namespace Habeuk\HbkSymfony\Contract;

interface BaseEntityInterface {

  public function getId(): ?int;

  public function getTitle(): ?string;

  public function setTimestampsOnCreate(): void;

  public function setTimestampsOnUpdate(): void;

  public function getCreatedAt(): \DateTimeImmutable;

  public function getUpdatedAt(): \DateTimeImmutable;

  public function touch(): static;

  public function getAge(): int;

  public function hasBeenModified(): bool;

  public function getTimeSinceLastUpdate(): \DateInterval;

  public function getTimeSinceCreation(): \DateInterval;
}