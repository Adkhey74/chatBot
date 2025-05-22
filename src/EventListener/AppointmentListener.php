<?PHP

namespace App\EventListener;

use App\Event\AppointmentCreatedEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mailer\Test\Constraint\EmailIsQueued;
use Symfony\Component\Mime\Email;




final class AppointmentListener implements EventSubscriberInterface
{
    public function __construct(private readonly MailerInterface $mailer)
    {

        $this->mailer = $mailer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AppointmentCreatedEvent::class => 'onCreate',
        ];
    }

    public function onCreateForUser(AppointmentCreatedEvent $event, Security $security): void
    {
        $user = $security->getUser();

        $email = (new Email())
            ->from('noreply@garage.local')
            ->to($user->getEmail())
            ->subject('Confirmation de votre rendez-vous')
            ->text('Salut, test envoi de mail');

        $this->mailer->send(message: $email);
    }
}