<?php

namespace App\Controller;

use App\Entity\Dogs;
use App\Enum\DogsStatus;
use App\Form\DogFormType;
use App\Service\CreateDogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CreatorDogController extends AbstractController
{
    #[IsGranted('ROLE_EMPLOYEE')]
    #[Route('/dogs/creator', name: 'app_dog_creator', methods: ['GET', 'POST'], priority: 10)]
    public function createDog(Request $request, CreateDogService $createDogService): Response
    {
        $dog = (new Dogs())->setStatus(DogsStatus::Disponible->value);
        $form = $this->createForm(DogFormType::class, $dog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $createDogService->saveDog($dog, $form->get('coverImage')->getData());
                $this->addFlash('success', 'Animal ajouté.');

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
