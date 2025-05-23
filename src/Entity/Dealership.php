<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DealershipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Appointment;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: DealershipRepository::class)]
#[ApiResource]
class Dealership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['appointment:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['appointment:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['appointment:read'])]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Groups(['appointment:read'])]
    private ?string $address = null;

    #[ORM\Column]
    private ?int $zipcode = null;

    #[ORM\Column]
    private ?float $latitude = null;

    #[ORM\Column]
    private ?float $longitude = null;


    #[ORM\OneToMany(mappedBy: 'dealership', targetEntity: Appointment::class)]
    private Collection $appointments;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getZipcode(): ?int
    {
        return $this->zipcode;
    }

    public function setZipcode(int $zipcode): static
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getAppointments(): Collection
    {
        return $this->appointments;
    }
}
