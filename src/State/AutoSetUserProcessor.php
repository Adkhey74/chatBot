<?php

namespace App\State;

use \App\Entity\User;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Metadata\Operation;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates:'api_platform.doctrine.orm.state.persist_processor')]
class AutoSetUserProcessor implements ProcessorInterface
{

    public function __construct(
        private ProcessorInterface $processor,
        private Security $security
    ) {
    }

        public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!\is_object($data) || $operation->getMethod() !== 'POST' && (!method_exists($data, 'setUser') || !method_exists($data, 'addUser'))) {
            return $this->processor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('Expected authenticated user to be User.');
        }

        // 🔹 Cas 1 : relation ManyToOne → setUser()
        if (method_exists($data, 'setUser') && method_exists($data, 'getUser') && !$data->getUser()) {
            $data->setUser($user);
        }

        // 🔹 Cas 2 : relation ManyToMany → addUser()
        if (method_exists($data, 'addUser') && method_exists($data, 'getUsers')) {
            $data->addUser($user);
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
