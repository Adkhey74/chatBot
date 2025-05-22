<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\AppointmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Dealership;
use App\Entity\Driver;
use App\Entity\User;
use App\Entity\CarOperation;
use App\State\NewAppointmentProcessor;


#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(processor: NewAppointmentProcessor::class),
        new Put(),
        new Delete(),
    ]
)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable:true)]
    private ?\DateTime $appointmentDate = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: Driver::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Driver $driver = null;

    #[ORM\ManyToOne(targetEntity: Dealership::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dealership $dealership = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $supplementaryInfos = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, CarOperation>
     */
    #[ORM\ManyToMany(targetEntity: CarOperation::class)]
    private Collection $carOperations;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vehicle $vehicle = null;

    public function __construct()
    {
        $this->carOperations = new ArrayCollection();
    }



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppointmentDate(): ?\DateTime
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(?\DateTime $appointmentDate): static
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
    public function getDriver(): ?Driver
    {
        return $this->driver;
    }

    public function setDriver(?Driver $driver): static
    {
        $this->driver = $driver;

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

    public function getSupplementaryInfos(): ?string
    {
        return $this->supplementaryInfos;
    }

    public function setSupplementaryInfos(?string $supplementaryInfos): static
    {
        $this->supplementaryInfos = $supplementaryInfos;

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

    /**
     * @return Collection<int, CarOperation>
     */
    public function getCarOperations(): Collection
    {
        return $this->carOperations;
    }

    public function addCarOperation(CarOperation $carOperation): static
    {
        if (!$this->carOperations->contains($carOperation)) {
            $this->carOperations->add($carOperation);
        }

        return $this;
    }

    public function removeCarOperation(CarOperation $carOperation): static
    {
        $this->carOperations->removeElement($carOperation);

        return $this;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): static
    {
        $this->vehicle = $vehicle;

        return $this;
    }
    
}
