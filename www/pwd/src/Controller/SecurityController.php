<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Check if the user has ROLE_NOT
        if ($this->getUser() && in_array('ROLE_NOT', $this->getUser()->getRoles())) {
            // Add a flash message to be displayed in the template
            $this->addFlash('danger', 'Votre compte est bloqué. Contactez l\'administrateur.');

            // Redirect to some page or return a response
            return $this->redirectToRoute('app_contact'); // Change 'your_blocked_route' to the actual route you want to redirect to.
        }

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
