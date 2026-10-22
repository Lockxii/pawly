<?php

namespace App\Controller;

use App\Repository\DogsRepository;
use App\Form\DogSearchType;
use App\Enum\DogsStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route(path: '/', name: 'home', methods:['GET'])]
class HomeController extends AbstractController
{
    public function __invoke(Request $request, DogsRepository $dogsRepository): Response
    {
        $form = $this->createForm(DogSearchType::class, null, ['method' => 'GET', 'csrf_protection' => false]);
        $form->remove('status');
        $form->handleRequest($request);
        $data = $form->getData() ?? [];
        $dogs = $form->isSubmitted() && !$form->isValid()
            ? [] : $dogsRepository->search($data['q'] ?? '', DogsStatus::Disponible->value);

        return $this->render('home.html.twig', [
            'dogs' => $dogs,
            'searchForm' => $form->createView(),
        ]);
    }
}
