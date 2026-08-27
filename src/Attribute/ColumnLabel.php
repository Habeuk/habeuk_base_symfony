<?php
namespace Habeuk\HbkSymfony\Attribute;

use Habeuk\HbkSymfony\Enum\ColumnType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class ColumnLabel {

  public function __construct(public string $label, public ColumnType $type = ColumnType::TEXT, public bool $sortable = false,
    public ?string $description = null, public int $order = 0, public bool $display = true) {}
}