<?php
namespace Habeuk\HbkSymfony\Controller\Api\Form;

use App\Entity\User;
use Habeuk\HbkSymfony\Controller\Api\BaseApiCrud;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

#[IsGranted('ROLE_USER')]
#[Route('/api/form')]
final class FormDefinitionController extends BaseApiCrud {
  
  /**
   * Recuperer les entites qui peuvent etre gerer de maniere dynamique.
   */
  #[Route('/available', name: 'api_forms_available', methods: [
    'GET'
  ])]
  public function available(#[CurrentUser] User $user): JsonResponse {
    try {
      $forms = $this->frontendFormConfig->getAllEntitiesConfig($user);
    }
    catch (\Throwable $e) {
      return $this->error($e, $user);
    }
    return $this->json($forms);
  }
  
  /**
   * Recupere les champs pour l'affichage du tableau et aussi les configs de l'entité.
   */
  #[Route('/definition/{entityName}', name: 'api_form_definition', methods: [
    'GET'
  ])]
  public function definition(string $entityName, #[CurrentUser] User $user): JsonResponse {
    if (! $this->baseValidation($entityName)) {
      return $this->json([
        'error' => 'Entity/Form not found : ' . $entityName
      ], Response::HTTP_UNAUTHORIZED);
    }
    try {
      $reults = $this->baseEntityDefinition($user);
    }
    catch (\Throwable $e) {
      return $this->error($e, $user);
    }
    return $this->json($reults);
  }
  
  /**
   * Recupere le formulaire d'une entité en JSON.
   */
  #[Route('/fields/{entityName}/{action}', name: 'api_form_fields', methods: [
    'GET'
  ])]
  public function getFormDefinitions(string $entityName, string $action, Request $request, #[CurrentUser] User $user): JsonResponse {
    if (! $this->baseValidation($entityName)) {
      return $this->json([
        'error' => 'Entity/Form not found : ' . $entityName
      ], Response::HTTP_UNAUTHORIZED);
    }
    try {
      $datas = $this->baseFormEntityDefinition($action, $user);
    }
    catch (\Throwable $e) {
      return $this->error($e, $user);
    }
    return $this->json($datas);
  }
}