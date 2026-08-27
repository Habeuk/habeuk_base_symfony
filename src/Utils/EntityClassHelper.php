<?php
namespace Habeuk\HbkSymfony\Utils;

use Symfony\Component\Form\FormTypeInterface;

class EntityClassHelper {

  /**
   * Récupère la classe DTO depuis le FormType
   *
   * @param class-string<FormTypeInterface<null>> $formTypeClass
   * @return class-string|null
   */
  public static function getDtoClassFromFormType(string $formTypeClass): ?string {
    // Remplacer 'Form\Type' par 'DTO' et 'FormType' par 'Dto'
    $dtoClass = str_replace([
      '\\Form\\Type\\',
      'FormType'
    ], [
      '\\DTO\\',
      'Dto'
    ], $formTypeClass);
    return class_exists($dtoClass) ? $dtoClass : null;
  }

  /**
   * Récupère la classe DTO depuis le nom de l'entité
   *
   * @param class-string $entityClass
   * @return class-string|null
   */
  public static function getDtoClassFromEntity(string $entityClass): ?string {
    $dtoClass = str_replace([
      '\\Entity\\'
    ], [
      '\\DTO\\'
    ], $entityClass);
    $dtoClass = $dtoClass . 'Dto';
    return class_exists($dtoClass) ? $dtoClass : null;
  }

  /**
   * Récupère la classe Entity depuis le DTO
   *
   * @param class-string $dtoClass
   * @return class-string|null
   */
  public static function getEntityClassFromDto(string $dtoClass): ?string {
    $entityClass = str_replace([
      '\\DTO\\',
      'Dto'
    ], [
      '\\Entity\\',
      ''
    ], $dtoClass);

    return class_exists($entityClass) ? $entityClass : null;
  }

  /**
   * Récupère la classe DTO depuis le FormType
   *
   * @param class-string<FormTypeInterface<null>> $formTypeClass
   * @return class-string|null
   */
  public static function getEntityClassFromFormType(string $formTypeClass): ?string {
    // Remplacer 'Form\Type' par 'DTO' et 'FormType' par 'Dto'
    $entityClass = str_replace([
      '\\Form\\Type\\',
      'FormType'
    ], [
      '\\Entity\\',
      ''
    ], $formTypeClass);
    return class_exists($entityClass) ? $entityClass : null;
  }

  /**
   * Récupère la classe FormType depuis le DTO
   *
   * @param class-string $dtoClass
   * @return class-string<FormTypeInterface<null>>|null
   */
  public static function getFormTypeFromDto(string $dtoClass): ?string {
    $formTypeClass = str_replace([
      '\\DTO\\',
      'Dto'
    ], [
      '\\Form\\Type\\',
      'FormType'
    ], $dtoClass);

    $class = class_exists($formTypeClass) ? $formTypeClass : null;
    if ($class === null)
      return null;
    return self::assertFormType($class);
  }

  /**
   * Vérifie que c'est un formulaire valide
   *
   * @param class-string $class
   * @return class-string<FormTypeInterface<null>>
   * @throws \InvalidArgumentException
   */
  public static function assertFormType(string $class): string {
    if (! is_subclass_of($class, FormTypeInterface::class)) {
      throw new \InvalidArgumentException(sprintf('Class "%s" must implement %s', $class, FormTypeInterface::class));
    }
    return $class;
  }

  /**
   * Vérifie que c'est un DTO valide
   *
   * @param class-string $class
   * @return class-string
   * @throws \InvalidArgumentException
   */
  public static function assertDto(string $class): string {
    if (! class_exists($class)) {
      throw new \InvalidArgumentException(sprintf('Class "%s" does not exist', $class));
    }
    return $class;
  }

  /**
   * Récupère le nom court de la classe (sans namespace)
   *
   * @param class-string $class
   * @return string
   */
  public static function getShortName(string $class): string {
    $parts = explode('\\', $class);
    return end($parts);
  }

  /**
   * Récupère le nom de l'entité à partir du DTO
   *
   * @param class-string $dtoClass
   * @return string|null
   */
  public static function getEntityNameFromDto(string $dtoClass): ?string {
    $shortName = self::getShortName($dtoClass);
    return str_replace('Dto', '', $shortName);
  }

  /**
   * Normalise le nom d'une méthode en nom de propriété JSON
   *
   * isPaid() → paid
   * getReference() → reference
   * hasDiscount() → discount
   */
  public static function normalizeMethodName(string $methodName): string {
    if (str_starts_with($methodName, 'is') && strlen($methodName) > 2) {
      return lcfirst(substr($methodName, 2));
    }
    if (str_starts_with($methodName, 'has') && strlen($methodName) > 3) {
      return lcfirst(substr($methodName, 3));
    }
    if (str_starts_with($methodName, 'get') && strlen($methodName) > 3) {
      return lcfirst(substr($methodName, 3));
    }
    return $methodName;
  }
}