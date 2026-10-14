<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;
use Habeuk\HbkSymfony\Attribute\ColumnLabel;
use Habeuk\HbkSymfony\Enum\ColumnType;

class BaseUserDto extends BaseDto {

  #[ColumnLabel('Mot de passe')]
  #[Assert\NotBlank(groups: [
    self::CREATE
  ])]
  #[Assert\Length(min: 8, max: 30, minMessage: 'Le mot de passe doit contenir au moins 8 caractères', maxMessage: 'Le mot de passe doit contenir au plus 30 caractères', groups: [
    self::CREATE,
    'change_password',
    'admin'
  ])]
  #[Assert\Regex(pattern: '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).+$/', message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre', groups: [
    self::CREATE,
    'change_password',
    'admin'
  ])]
  #[Groups([
    self::CREATE,
    'change_password',
    'admin'
  ])]
  public ?string $plainPassword = null;
}