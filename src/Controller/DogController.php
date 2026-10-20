<?php

namespace App\Controller;

use App\Repository\DogsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route(path: '/dogs/{id}', name: 'dog_show', requirements: ['id' => '\d+'], methods: ['GET'])]
class DogController extends AbstractController
{
    public function __invoke(int $id, DogsRepository $dogsRepository): Response
    {
        $currentdog = $dogsRepository->find($id);
        if ($currentdog === null) {
            throw $this->createNotFoundException('Animal introuvable');
        }

        return $this->render('dogs/show.html.twig', [
            'dog' => $currentdog,
        ]);
    }
}
