<?php

namespace App\Controller\User;

use App\Form\User\AdminUserType;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_users', methods: ['GET'])]
    public function index(UserRepository $users): Response
    {
        return $this->render('user/all.html.twig', ['users' => $users->findBy([], ['username' => 'ASC'])]);
    }

    #[Route('/admin/users/{id}', name: 'admin_user_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, UserRepository $users, EntityManagerInterface $manager): Response
    {
        $user = $users->find($id);
        if ($user === null) {
            throw $this->createNotFoundException('Utilisateur introuvable');
        }
        $isCurrentUser = $this->getUser()->getUserIdentifier() === $user->getUserIdentifier();
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($isCurrentUser && !in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                $form->get('roles')->addError(new FormError('Vous ne pouvez pas retirer vos propres droits administrateur.'));
            } else {
                try {
                    $manager->flush();
                    $this->addFlash('success', 'Utilisateur modifié.');

                    return $this->redirectToRoute('admin_users');
                } catch (UniqueConstraintViolationException) {
                    $form->get('email')->addError(new FormError('Cette adresse e-mail est déjà utilisée.'));
                }
            }
        }

        return $this->render('user/edit.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }
}
