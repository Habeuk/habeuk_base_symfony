<?php
declare(strict_types = 1);
namespace Habeuk\HbkSymfony\Form\Schema;

use Habeuk\HbkSymfony\Enum\PermissionEnum;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationExtractor {

  public function __construct(private ValidatorInterface $validator) {}

  /**
   * Extrait les règles de validation pour un DTO.
   *
   * @param string $dtoClass
   * @param array<int, string> $groups
   * @return array<string, array<string, mixed>>
   */
  public function extractConstraints(string $dtoClass, array $groups): array {
    $constraints = [];

    if (! class_exists($dtoClass)) {
      return $constraints;
    }

    PermissionEnum::validatedEnums($groups);

    /** @var ClassMetadata $metadata */
    $metadata = $this->validator->getMetadataFor($dtoClass);

    foreach ($metadata->getConstrainedProperties() as $property) {
      /** @var array<int, PropertyMetadataInterface> $propertyMetadataList */
      $propertyMetadataList = $metadata->getPropertyMetadata($property);

      if (! isset($propertyMetadataList[0])) {
        continue;
      }

      $propertyMetadata = $propertyMetadataList[0];
      $constraintsFields = $propertyMetadata->getConstraints();

      foreach ($constraintsFields as $constraint) {
        // Vérifier si la contrainte s'applique au groupe
        $constraintGroups = $constraint->groups ?? [
          'Default'
        ];

        if (array_intersect($groups, $constraintGroups) === []) {
          continue;
        }

        $ar = $this->normalizeConstraint($constraint);

        /** @var string $type */
        $type = $ar['type'];
        $constraints[$property][$type] = $ar;
      }
    }

    return $constraints;
  }

  /**
   * Normalise une contrainte en tableau.
   *
   * @param Constraint $constraint
   * @return array<string, mixed>
   */
  private function normalizeConstraint(Constraint $constraint): array {
    $normalized = [];

    switch (true) {
      case $constraint instanceof Assert\NotBlank:
        $normalized = [
          'type' => 'required',
          'message' => $constraint->message
        ];
        break;

      case $constraint instanceof Assert\Length:
        $normalized = [
          'type' => 'length',
          'min' => $constraint->min,
          'max' => $constraint->max,
          'minMessage' => $constraint->minMessage,
          'maxMessage' => $constraint->maxMessage
        ];
        break;

      case $constraint instanceof Assert\Regex:
        $normalized = [
          'type' => 'regex',
          'pattern' => $constraint->pattern,
          'message' => $constraint->message
        ];
        break;

      case $constraint instanceof Assert\Email:
        $normalized = [
          'type' => 'email',
          'message' => $constraint->message
        ];
        break;

      case $constraint instanceof Assert\Choice:
        $normalized = [
          'type' => 'choice',
          'choices' => $constraint->choices,
          'message' => $constraint->message
        ];
        break;

      case $constraint instanceof Assert\Range:
        $normalized = [
          'type' => 'range',
          'min' => $constraint->min,
          'max' => $constraint->max,
          'notInRangeMessage' => $constraint->notInRangeMessage,
          'minMessage' => $constraint->minMessage,
          'maxMessage' => $constraint->maxMessage
        ];

        if ($constraint->min === null) {
          unset($normalized['min'], $normalized['minMessage']);
        }
        if ($constraint->max === null) {
          unset($normalized['max'], $normalized['maxMessage']);
        }
        break;

      case $constraint instanceof Assert\When:
        // 1. Normalisation des contraintes conditionnelles (Expression = TRUE)
        $nestedConstraints = [];
        $constraintsList = is_array($constraint->constraints) ? $constraint->constraints : [
          $constraint->constraints
        ];

        foreach ($constraintsList as $nestedConstraint) {
          /** @var Constraint $nestedConstraint */
          $normalizedNested = $this->normalizeConstraint($nestedConstraint);
          /** @var string $type */
          $type = $normalizedNested['type'];
          $nestedConstraints[$type] = $normalizedNested;
        }

        // 2. Normalisation des contraintes alternatives (Expression = FALSE)
        $otherwiseConstraints = [];
        $otherwiseList = is_array($constraint->otherwise) ? $constraint->otherwise : [
          $constraint->otherwise
        ];

        foreach ($otherwiseList as $otherwiseConstraint) {
          /** @var Constraint $otherwiseConstraint */
          $normalizedOtherwise = $this->normalizeConstraint($otherwiseConstraint);
          /** @var string $type */
          $type = $normalizedOtherwise['type'];
          $otherwiseConstraints[$type] = $normalizedOtherwise;
        }

        // 3. Structure finale exportée vers le Schema
        $normalized = [
          'type' => 'when',
          'expression' => $constraint->expression instanceof \Closure ? 'closure' : (string) $constraint->expression,
          'constraints' => $nestedConstraints,
          'otherwise' => $otherwiseConstraints
        ];
        break;

      case $constraint instanceof Assert\Positive:
        $normalized = [
          'type' => 'positive',
          'message' => $constraint->message
        ];
        break;

      case $constraint instanceof Assert\PositiveOrZero:
        $normalized = [
          'type' => 'positive_or_zero',
          'message' => $constraint->message
        ];
        break;

      case $constraint instanceof Assert\Iban:
        $normalized = [
          'type' => 'iban',
          'message' => $constraint->message
        ];
        break;

      case $constraint instanceof Assert\Url:
        $normalized = [
          'type' => 'url',
          'message' => $constraint->message
        ];
        break;

      default:
        $className = get_class($constraint);
        throw new \ErrorException(sprintf('Le type de validation "%s" n\'est pas encore pris en compte. Ajoutez-le dans %s::normalizeConstraint()', $className, __CLASS__));
    }

    return $normalized;
  }
}