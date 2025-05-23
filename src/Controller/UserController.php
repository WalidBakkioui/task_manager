<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    #[Route('/user/profile', name: 'user_profile')]
    public function profile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            if ($request->request->has('update_username')) {
                $username = $request->request->get('username');

                if (empty($username) || strlen($username) > 16) {
                    $this->addFlash('profile_error', 'Le nom d’utilisateur ne peut pas dépasser 16 caractères.');
                    return $this->redirectToRoute('user_profile');
                }

                $user->setUsername($username);
                $em->flush();

                $this->addFlash('profile_success', 'Nom d\'utilisateur mis à jour !');
            }

            if ($request->request->has('update_password')) {
                $password = $request->request->get('new_password');
                $confirmPassword = $request->request->get('confirm_password');

                if ($password !== $confirmPassword) {
                    $this->addFlash('profile_error', 'Les mots de passe ne correspondent pas.');
                    return $this->redirectToRoute('user_profile');
                }

                if (
                    strlen($password) < 8 ||
                    !preg_match('/[A-Z]/', $password) ||
                    !preg_match('/[a-z]/', $password) ||
                    !preg_match('/\d/', $password)
                ) {
                    $this->addFlash('profile_error', 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.');
                    return $this->redirectToRoute('user_profile');
                }

                $user->setPassword(
                    $passwordHasher->hashPassword($user, $password)
                );
                $em->flush();

                $this->addFlash('profile_success', 'Mot de passe modifié avec succès !');
                return $this->redirectToRoute('user_profile');
            }
        }

        return $this->render('task/profile.html.twig', [
            'user' => $user,
        ]);
    }

}
