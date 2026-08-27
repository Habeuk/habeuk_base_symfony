<?php
// src/Enum/ColumnType.php
namespace Habeuk\HbkSymfony\Enum;

/**
 * Types de colonnes pour l'affichage dynamique dans DataTable
 *
 * Chaque type définit comment la valeur doit être formatée et affichée
 * dans le frontend (PrimeVue DataTable).
 *
 * @see src/Service/EntityDefinitionService pour la génération des définitions
 */
enum ColumnType: string implements BaseEnumInterface {
  use BaseEnumTrait;

  /**
   * TEXTE - Valeur texte simple
   *
   * Affichage: texte brut
   * Exemple: "Jean Dupont"
   * Frontend: affichage direct
   */
  case TEXT = 'string';

  /**
   * NOMBRE - Valeur numérique
   *
   * Affichage: nombre avec séparateur de milliers
   * Exemple: 1 234
   * Frontend: toLocaleString()
   */
  case NUMBER = 'number';

  /**
   * DÉCIMAL - Valeur décimale
   *
   * Affichage: nombre avec décimales
   * Exemple: 1 234,56
   * Frontend: toLocaleString(2)
   */
  case DECIMAL = 'decimal';

  /**
   * MONNAIE - Valeur monétaire
   *
   * Affichage: devise formatée
   * Exemple: 1 234,56 €
   * Frontend: Intl.NumberFormat('currency')
   */
  case CURRENCY = 'currency';

  /**
   * POURCENTAGE - Valeur en pourcentage
   *
   * Affichage: pourcentage
   * Exemple: 75%
   * Frontend: valeur * 100 + '%'
   */
  case PERCENTAGE = 'percentage';

  /**
   * DATE - Date sans heure
   *
   * Affichage: JJ/MM/AAAA
   * Exemple: 28/03/2026
   * Frontend: toLocaleDateString()
   */
  case DATE = 'date';

  /**
   * DATE_HEURE - Date avec heure
   *
   * Affichage: JJ/MM/AAAA HH:MM
   * Exemple: 28/03/2026 07:39
   * Frontend: toLocaleString()
   */
  case DATETIME = 'datetime';

  /**
   * BOOLÉEN - Valeur vrai/faux
   *
   * Affichage: icône ✅ ou ❌
   * Exemple: true → ✅ Oui
   * Frontend: icône PrimeVue + texte
   */
  case BOOLEAN = 'boolean';

  /**
   * TABLEAU - Liste de valeurs
   *
   * Affichage: liste separer par des ","
   * Exemple: ["USER", "ADMIN"] → USER, ADMIN
   * ...join(', ')
   */
  case ARRAY = 'array';

  /**
   * TABLEAU - Liste de valeurs
   *
   * Affichage: badges
   * Exemple: ["USER", "ADMIN"] → USER, ADMIN
   * Frontend: PrimeVue Tag
   */
  case ARRAY_BADGE = 'array_badge';

  /**
   * TABLEAU - Liste de valeurs
   *
   * Affichage: badges
   * Exemple: ["USER", "ADMIN"] → USER, ADMIN
   * Frontend: PrimeVue Tag
   */
  case ARRAY_ENUM = 'array_enum';

  /**
   * ÉNUMÉRATION - Valeur parmi une liste
   *
   * Affichage: badge coloré selon la valeur
   * Exemple: "pending" → badge orange "En attente"
   * Frontend: Tag avec severity
   */
  case ENUM = 'enum';

  /**
   * EMAIL - Adresse email
   *
   * Affichage: lien mailto
   * Exemple: user@example.com
   * Frontend: <a href="mailto:...">
   */
  case EMAIL = 'email';

  /**
   * URL - Lien web
   *
   * Affichage: lien cliquable
   * Exemple: https://example.com
   * Frontend: <a href="..." target="_blank">
   */
  case URL = 'url';

