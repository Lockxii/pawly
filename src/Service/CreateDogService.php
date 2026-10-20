<?php

namespace App\Service;

use App\Entity\Dogs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class CreateDogService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FileUploaderService $fileUploaderService,
    ) {
    }

    public function saveDog(Dogs $dog, ?UploadedFile $image = null): void
    {
        if ($image !== null) {
            $dog->setCoverImage($this->fileUploaderService->upload($image));
        }

        $this->entityManager->persist($dog);
        $this->entityManager->flush();
    }
}
