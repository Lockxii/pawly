<?php

namespace App\Controller;

use App\Repository\DogsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
#[Route(path: '/', name: 'home', methods:['GET'])]
class HomeController
{
    public function __invoke(Environment $twig, DogsRepository $dogsRepository) : Response {

        return new Response($twig->render('home.html.twig',[
            'dogs' => $dogsRepository->findDogDisponible(),

        ]), Response::HTTP_OK);
    }
}
