<?php

namespace App\Controller;

use App\Form\DogFormType;
use App\Repository\DogsRepository;
use App\Service\CreateDogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DogModController extends AbstractController
{
    #[IsGranted('ROLE_EMPLOYEE')]
    #[Route('/dogs/modify/{id}', name: 'app_dog_modify', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifyDog(int $id, Request $request, DogsRepository $dogsRepository, CreateDogService $createDogService): Response
    {
        $dog = $dogsRepository->find($id);
        if ($dog === null) {
            throw $this->createNotFoundException('Animal introuvable');
        }

        $form = $this->createForm(DogFormType::class, $dog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $createDogService->saveDog($dog, $form->get('coverImage')->getData());
                $this->addFlash('success', 'Animal modifié.');

                return $this->redirectToRoute('dog_show', ['id' => $dog->getId()]);
            } catch (FileException) {
                $form->get('coverImage')->addError(new FormError('La photographie n’a pas pu être enregistrée. Réessayez.'));
            }
        }

        return $this->render('Form/DogBuilder.html.twig', [
            'form' => $form->createView(),
            'dog' => $dog,
        ]);
    }
}
