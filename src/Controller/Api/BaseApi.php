<?php
namespace Habeuk\HbkSymfony\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Habeuk\HbkSymfony\Shared\Doctrine\BaseUser;
use Psr\Log\LoggerInterface;
use Stephane888\Debug\ExceptionExtractMessage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

abstract class BaseApi extends AbstractController {
  
  /**
   * Le chemin vers l'entite Dto.
   *
   * @var class-string|null
   */
  protected ?string $entityDtoClass = null;
  
  /**
   * Le chemin vers l'entite doctrine.
   *
   * @var class-string|null
   */
  protected ?string $entityClass = null;
  
  /**
   *
   * @var class-string|null
   */
  protected ?string $formTypeClass = null;
  
  protected readonly LoggerInterface $logger;
  
  function __construct(LoggerInterface $appLogger) {
    $this->logger = $appLogger;
  }
  
  protected function error(\Throwable $e, BaseUser $user, ?string $publicMessage = null, int $code = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse {
    $this->logger->debug($e->getMessage() . "\n details : {dbg}", [
      'dbg' => $e
    ]);
    \Stephane888\Debug\debugLog::symfonyDebug($e, "BaseApi--error--", true);
    if ($e instanceof ForeignKeyConstraintViolationException) {
      if ($user->isSuperAdmin())
        $errors = [
          'error' => $e->getMessage(),
          'details' => $e->getQuery(),
          'sql' => $e->getSQLState()
        ];
      else {
        $errors = [
          'error' => "Une relation externe empeche d'executer la requette"
        ];
      }
      return $this->json($errors, Response::HTTP_CONFLICT);
    }
    if ($e instanceof ConstraintViolationException) {
      $errors = [
        'error' => $e->getMessage(),
        'details' => $e->getQuery(),
        'message' => $e->getMessage()
      ];
      return $this->json($errors, Response::HTTP_CONFLICT);
    }
    if ($e instanceof \LogicException || $e instanceof \Exception) {
      if ($user->isSuperAdmin())
        return $this->json([
          'error' => $e->getMessage(),
          'details' => ExceptionExtractMessage::errorAll($e),
          'class' => get_class($e)
        ], $code);
      return $this->json([
        'error' => $e->getMessage()
      ], $code);
    }
    if ($user->isSuperAdmin())
      return $this->json([
        'error' => $e->getMessage(),
        'details' => ExceptionExtractMessage::errorAll($e),
        'class' => get_class($e)
      ], $code);
    $message = $publicMessage ?? "Une erreur s'est produite";
    return $this->json([
      'error' => $message
    ], $code);
  }
  
  /**
   * Verifie que le code est ok.
   *
   * @param string $entityType
   * @return boolean
   */
  protected function baseValidation(string $entityType): bool {
    $entityTypeDto = $entityType . 'Dto';
    $dtoClass = 'App\\DTO\\' . ucfirst($entityTypeDto);
    $entityClass = 'App\\Entity\\' . ucfirst($entityType);
    $formTypeClass = "App\\Form\\Type\\" . $entityType . "FormType";
    if (! class_exists($dtoClass) || ! class_exists($entityClass))
      return false;
    /** @var class-string $dtoClass */
    $this->entityDtoClass = $dtoClass;
    /** @var class-string $entityClass */
    $this->entityClass = $entityClass;
    // Peut etre null si l'entité n'autorise pas l'edition.
    /** @var class-string $formTypeClass */
    $this->formTypeClass = class_exists($formTypeClass) ? $formTypeClass : null;
    
    return true;
  }
}