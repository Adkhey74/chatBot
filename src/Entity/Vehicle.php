<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\VehicleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Driver;
use App\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
#[ApiResource(normalizationContext: ['groups' => ['vehicle:read']])]

#[ApiResource]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['vehicle:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['vehicle:read'])]
    private ?string $brand = null;

    #[ORM\Column(length: 255)]
    #[Groups(['vehicle:read'])]
    private ?string $model = null;

    #[ORM\Column(length: 255)]
    #[Groups(['vehicle:read'])]
    private ?string $registrationNumber = null;

    #[ORM\Column(length: 255)]
    #[Groups(['vehicle:read'])]
    private ?string $vin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['vehicle:read'])]
    private ?\DateTime $firstRegistrationDate = null;

    #[ORM\Column]
    #[Groups(['vehicle:read'])]
    private ?int $mileage = null;

    #[ORM\ManyToMany(targetEntity: Driver::class, mappedBy: 'vehicles')]
    #[Groups(['vehicle:read'])]
    private Collection $drivers;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'vehicles')]
    #[Groups(['vehicle:read'])]
    private Collection $users;

    public function __construct()
    {
        $this->drivers = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(string $registrationNumber): static
    {
        $this->registrationNumber = $registrationNumber;
        return $this;
    }

    public function getVin(): ?string
    {
        return $this->vin;
    }

    public function setVin(string $vin): static
    {
        $this->vin = $vin;
        return $this;
    }

    public function getFirstRegistrationDate(): ?\DateTime
    {
        return $this->firstRegistrationDate;
    }

    public function setFirstRegistrationDate(\DateTime $firstRegistrationDate): static
    {
        $this->firstRegistrationDate = $firstRegistrationDate;
        return $this;
    }

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(int $mileage): static
    {
        $this->mileage = $mileage;
        return $this;
    }

    public function getDrivers(): Collection
    {
        return $this->drivers;
    }




    public function addDriver(Driver $driver): static
    {
        if (!$this->drivers->contains($driver)) {
            $this->drivers[] = $driver;
            $driver->addVehicle($this);
        }




        return $this;
    }




    public function removeDriver(Driver $driver): static
    {
        if ($this->drivers->removeElement($driver)) {
            $driver->removeVehicle($this);
        }


        return $this;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addVehicle($this);
        }


        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeVehicle($this);
        }


        return $this;
    }
}
