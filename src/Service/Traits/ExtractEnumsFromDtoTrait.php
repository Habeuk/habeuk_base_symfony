<?php
namespace Habeuk\HbkSymfony\Service\Traits;

use Habeuk\HbkSymfony\Utils\EntityClassHelper;

trait ExtractEnumsFromDtoTrait {

  /**
   * Extrait automatiquement tous les enums présents dans le DTO
   *
   * @param class-string $dtoClass
   * @return array<string, array<mixed>>
   */
  protected function doExtractEnumsFromDto(string $dtoClass): array {
    $enums = [];
    $reflection = new \ReflectionClass($dtoClass);
    // Parcourir les propriétés
    foreach ($reflection->getProperties() as $property) {
      $enumClass = $this->isEnumType($property->getType());
      if ($enumClass !== null) {
        $enums[$property->getName()] = $this->normalizeEnum($enumClass);
      }
    }
    // Parcourir les getters (méthodes publiques)
    foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
      // Ignorer les méthodes héritées
      if ($method->getDeclaringClass()->getName() !== $dtoClass) {
        continue;
      }
      $methodName = $method->getName();
      if (str_starts_with($methodName, 'get') || str_starts_with($methodName, 'is') || str_starts_with($methodName, 'has')) {
        $enumClass = $this->getEnumClassFromType($method->getReturnType());
        if ($enumClass !== null) {
          $enumName = EntityClassHelper::normalizeMethodName($method->getName());
          $enums[$enumName] = $this->normalizeEnum($enumClass);
        }
      }
    }
    return $enums;
  }

  /**
   *
   * @param \ReflectionType $type
   * @return class-string|NULL
   */
  private function getEnumClassFromType(?\ReflectionType $type): ?string {
    if ($type === null) {
      return null;
    }
    $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [
      $type
    ];
    foreach ($types as $t) {
      if ($t instanceof \ReflectionNamedType && ! in_array($t->getName(), [
        'null',
        'mixed'
      ], true)) {
        $name = $t->getName();
        if (enum_exists($name)) {
          return $name;
        }
      }
    }
    return null;
  }

  /**
   * Normalise un enum en tableau pour le frontend
   *
   * @param class-string $enumClass
   * @return array<string, mixed>
   */
  private function normalizeEnum(string $enumClass): array {
    $cases = [];
    foreach ($enumClass::cases() as $case) {
      if ($case instanceof \App\Enum\BaseEnumInterface) {
        $value = $case->getValue();
        $caseData = [
          'value' => $value,
          'label' => $case->getLabel()
        ];
        // Ajouter les méthodes utilitaires si elles existent
        if (method_exists($case, 'getColor')) {
          $caseData['color'] = $case->getColor();
        }
        $cases[$value] = $caseData;
      }
    }
    return $cases;
  }

  /**
   *
   * @param \ReflectionType $type
   * @return class-string|NULL
   */
  private function isEnumType(?\ReflectionType $type): ?string {
    if ($type === null) {
      return null;
    }
    // Cas nullable
    if ($type instanceof \ReflectionNamedType) {
      $name = $type->getName();
      return enum_exists($name) ? $name : null;
    }
    // Union types (ex: MyEnum|null ou int|MyEnum)
    if ($type instanceof \ReflectionUnionType) {
      foreach ($type->getTypes() as $t) {
        if ($t instanceof \ReflectionNamedType) {
          $name = $t->getName();
          if (enum_exists($name)) {
            return $name;
          }
        }
      }
    }
    return null;
  }
}