  /**
   * TÉLÉPHONE - Numéro de téléphone
   *
   * Affichage: lien tel
   * Exemple: +33 6 12 34 56 78
   * Frontend: <a href="tel:...">
   */
  case PHONE = 'phone';

  /**
   * IMAGE - URL d'image
   *
   * Affichage: vignette image
   * Exemple: /uploads/logo.png
   * Frontend: <img src="..." class="w-8 h-8">
   */
  case IMAGE = 'image';

  /**
   * FICHIER - Lien vers fichier
   *
   * Affichage: icône + nom du fichier
   * Exemple: facture.pdf
   * Frontend: icône PrimeVue + lien de téléchargement
   */
  case FILE = 'file';

  /**
   * RICH_TEXT - Texte enrichi (HTML)
   *
   * Affichage: HTML sécurisé
   * Exemple: "<p>Description...</p>"
   * Frontend: v-html (sanitized)
   */
  case RICH_TEXT = 'richtext';

  /**
   * CODE - Code source
   *
   * Affichage: bloc de code formaté
   * Exemple: "<?php echo 'hello'; ?>"
   * Frontend: <pre><code>
   */
  case CODE = 'code';

  /**
   * JSON - Objet JSON
   *
   * Affichage: JSON formaté
   * Exemple: {"key": "value"}
   * Frontend: <pre>{{ JSON.stringify() }}</pre>
   */
  case JSON = 'json';

  /**
   * PROGRÈS - Barre de progression
   *
   * Affichage: barre de progression
   * Exemple: 75
   * Frontend: ProgressBar
   */
  case PROGRESS = 'progress';

  /**
   * ÉTOILES - Note sur 5
   *
   * Affichage: étoiles
   * Exemple: 4
   * Frontend: Rating component
   */
  case RATING = 'rating';

  case PERMISSIONS = 'permissions';

  case ENTITY_NAME = "entity_name";

  case ENTITY_FULLNAME = "entity_fullname";

  /**
   * RETOURNE le libellé en français
   */
  public function getLabel(): string {
    return match ($this) {
      self::TEXT => 'Texte',
      self::NUMBER => 'Nombre',
      self::DECIMAL => 'Décimal',
      self::CURRENCY => 'Monnaie',
      self::PERCENTAGE => 'Pourcentage',
      self::DATE => 'Date',
      self::DATETIME => 'Date et heure',
      self::BOOLEAN => 'Booléen',
      self::ARRAY => 'Tableau',
      self::ARRAY_BADGE => 'Tableau badge',
      self::ARRAY_ENUM => 'Tableau Énumération',
      self::ENUM => 'Énumération',
      self::EMAIL => 'Email',
      self::URL => 'URL',
      self::PHONE => 'Téléphone',
      self::IMAGE => 'Image',
      self::FILE => 'Fichier',
      self::RICH_TEXT => 'Texte enrichi',
      self::CODE => 'Code',
      self::JSON => 'JSON',
      self::PROGRESS => 'Progression',
      self::RATING => 'Évaluation',
      self::PERMISSIONS => 'Permissions',
      self::ENTITY_NAME => 'Entity name',
      self::ENTITY_FULLNAME => 'Entity full name'
    };
  }

  /**
   * RETOURNE la classe CSS recommandée
   */
  public function getCssClass(): string {
    return match ($this) {
      self::NUMBER, self::DECIMAL, self::CURRENCY, self::PERCENTAGE => 'text-right',
      self::BOOLEAN => 'text-center',
      default => 'text-left'
    };
  }

  /**
   * RETOURNE si le champ est triable
   */
  public function isSortable(): bool {
    return match ($this) {
      self::IMAGE, self::FILE, self::RICH_TEXT, self::CODE, self::JSON => false,
      default => true
    };
  }

  /**
   * RETOURNE si le champ est filtrable
   */
  public function isFilterable(): bool {
    return match ($this) {
      self::IMAGE, self::FILE, self::RICH_TEXT, self::CODE, self::JSON => false,
      default => true
    };
  }
}