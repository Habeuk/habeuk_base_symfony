<?php
namespace Habeuk\HbkSymfony\Domain;

use Habeuk\HbkSymfony\Contract\RecordsEventsInterface;

abstract class RecordsEvents implements RecordsEventsInterface {

  /**
   *
   * @var array<object>
   */
  private array $domainEvents = [];

  public function recordEvent(object $event): void {
    $this->domainEvents[] = $event;
  }

  /**
   *
   * {@inheritdoc}
   * @see \Habeuk\HbkSymfony\Contract\RecordsEventsInterface::pullDomainEvents()
   * @return array<object>
   */
  public function pullDomainEvents(): array {
    $events = $this->domainEvents;
    $this->domainEvents = [];
    return $events;
  }

  public function clearDomainEvents(): void {
    $this->domainEvents = [];
  }
}
