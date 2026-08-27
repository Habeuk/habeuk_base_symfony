<?php
namespace Habeuk\HbkSymfony\Contract;

interface PdfInterface extends OwnerInterface, BaseEntityInterface {

  public function getId(): ?int;

  public function getName(): ?string;

  public function buildPdfFooter(): string;

  public function buildPdfHeader(): string;
}