<?php
namespace Habeuk\HbkSymfony\Contract;

interface MeilisearchIEntityInterface {

  const KEY_INDEX = 'entities_search';

  const RESULT_KEY_OBJECTID = 'objectID';

  public function getId(): ?int;

  public function getName(): ?string;
}