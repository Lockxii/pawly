<?php

namespace App\Controller;

use App\Repository\DogsRepository;
use App\Form\DogSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ShowAllController extends AbstractController
{
    #[IsGranted('ROLE_EMPLOYEE')]
    #[Route('/animals', name: 'all', methods: ['GET'])]
    public function __invoke(Request $request, DogsRepository $dogs): Response
    {
        $form = $this->createForm(DogSearchType::class, null, ['method' => 'GET', 'csrf_protection' => false]);
        $form->handleRequest($request);
        $data = $form->getData() ?? [];
        $results = $form->isSubmitted() && !$form->isValid()
            ? [] : $dogs->search($data['q'] ?? '', $data['status'] ?? null);

        return $this->render('show_all/index.html.twig', [
            'dogs' => $results,
            'searchForm' => $form->createView(),
        ]);
    }
}
