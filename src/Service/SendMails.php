<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Stephane888\Debug\debugLog;
use Stephane888\Debug\ExceptionExtractMessage;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Twig\Environment;
use Webmozart\Assert\Assert;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SendMails {

  private string $email_from;

  private string $email_from_name;

  private string $styles = '';

  private ?string $template = null;

  /**
   *
   * @var array<mixed>
   */
  private array $contexts = [];

  private BodyRenderer $bodyRenderer;

  private LoggerInterface $logger;

  private ?RateLimiterFactory $rateLimiter = null;

  // Statistiques pour surveillance
  private int $invalidEmailsCount = 0;

  /**
   *
   * @var array<mixed>
   */
  private array $invalidEmailsList = [];

  /**
   *
   * @param MailerInterface $mailer
   * @param EntrypointLookupInterface $entrypointLookup
   * @param Environment $twig
   * @param LoggerInterface $logger
   * @param EmailValidator $emailValidator
   * @param array<mixed> $notification
   * @param string $projectDir
   * @param RateLimiterFactory|null $emailRateLimiter
   */
  public function __construct(private readonly MailerInterface $mailer, private readonly EntrypointLookupInterface $entrypointLookup,
    private readonly Environment $twig, LoggerInterface $logger, private readonly EmailValidator $emailValidator,
    #[Autowire(param: 'email_notification')] private readonly array $notification, #[Autowire(param: 'kernel.project_dir')] private readonly string $projectDir,
    ?RateLimiterFactory $emailRateLimiter = null) {
    Assert::isArray($this->notification);
    $this->email_from = $this->notification['email_value'];
    $this->email_from_name = $this->notification['email_name'];
    $this->bodyRenderer = new BodyRenderer($this->twig);
    $this->logger = $logger;
    $this->rateLimiter = $emailRateLimiter;
  }

  /**
   * ✅ Validation avancée de l'email avec DNS
   */
  public function isValidEmail(string $email): bool {
    // 1. Validation syntaxique de base
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
      $this->logger->debug('Email invalide (syntaxe)', [
        'email' => $email
      ]);
      return false;
    }
    // 2. Validation DNS (domaine existe et a un serveur MX)
    $validations = new MultipleValidationWithAnd([
      new RFCValidation(),
      new DNSCheckValidation()
    ]);
    $isValid = $this->emailValidator->isValid($email, $validations);
    return $isValid;
  }

  /**
   * ✅ Envoi avec validation DNS et protections
   */
  public function sendCustom(string $to, string $toName, string $subject, ?string $content, ?object $entity = null, ?string $header_title = null,
    ?string $header_sub_title = null): bool {
    try {
      // 🔒 1. Rate Limiting (protection anti-brute force)
      if ($this->rateLimiter !== null) {
        $limiter = $this->rateLimiter->create($to);
        if (! $limiter->consume()->isAccepted()) {
          $this->logger->warning('Rate limit exceeded pour l\'email', [
            'email' => $to
          ]);
          throw new \Exception("Trop de tentatives d'envoi vers cette adresse. Réessayez plus tard.");
        }
      }
      // 🔒 2. Validation DNS de l'email
      if (! $this->isValidEmail($to)) {
        $this->invalidEmailsCount ++;
        $this->invalidEmailsList[] = $to;

        // Journaliser pour surveillance
        $this->logger->warning('Tentative d\'envoi vers email invalide', [
          'email' => $to,
          'toName' => $toName,
          'subject' => $subject,
          'total_invalid_today' => $this->invalidEmailsCount
        ]);

        // ⚠️ Option : Envoyer quand même en BCC à un admin pour analyse
        // $this->sendToAdminForAnalysis($to, $subject);

        throw new \Exception("L'adresse email '$to' semble invalide ou inexistante. Vérifiez l'adresse.");
      }

      // 📧 3. Préparation et envoi
      $email = $this->prepareSendMail($to, $toName, $subject, $content, $entity, $header_title, $header_sub_title);
      $this->mailer->send($email);
      return true;
    }
    catch (TransportExceptionInterface $e) {
      // 🔥 5. Gestion spécifique des erreurs SMTP
      $errorMessage = $e->getMessage();

      // Détection des erreurs "User unknown"
      if (str_contains($errorMessage, 'User unknown') || str_contains($errorMessage, '550 5.1.1') || str_contains($errorMessage, 'Recipient address rejected') ||
        str_contains($errorMessage, 'mailbox unavailable')) {

        $this->invalidEmailsCount ++;
        $this->invalidEmailsList[] = $to;

        // Marquer l'email comme invalide
        $this->logger->error('Email rejeté par le serveur SMTP (inexistant)', [
          'email' => $to,
          'error' => $errorMessage,
          'total_invalid_today' => $this->invalidEmailsCount
        ]);

        // ⚠️ Alerte si trop d'invalides
        if ($this->invalidEmailsCount > 10) {
          $this->logger->critical('Taux d\'emails invalides élevé détecté !', [
            'count' => $this->invalidEmailsCount,
            'emails' => $this->invalidEmailsList,
            'action' => 'Vérifier les inscriptions automatiques'
          ]);
        }

        throw new \Exception("L'adresse email '$to' n'existe pas. Veuillez vérifier l'adresse.");
      }

      // Autres erreurs de transport
      $this->logger->error('Erreur de transport SMTP', [
        'email' => $to,
        'error' => $errorMessage,
        'exception' => ExceptionExtractMessage::errorAll($e)
      ]);

      throw new \Exception("Erreur d'envoi : " . $errorMessage);
    }
    catch (\Exception $e) {
      // 📝 6. Toute autre erreur
      $dbg = [
        'email' => $to,
        'errors' => ExceptionExtractMessage::errorAll($e),
        '$e' => $e
      ];
      debugLog::symfonyDebug($dbg, 'sendCustom---' . $to . '---', true);

      $this->logger->error('Erreur générique lors de l\'envoi', [
        'email' => $to,
        'error' => $e->getMessage()
      ]);

      return false;
    }
  }

  /**
   * Récupérer les statistiques d'invalides
   *
   * @return array<mixed>
   */
  public function getInvalidEmailsStats(): array {
    return [
      'count' => $this->invalidEmailsCount,
      'emails' => $this->invalidEmailsList,
      'timestamp' => date('Y-m-d H:i:s')
    ];
  }

  /**
   * ✅ Réinitialiser les statistiques
   */
  public function resetInvalidEmailsStats(): void {
    $this->invalidEmailsCount = 0;
    $this->invalidEmailsList = [];
  }

  // Vos méthodes existantes préparées pour la validation
  private function prepareSendMail(string $to, string $toName, string $subject, ?string $content, ?object $entityContact = null, ?string $header_title = null,
    ?string $header_sub_title = null): TemplatedEmail {
    $receipt = new Address($to, $toName);
    $sender = new Address($this->email_from, $this->email_from_name);
    $cssContent = $this->getStyle();

    $email = (new TemplatedEmail())->from($sender)
      ->to($receipt)
      ->subject($subject);

    $template = $this->template !== null ? $this->template : 'mailer/template_m1.html.twig';
    $email->htmlTemplate($template);

    $email->context([
      'css_content' => $cssContent,
      'subject' => $subject,
      'email_from_name' => $this->email_from_name,
      'content_data' => $content,
      'header_title_data' => $header_title,
      'header_sub_title_data' => $header_sub_title,
      'entityContact' => $entityContact
    ] + $this->contexts);

    return $email;
  }

  /**
   *
   * @param array<mixed> $contexts
   */
  public function setContexts(array $contexts): void {
    $this->contexts = $contexts;
  }

  public function setTemplate(string $template): void {
    $this->template = $template;
  }

  public function setEmailAdress(string $email): void {
    $this->email_from = $email;
  }

  public function setEmailAdressName(string $name): void {
    $this->email_from_name = $name;
  }

  public function simulateSendCustom(string $to, string $toName, string $subject, ?string $content, ?object $entity = null, ?string $header_title = null,
    ?string $header_sub_title = null): bool {
    // ✅ Simulation avec validation
    if (! $this->isValidEmail($to)) {
      $this->logger->debug('Simulation : email invalide', [
        'email' => $to
      ]);
      return false;
    }

    $this->prepareSendMail($to, $toName, $subject, $content, $entity, $header_title, $header_sub_title);
    return true;
  }

  public function previewMail(string $to, string $toName, string $subject, ?string $content, ?object $entity = null, ?string $header_title = null,
    ?string $header_sub_title = null): string {
    $email = $this->prepareSendMail($to, $toName, $subject, $content, $entity, $header_title, $header_sub_title);
    $this->bodyRenderer->render($email);
    $html = $email->getHtmlBody();

    if (is_string($html)) {
      return $html;
    }

    throw new \Exception("Une erreur s'est produite lors de la génération du preview");
  }

  public function getStyle(): string {
    if ($this->styles === '') {
      /** @var \Symfony\WebpackEncoreBundle\Asset\EntrypointLookup $entrypointLookup */
      $entrypointLookup = $this->entrypointLookup;
      $entrypointLookup->reset();

      $cssFiles = $entrypointLookup->getCssFiles('mailer');
      $cssContent = '';

      foreach ($cssFiles as $file) {
        $cssPath = $this->projectDir . '/public' . $file;
        if (file_exists($cssPath)) {
          $cssContent .= file_get_contents($cssPath);
        }
      }

      $this->styles = $cssContent;
    }

    return $this->styles;
  }
}