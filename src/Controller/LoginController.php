<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route(path: '/', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils, Security $security, AuthorizationCheckerInterface $authChecker): Response
    {

        if ($security->getUser() && in_array('ROLE_BANNED', $security->getUser()->getRoles())) {
            $this->container->get('security.token_storage')->setToken(null);
            return $this->redirectToRoute('login');
        }

        if ($security->getUser() && !$authChecker->isGranted('ACCESS_SITE')) {
            $this->addFlash('error', 'Votre compte est banni.');
            $this->container->get('security.token_storage')->setToken(null); // déconnexion automatique
            return $this->redirectToRoute('login');
        }

        // Vérifie si l'utilisateur est déjà connecté
        if ($security->getUser()) {
            if (in_array('ROLE_BANNED', $security->getUser()->getRoles())) {
                $this->addFlash('error', 'Votre compte a été banni. Contactez l\'administrateur.');
                return $this->redirectToRoute('login');
            }
            // Si l'utilisateur est déjà connecté, redirige vers la page protégée
            return $this->redirectToRoute('task_index'); // rediriger vers la page des tâches
        }

        // Si l'utilisateur n'est pas connecté, afficher la page de connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        // Symfony s'occupe du logout automatiquement
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
