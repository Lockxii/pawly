<?php

namespace App\DataFixtures;

use App\Entity\Dogs;
use App\Entity\Adoption;
use App\Entity\User;
use App\Enum\DogsStatus;
use App\Enum\AdoptionStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->user($manager, 'admin', 'admin@example.com', ['ROLE_ADMIN']);
        $this->user($manager, 'membre', 'membre@example.com', ['ROLE_EMPLOYEE']);
        $this->user($manager, 'user', 'user@example.com', ['ROLE_USER']);

        $rex = $this->dog($manager, 'Rex', 'Berger allemand', 3, 'Un chien calme qui aime les promenades.', DogsStatus::Disponible);
        $this->dog($manager, 'Luna', 'Labrador', 2, 'Une chienne joueuse et sociable.', DogsStatus::Disponible);
        $this->dog($manager, 'Oscar', 'Croisé', 7, 'Un compagnon tranquille qui recherche un foyer attentionné.', DogsStatus::Disponible);
        $nala = $this->dog($manager, 'Nala', 'Beagle', 4, 'Une chienne curieuse dont l’adoption est en cours.', DogsStatus::EnAttente);
        $this->dog($manager, 'Max', 'Border collie', 5, 'Un chien actif qui rencontre sa future famille.', DogsStatus::EnAttente);
        $bella = $this->dog($manager, 'Bella', 'Golden retriever', 6, 'Une chienne qui a trouvé sa nouvelle famille.', DogsStatus::Adopte);

        $this->adoption($manager, $rex, 'Alice', 'alice@example.com', AdoptionStatus::Pending);
        $this->adoption($manager, $nala, 'Lucas', 'lucas@example.com', AdoptionStatus::Accepted);
        $this->adoption($manager, $bella, 'Emma', 'emma@example.com', AdoptionStatus::Rejected);

        $manager->flush();
    }

    private function user(ObjectManager $manager, string $username, string $email, array $roles): void
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $manager->persist($user);
    }

    private function dog(ObjectManager $manager, string $name, string $race, int $age, string $description, DogsStatus $status): Dogs
    {
        $dog = (new Dogs())
            ->setNom($name)
            ->setEspece('Chien')
            ->setRace($race)
            ->setAge($age)
            ->setDescription($description)
            ->setStatus($status->value);
        $manager->persist($dog);

        return $dog;
    }

    private function adoption(ObjectManager $manager, Dogs $dog, string $name, string $email, AdoptionStatus $status): void
    {
        $adoption = new Adoption();
        $adoption->setDog($dog);
        $adoption->setName($name);
        $adoption->setEmail($email);
        $adoption->setMessage('Je souhaite rencontrer cet animal et lui offrir un foyer adapté.');
        $adoption->setStatus($status);
        $manager->persist($adoption);
    }
}
