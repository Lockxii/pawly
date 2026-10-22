<?php

namespace App\Controller\User;

use App\Repository\AdoptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MyAdoptionsController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/mes-demandes', name: 'my_adoptions', methods: ['GET'])]
    public function __invoke(AdoptionRepository $adoptions): Response
    {
        return $this->render('user/registrations.html.twig', [
            'adoptions' => $adoptions->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']),
        ]);
    }
}
