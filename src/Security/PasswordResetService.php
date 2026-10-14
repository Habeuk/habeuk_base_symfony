<?php
namespace Habeuk\HbkSymfony\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use App\Service\SendMails;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Webmozart\Assert\Assert;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetService {

  private string $email_from;

  private string $email_from_name;

  private readonly string $keyBase;

  /**
   *
   * @param VerifyEmailHelperInterface $verifyEmailHelper
   * @param EntityManagerInterface $entityManager
   * @param SendMails $sendMails
   * @param array<mixed> $notification
   * @param UserPasswordHasherInterface $userPasswordHasher
   */
  public function __construct(private VerifyEmailHelperInterface $verifyEmailHelper, private EntityManagerInterface $entityManager,
    private readonly SendMails $sendMails, #[Autowire(param: 'email_notification')] private readonly array $notification,
    private readonly UserPasswordHasherInterface $userPasswordHasher) {
    Assert::isArray($this->notification);
    $this->email_from = $this->notification['email_value'];
    $this->email_from_name = $this->notification['email_name'];
    $this->keyBase = 'PasswordResetService';
  }

  /**
   * Envoyer un email de réinitialisation de mot de passe
   */
  public function sendPasswordResetEmail(string $verifyEmailRouteName, User $user): void {
    /**
     * verifyEmailHelper utilise 2 champs string pour valider, on ne doit pas valider le reset du mot passe avec celui de l'authentification.
     */
    $key1 = $this->keyBase . $user->getUuid();
    $key2 = $this->keyBase . $user->getEmail();
    $signatureComponents = $this->verifyEmailHelper->generateSignature($verifyEmailRouteName, $key1, $key2, [
      'uuid' => $user->getUuid()
    ]);
    $context = [
      'signedUrl' => $signatureComponents->getSignedUrl(),
      'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
      'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
      'userName' => $user->firstName !== null || $user->lastName !== null ? $user->fullName : null
    ];
    $to = $user->getEmail();
    $toName = $user->fullName;
    $subject = 'Réinitialisation de votre mot de passe';
    $content = null;

    $this->sendMails->setEmailAdress($this->email_from);
    $this->sendMails->setEmailAdressName($this->email_from_name);
    $this->sendMails->setContexts($context);
    $this->sendMails->setTemplate('registration/confirmation_email.html.twig');
    $this->sendMails->sendCustom($to, $toName, $subject, $content);
  }

  /**
   *
   * @throws VerifyEmailExceptionInterface
   */
  public function handleEmailConfirmation(Request $request, User $user, string $newPlainPassword): void {
    $key1 = $this->keyBase . $user->getUuid();
    $key2 = $this->keyBase . $user->getEmail();
    $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, $key1, $key2);
    $user->setHashedPassword($this->userPasswordHasher->hashPassword($user, $newPlainPassword));
    // @todo si l'utilisateur s'est deja connecté. il ne faudra pas reactivé son compte.
    // cela represente une faille de securité.
    $user->setIsVerified(true);
    $user->setStatus(true);
    $this->entityManager->flush();
  }
}