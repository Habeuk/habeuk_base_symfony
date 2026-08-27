<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\Attribute;

use Habeuk\HbkSymfony\Enum\ScopeEnumInterface;
use Habeuk\HbkSymfony\Enum\PermissionEnum;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class MenuFrontendConfig {

  public const UNLIMITED = - 1;

  /**
   * Configuration du menu frontend et des règles d'accès aux entités.
   *
   * Les règles d'accès sont appliquées par le service
   *
   * ============================================================
   * RÈGLES D'ACCÈS
   * ============================================================
   *
   * 1. RÔLES ($roles)
   * - Définit quels rôles peuvent accéder à l'entité
   * - Exemple: ['ROLE_ADMIN', 'ROLE_USER']
   *
   * 2. PERMISSIONS ($permissions)
   * - Actions autorisées: ( voir ScopeEnum)
   * - Par défaut, [view, create, edit, delete] sont autorisées
   *
   * 3. PROPRIÉTÉ ($requireOwnership)
   * - true: seul le propriétaire peut modifier/supprimer
   * - false: n'importe qui peut modifier/supprimer
   * - La vue (view) est toujours autorisée
   *
   * 4. CARDINALITÉ ($cardinality)
   * - Nombre maximum d'éléments qu'un utilisateur peut créer
   * - -1: illimité (constante self::UNLIMITED)
   * - 1: un seul élément
   * - 10: maximum 10 éléments
   *
   * 5. PORTÉE ($scope)
   * - PERSONAL: l'utilisateur ne voit que ses propres données
   * - TEAM: les membres de l'équipe partagent les données
   * - GLOBAL: tous les utilisateurs voient toutes les données
   * - RESTRICTED: accès basé sur des règles spécifiques (pas impleté).
   *
   * ============================================================
   * * NOTE SUR requireOwnership ET scope:
   * ============================================================
   *
   * - PERSONAL: requireOwnership est ignoré (déjà propriétaire)
   * - TEAM: requireOwnership = true → équipe voit, seul le propriétaire modifie
   * - TEAM: requireOwnership = false → toute l'équipe voit et modifie
   * - GLOBAL: requireOwnership = true → tous voient, seul le propriétaire modifie
   * - GLOBAL: requireOwnership = false → tous voient et modifient
   *
   * ============================================================
   * COMPLÉMENTARITÉ SCOPE / REQUIRE_OWNERSHIP
   * ============================================================
   *
   * - $scope: détermine qui peut VOIR les données
   * - $requireOwnership: détermine qui peut MODIFIER les données
   *
   * Exemples:
   *
   * // Personnel: je vois et je modifie mes données
   * scope: PERSONAL, requireOwnership: true
   *
   * // Équipe: l'équipe voit, seul le propriétaire modifie
   * scope: TEAM, requireOwnership: true
   *
   * // Équipe: toute l'équipe voit et modifie
   * scope: TEAM, requireOwnership: false
   *
   * // Global: tout le monde voit, personne ne modifie
   * scope: GLOBAL, permissions: [VIEW]
   *
   * // Restreint: accès contrôlé par des règles métier // ( non implemnter ).
   * scope: RESTRICTED
   *
   * ============================================================
   * EXEMPLES D'UTILISATION
   * ============================================================
   *
   * // Factures personnelles (CRUD complet)
   * #[MenuFrontendConfig(
   * enabled: true,
   * label: "Mes factures",
   * entity: "Invoice",
   * scope: ScopeEnum::PERSONAL,
   * requireOwnership: true
   * )]
   *
   * // Devis avec limite (10 maximum)
   * #[MenuFrontendConfig(
   * enabled: true,
   * label: "Devis",
   * entity: "Quote",
   * scope: ScopeEnum::PERSONAL,
   * cardinality: 10,
   * requireOwnership: true
   * )]
   *
   * // Archive publique (lecture seule)
   * #[MenuFrontendConfig(
   * enabled: true,
   * label: "Archive",
   * entity: "Archive",
   * scope: ScopeEnum::GLOBAL,
   * permissions: [PermissionEnum::VIEW],
   * requireOwnership: false
   * )]
   *
   * ============================================================
   *
   * @param bool $enabled Active/désactive l'entité dans le menu
   * @param string $label Libellé affiché dans le menu
   * @param string $entity Nom de l'entité (ex: "User", "Invoice")
   * @param string|null $icon Icône PrimeVue (ex: "pi pi-user")
   * @param int $order Ordre d'affichage dans le menu (plus petit = plus haut)
   * @param array<string> $roles Rôles Symfony autorisés (ex: ["ROLE_ADMIN"])
   * @param int $cardinality Nombre maximum d'éléments (-1 = illimité)
   * @param ScopeEnumInterface $scope Portée des données (personal, team, global, restricted)
   * @param array<PermissionEnum> $permissions Actions autorisées (view, create, edit, delete)
   * @param bool $requireOwnership true = seul le propriétaire peut modifier
   * @param string|null $parentEntity Entité parente pour les relations hiérarchiques
   * @param bool $auditable Active la traçabilité des modifications
   * @param bool $revisionable Snapshot complet de l’entité
   */
  public function __construct(public bool $enabled, public string $label, public string $entity, public ?string $icon = null, public int $order = 0,
    public array $roles = [], public bool $display = true, public int $cardinality = self::UNLIMITED, public ?ScopeEnumInterface $scope = null,
    public array $permissions = [
      PermissionEnum::VIEW,
      PermissionEnum::CREATE,
      PermissionEnum::EDIT,
      PermissionEnum::DELETE
    ], public bool $requireOwnership = false, public ?string $parentEntity = null, public bool $auditable = false, public bool $revisionable = false) {}
}