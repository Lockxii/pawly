<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\User\UserType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegisterUserController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()));
            try {
                $manager->persist($user);
                $manager->flush();
                $this->addFlash('success', 'Compte créé. Vous pouvez vous connecter.');

                return $this->redirectToRoute('app_login');
            } catch (UniqueConstraintViolationException) {
                $form->get('email')->addError(new FormError('Cette adresse e-mail est déjà utilisée.'));
            }
        }

        return $this->render('user/register.html.twig', ['form' => $form->createView()]);
    }
}
