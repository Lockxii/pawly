<?php

namespace App\Controller;

use App\Enum\AdoptionStatus;
use App\Form\AdoptionDecisionType;
use App\Repository\AdoptionRepository;
use App\Service\AdoptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYEE')]
class AdoptionManagementController extends AbstractController
{
    #[Route('/adoptions', name: 'adoption_list', methods: ['GET'])]
    public function index(AdoptionRepository $adoptions): Response
    {
        return $this->render('adoption/index.html.twig', ['adoptions' => $adoptions->findBy([], ['createdAt' => 'DESC'])]);
    }

    #[Route('/adoptions/{id}', name: 'adoption_review', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function review(int $id, Request $request, AdoptionRepository $adoptions, AdoptionService $service): Response
    {
        $adoption = $adoptions->find($id);
        if ($adoption === null) {
            throw $this->createNotFoundException('Demande introuvable');
        }
        $form = $this->createForm(AdoptionDecisionType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $service->decide($adoption, $form->get('status')->getData());
                $this->addFlash('success', 'Demande traitée.');

                return $this->redirectToRoute('adoption_list');
            } catch (\LogicException $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('adoption/review.html.twig', [
            'adoption' => $adoption,
            'form' => $adoption->getStatus() === AdoptionStatus::Pending ? $form->createView() : null,
        ]);
    }
}
