<?php

namespace App\State;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Appointment;
use App\Entity\User;
use App\Event\AppointmentCreatedEvent;
use App\Repository\CarOperationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NewAppointmentProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private CarOperationRepository $carOperationRepository,
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Appointment) {
            // pour les autres entités, on délègue directement
            return $this->processor->process($data, $operation, $uriVariables, $context);
        }

        // 1) logique métier avant persistance
        if ($data->getStatus() === null) {
            $data->setStatus('Planified');

            $recall = $this->carOperationRepository->findOneBy(['name' => 'Demande de rappel']);
            if ($recall && $data->getCarOperations()->contains($recall)) {
                $data->setStatus('To planify');
                $data->setAppointmentDate(null);
            }
        }

        $user = $this->security->getUser();
        if ($data->getDriver() !== null && $data->getDriver()->getUser() !== $user) {
            throw new \LogicException('User is not owner of this driver.');
        }

        if ($data->getDriver() === null) {
            if (!$user instanceof User) {
                throw new \LogicException('User must be an instance of User.');
            }
            $data->setDriver($user->getOwnDriver());
        }

        // 2) persistance et flush via API Platform
        /** @var Appointment $savedAppointment */
        $savedAppointment = $this->processor->process($data, $operation, $uriVariables, $context);

        // 3) dispatch de l’évènement métier APRES le flush
        $this->dispatcher->dispatch(new AppointmentCreatedEvent($savedAppointment));

        return $savedAppointment;
    }
}
