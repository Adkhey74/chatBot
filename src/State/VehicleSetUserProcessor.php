<?php

namespace App\State;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Metadata\Operation;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Vehicle;

#[AsDecorator(decorates:'api_platform.doctrine.orm.state.persist_processor')]
class VehicleSetUserProcessor implements ProcessorInterface
{

    public function __construct(
        private ProcessorInterface $processor,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        // if ($data instanceof Vehicle &&  $this->security->getUser()) {
        //     $data->addUser($this->security->getUser());
        // }
        if ($data instanceof Vehicle && $operation->getMethod() === 'POST') {
            $user = $this->security->getUser();
            if ($user instanceof UserInterface) {
                $data->addUser($user);
            }
        }
        $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
