<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Driver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

class UserCreation implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * @param User $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        // Create User
        if (!$data instanceof User) {
            throw new \InvalidArgumentException('Expected User object');
        }

        if (!$data->getPlainPassword()) {
            return $this->processor->process($data, $operation, $uriVariables, $context);
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            $data,
            $data->getPlainPassword()
        );
        $data->setPassword($hashedPassword);
        $data->eraseCredentials();

        $this->em->persist($data);
        $this->em->flush(); 

        // Create Driver
        $driver = new Driver();
        $driver->setLastName($data->getLastName())
            ->setFirstName($data->getFirstName())
            ->setPhoneNumber($data->getPhoneNumber())
            ->setUser($data)
        ;
        $this->em->persist($driver);
        $this->em->flush();

        // Connect new Driver to User
        $data->setOwnDriver($driver);

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}