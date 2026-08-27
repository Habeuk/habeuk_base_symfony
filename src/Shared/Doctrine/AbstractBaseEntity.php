<?php
namespace Habeuk\HbkSymfony\Shared\Doctrine;

use Habeuk\HbkSymfony\Contract\BaseEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Habeuk\HbkSymfony\DTO\BaseDto;

/**
 * Les traits ne sont pas supporté au niveau des super classes.
 * il faut utiliser les interfaces afin de forcer la definition.
 * "#[ORM\HasLifecycleCallbacks]" fonctionnent uniquement au niveau de la classe final( ou classe de l'entité ).
 */
#[ORM\MappedSuperclass]
abstract class AbstractBaseEntity implements BaseEntityInterface {

  /**
   * Tres important, car lors de la serialisation des collections, le Serialisateur utilise directement l'entité.
   */
  #[Groups([
    BaseDto::REFERENCE,
    BaseDto::VIEW,
    BaseDto::CREATE,
    BaseDto::EDIT,
    BaseDto::ADMIN
  ])]
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  public protected(set) ?int $id = null;

  public function getId(): ?int {
    return $this->id;
  }
}