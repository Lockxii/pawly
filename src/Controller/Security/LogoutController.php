<?php

namespace App\Controller\Security;

use Symfony\Component\Routing\Attribute\Route;

#[Route('/logout', name: 'logout')]
class LogoutController
{
    public function __invoke(): never
    {
        throw new \LogicException('La déconnexion est gérée par Symfony.');
    }

}
