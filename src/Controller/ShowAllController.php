<?php

namespace App\Controller;

use App\Repository\DogsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ShowAllController extends AbstractController
{
    #[IsGranted('ROLE_EMPLOYEE')]
    #[Route('/animals', name: 'all', methods: ['GET'])]
    public function __invoke(DogsRepository $dogs): Response
    {
        return $this->render('show_all/index.html.twig', ['dogs' => $dogs->findBy([], ['id' => 'DESC'])]);
    }
}
