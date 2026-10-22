<?php

namespace App\Controller;

use App\Entity\Adoption;
use App\Entity\User;
use App\Form\AdoptionFormType;
use App\Repository\DogsRepository;
use App\Service\AdoptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;

class AdoptionController extends AbstractController
{
    #[Route('/dogs/{id}/adopt', name: 'dog_adopt', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function __invoke(int $id, Request $request, DogsRepository $dogs, AdoptionService $service): Response
    {
        $dog = $dogs->find($id);
        if ($dog === null) {
            throw $this->createNotFoundException('Animal introuvable');
        }
        $adoption = new Adoption();
        $adoption->setDog($dog);
        $user = $this->getUser();
        if ($user instanceof User) {
            $adoption->setUser($user);
            $adoption->setName($user->getUsername());
            $adoption->setEmail($user->getEmail());
        }
        $form = $this->createForm(AdoptionFormType::class, $adoption);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $service->submit($adoption);
            } catch (\LogicException $exception) {
                throw new ConflictHttpException($exception->getMessage());
            }
            $this->addFlash('success', 'Votre demande a été envoyée au refuge.');

            return $this->redirectToRoute('dog_show', ['id' => $id]);
        }

        return $this->render('adoption/new.html.twig', ['form' => $form->createView(), 'dog' => $dog]);
    }
}
