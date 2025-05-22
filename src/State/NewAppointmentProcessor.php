<?php

namespace App\State;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\CarOperationRepository;
use Symfony\Bundle\SecurityBundle\Security;

class NewAppointmentProcessor implements ProcessorInterface
{

    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private CarOperationRepository $carOperationRepository
    ) {}

        public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Appointment) {
            if ($data->getStatus() === null) {
                $data->setStatus('Planified');
                
                // if recall then other status
                $recall = $this->carOperationRepository->findOneBy(['name' => 'Demande de rappel']);
                if ($data->getCarOperations()->contains($recall)) {
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
        }
        
        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
