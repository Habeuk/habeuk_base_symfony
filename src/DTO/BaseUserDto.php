<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;
use Habeuk\HbkSymfony\Attribute\ColumnLabel;
use Habeuk\HbkSymfony\Enum\ColumnType;

class BaseUserDto extends BaseDto {

  // ========================
  // Authentification
  // ========================
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

  #[ColumnLabel('Email', sortable: true, order: 4)]
  #[Assert\NotBlank(groups: [
    self::CREATE,
    'admin'
  ])]
  #[Assert\Email(groups: [
    self::CREATE,
    self::EDIT,
    'admin'
  ])]
  #[Groups([
    self::EDIT,
    self::VIEW,
    self::LIST,
    self::CREATE
  ])]
  public ?string $email = null;

  // ========================
  // Informations personnelles
  // ========================
  #[ColumnLabel('Prenom', sortable: true, order: 5)]
  #[Assert\Length(max: 100, groups: [
    self::CREATE,
    self::EDIT,
    'admin'
  ])]
  #[Groups([
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  public ?string $firstName = null;

  #[ColumnLabel('Nom', sortable: true, order: 2)]
  #[Assert\Length(max: 100, groups: [
    self::CREATE,
    self::EDIT,
    'admin'
  ])]
  #[Groups([
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  public ?string $lastName = null;

  #[ColumnLabel('Telephone', type: ColumnType::PHONE, order: 5)]
  #[Assert\Length(max: 20, groups: [
    self::CREATE,
    self::EDIT,
    'admin'
  ])]
  #[Assert\Regex(pattern: '/^[0-9+\-\s\(\)]+$/', message: 'Format de téléphone invalide', groups: [
    self::CREATE,
    self::EDIT,
    'admin'
  ])]
  #[Groups([
    self::VIEW,
    self::CREATE,
    self::EDIT
  ])]
  public ?string $phone = null;

  // ========================
  // Métadonnées
  // ========================
  #[ColumnLabel('Date de creation', type: ColumnType::DATETIME, order: 8, sortable: true)]
  #[Groups([
    self::REFERENCE,
    self::LIST,
    self::VIEW
  ])]
  public ?\DateTimeImmutable $createdAt = null;

  #[ColumnLabel('Mise à jour')]
  #[Groups([
    self::VIEW
  ])]
  public ?\DateTimeImmutable $updatedAt = null;

  #[ColumnLabel('Deniere connexion')]
  #[Groups([
    self::REFERENCE,
    self::VIEW
  ])]
  public ?\DateTimeImmutable $lastLoginAt = null;
}