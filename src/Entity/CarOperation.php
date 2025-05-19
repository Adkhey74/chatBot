<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CarOperationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarOperationRepository::class)]
#[ApiResource]
class CarOperation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $additionnalHelp = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $additionnalComment = null;

    #[ORM\Column]
    private ?int $timeUnit = null;

    #[ORM\Column]
    private ?float $price = null;

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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getAdditionnalHelp(): ?string
    {
        return $this->additionnalHelp;
    }

    public function setAdditionnalHelp(?string $additionnalHelp): static
    {
        $this->additionnalHelp = $additionnalHelp;

        return $this;
    }

    public function getAdditionnalComment(): ?string
    {
        return $this->additionnalComment;
    }

    public function setAdditionnalComment(?string $additionnalComment): static
    {
        $this->additionnalComment = $additionnalComment;

        return $this;
    }

    public function getTimeUnit(): ?int
    {
        return $this->timeUnit;
    }

    public function setTimeUnit(int $timeUnit): static
    {
        $this->timeUnit = $timeUnit;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }
}
