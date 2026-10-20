<?php

namespace App\Entity;

use App\Enum\AdoptionStatus;
use App\Repository\AdoptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdoptionRepository::class)]
class Adoption
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dogs $dog = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank, Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank, Assert\Email, Assert\Length(max: 180)]
    private string $email = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank, Assert\Length(max: 5000)]
    private string $message = '';

    #[ORM\Column(enumType: AdoptionStatus::class)]
    private AdoptionStatus $status = AdoptionStatus::Pending;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDog(): ?Dogs
    {
        return $this->dog;
    }

    public function setDog(Dogs $dog): void
    {
        $this->dog = $dog;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = strtolower(trim($email));
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getStatus(): AdoptionStatus
    {
        return $this->status;
    }

    public function setStatus(AdoptionStatus $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
