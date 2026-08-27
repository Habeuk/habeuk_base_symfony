<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\DTO;

use Symfony\Component\Serializer\Attribute\Groups;
use Habeuk\HbkSymfony\Enum\PermissionEnum;
use Habeuk\HbkSymfony\Enum\ColumnType;
use Habeuk\HbkSymfony\Attribute\ColumnLabel;

/**
 * ################# Mise en place #####################
 * 1- Ajouter "ColumnLabel" sur tous les champs du Dto (qui doivent s'afficher).
 * 2- Ajouter les contraintes sur les champs regroupes en function des permissions.
 * ########################################################
 *
 *
 * Le filterage est base sur les groupes. Le groupe permet de filtrer
 * 1- Les champs du formulaire.
 * 2- Les données qui vont etre afficher ou traiter.
 *
 * @author stephane
 * @phpstan-consistent-constructor
 *
 */
abstract class BaseDto implements BaseDtoInterface {

  # #######
  # # Definition des groupes
  # # permet de filtrer les données et formulaire.
  # ######
  /**
   * Affichage complet( en fonction du role).
   *
   * @var string
   */
  public const VIEW = 'view';

  public const EDIT = 'edit';

  public const ADMIN = 'admin';

  public const CREATE = 'create';

  /**
   * Affiche les champs generalement pour les tableaux.
   *
   * @var string
   */
  public const LIST = 'list';

  public const REFERENCE = 'reference';

  public const DELETE = 'delete';

  public const EXPORT = 'export';

  public const SHARE = 'share';

  public const ARCHIVE = 'archive';

  public const RESTORE = 'restore';

  public const CHANGE_PASSWORD = 'change_password';

  #[Groups([
    self::LIST,
    self::VIEW,
    self::ADMIN,
    self::REFERENCE
  ])]
  #[ColumnLabel('#ID', type: ColumnType::NUMBER, sortable: true, order: 1)]
  public ?int $id = null;

  public function getId(): ?int {
    return $this->id;
  }

  /**
   * Le titre est tres utile en front permet de visuellement identifier un element lors de la suppresion.
   * TODO doit etre un champs requis dans l'entité de base.
   */
  #[Groups([
    self::VIEW,
    self::EDIT,
    self::ADMIN,
    self::CREATE
  ])]
  public ?string $title = null;

  public function setTitle(?string $title): void {
    if ($title === null)
      $this->title = '';
    else $this->title = $title;
  }

  #[Groups([
    self::ADMIN,
    self::VIEW,
    self::LIST
  ])]
  #[ColumnLabel('Date création', type: ColumnType::DATETIME, order: 23, display: false, sortable: true)]
  public ?\DateTimeImmutable $createdAt = null;

  #[Groups([
    self::ADMIN,
    self::VIEW,
    self::LIST
  ])]
  #[ColumnLabel('Date modification', type: ColumnType::DATETIME, order: 24, display: false, sortable: true)]
  public ?\DateTimeImmutable $updatedAt = null;
}
