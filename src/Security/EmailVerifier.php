<?php
namespace Habeuk\HbkSymfony\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Habeuk\HbkSymfony\Service\SendMails;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EmailVerifier {

  private string $email_from;

  private string $email_from_name;

  private readonly string $keyBase;

  public function __construct(private VerifyEmailHelperInterface $verifyEmailHelper, private EntityManagerInterface $entityManager,
    private readonly SendMails $sendMails, ParameterBagInterface $params) {
    /**
     *
     * @var array<string> $notification_email
     */
    $notification_email = $params->get('email_notification');
    $this->email_from = $notification_email['email_value'];
    $this->email_from_name = $notification_email['email_name'];
    $this->keyBase = 'EmailVerifier';
  }

  public function sendEmailConfirmation(string $verifyEmailRouteName, User $user): void {
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
    $subject = 'Veuillez confirmer votre adresse e-mail';
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
  public function handleEmailConfirmation(Request $request, User $user): void {
    $key1 = $this->keyBase . $user->getUuid();
    $key2 = $this->keyBase . $user->getEmail();
    $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, $key1, $key2);
    $user->setIsVerified(true);
    $user->setStatus(true);
    $this->entityManager->persist($user);
    $this->entityManager->flush();
  }
}
