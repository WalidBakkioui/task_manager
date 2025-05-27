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
    #[Route('/', name: 'homepage')]
    public function homepage(): Response
    {
        return $this->render('task/index.html.twig');
    }

    #[Route(path: '/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils, Security $security, AuthorizationCheckerInterface $authChecker): Response
    {
        if ($security->getUser() && !$authChecker->isGranted('ACCESS_SITE')) {
            $this->addFlash('error', 'Votre compte est banni.');
            $this->container->get('security.token_storage')->setToken(null);
            return $this->redirectToRoute('login');
        }

        if ($security->getUser()) {
            if (in_array('ROLE_BANNED', $security->getUser()->getRoles()) || !$authChecker->isGranted('ACCESS_SITE')) {
                $this->addFlash('error', 'Votre compte est banni.');
                $this->container->get('security.token_storage')->setToken(null);
                return $this->redirectToRoute('login');
            }

            return $this->redirectToRoute('task_index');
        }

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

        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
