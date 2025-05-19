<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Dealership;
use App\Entity\User;
use App\Entity\CarOperation;


#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ApiResource]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $appointmentDate = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: CarOperation::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?CarOperation $carOperation = null;

    #[ORM\ManyToOne(targetEntity: Dealership::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dealership $dealership = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppointmentDate(): ?\DateTime
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(\DateTime $appointmentDate): static
    {
        $this->appointmentDate = $appointmentDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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
    public function getCarOperation(): ?CarOperation
    {
        return $this->carOperation;
    }
    
    public function setCarOperation(?CarOperation $carOperation): static
    {
        $this->carOperation = $carOperation;
    
        return $this;
    }
    public function getDealership(): ?Dealership
    {
        return $this->dealership;
    }
    
    public function setDealership(?Dealership $dealership): static
    {
        $this->dealership = $dealership;
        return $this;
    }
    
    
}
