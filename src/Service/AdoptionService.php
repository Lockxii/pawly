<?php

namespace App\Service;

use App\Entity\Adoption;
use App\Enum\AdoptionStatus;
use App\Enum\DogsStatus;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class AdoptionService
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    public function submit(Adoption $adoption): void
    {
        $this->manager->wrapInTransaction(function () use ($adoption): void {
            $dog = $adoption->getDog();
            $this->manager->refresh($dog, LockMode::PESSIMISTIC_WRITE);
            if ($dog->getStatus() !== DogsStatus::Disponible->value) {
                throw new \LogicException('Cet animal n’est plus disponible à l’adoption.');
            }
            $this->manager->persist($adoption);
        });
    }

    public function decide(Adoption $adoption, AdoptionStatus $status): void
    {
        $this->manager->wrapInTransaction(function () use ($adoption, $status): void {
            $dog = $adoption->getDog();
            $this->manager->refresh($dog, LockMode::PESSIMISTIC_WRITE);
            $this->manager->refresh($adoption);
            if ($adoption->getStatus() !== AdoptionStatus::Pending || $status === AdoptionStatus::Pending) {
                throw new \LogicException('Cette demande a déjà été traitée ou la décision est invalide.');
            }
            if ($status === AdoptionStatus::Accepted) {
                if ($dog->getStatus() !== DogsStatus::Disponible->value) {
                    throw new \LogicException('Cet animal n’est plus disponible à l’adoption.');
                }
                $dog->setStatus(DogsStatus::EnAttente->value);
            }
            $adoption->setStatus($status);
        });
    }
}
