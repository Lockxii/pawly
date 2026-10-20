<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;


#[Table(name: 'users')]
#[Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse e-mail est déjà utilisée.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Id, Column(type: 'integer'), GeneratedValue]
    private ?int $id = null;

    #[Column(type: 'string', length: 100, nullable: false)]
    #[Assert\NotBlank, Assert\Length(max: 100)]
    private string $username = '';

    #[Column(type: 'string', length: 180, unique: true)]
    #[Assert\Email]
    #[Assert\NotBlank, Assert\Length(max: 180)]
    private string $email = '';

    #[Column(type: 'string', nullable: false)]
    private string $password;

    #[Column(type: 'json')]
    #[Assert\NotBlank]
    private array $roles = ['ROLE_USER'];

    #[Column(type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = strtolower(trim($email));
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return array_unique([...$this->roles, 'ROLE_USER']);
    }

    public function setRoles(array $roles): void {
        $this->roles = $roles;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
