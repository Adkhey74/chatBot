<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DriverRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Vehicle;
use App\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DriverRepository::class)]
#[ApiResource]
class Driver
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['driver:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['driver:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['driver:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['driver:read'])]
    private ?string $phoneNumber = null;

    #[ORM\ManyToMany(targetEntity: Vehicle::class, inversedBy: 'drivers')]
    private Collection $vehicles;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'drivers')]
    #[ORM\JoinColumn(nullable: false)] // ← ou true si le driver peut ne pas avoir de user au début
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }
    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }
    
    public function addVehicle(Vehicle $vehicle): static
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles[] = $vehicle;
            $vehicle->addDriver($this); // synchronise les deux côtés
        }
    
        return $this;
    }
    
    public function removeVehicle(Vehicle $vehicle): static
    {
        if ($this->vehicles->removeElement($vehicle)) {
            $vehicle->removeDriver($this); // synchronise les deux côtés
        }
    
        return $this;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }
    
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}
