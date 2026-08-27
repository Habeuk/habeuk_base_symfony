<?php
namespace Habeuk\HbkSymfony\Contract;

interface AliasableEntityInterface extends BaseEntityInterface {

  public function getUrlAlias(): ?string;

  public function setUrlAlias(?string $alias, bool $Dispacth = true): static;

  public function isAutoGenerateAlias(): bool;

  public function setAutoGenerateAlias(bool $autoGenerateAlias): static;
}