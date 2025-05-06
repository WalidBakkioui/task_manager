<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Uid\Uuid;

class ForgotPasswordController extends AbstractController

{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/forgot-password', name: 'forgot_password')]
    public function forgotPassword(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $emailInput = $request->request->get('email');

            $user = $em->getRepository(User::class)->findOneBy(['email' => $emailInput]);

            if ($user) {
                $token = Uuid::v4()->toRfc4122(); // génère un token unique
                $user->setResetToken($token);
                $em->flush();

                $resetUrl = $this->generateUrl('reset_password', ['token' => $token], 0);

                $email = (new Email())
                    ->from('noreply@monsite.local')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation du mot de passe')
                    ->html('<p>Pour réinitialiser votre mot de passe, cliquez ici : 
                        <a href="http://localhost:8000' . $resetUrl . '">Réinitialiser le mot de passe</a></p>');

                $mailer->send($email);

                $this->addFlash('success', 'Un email de réinitialisation a été envoyé.');
            } else {
                $this->addFlash('danger', 'Aucun compte avec cet email.');
            }
        }

        return $this->render('security/forgot_password.html.twig');
    }


    #[Route('/reset-password/{token}', name: 'reset_password')]
    public function resetPassword(string $token, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user) {
            throw $this->createNotFoundException('Lien invalide ou expiré.');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirmPassword');

            if ($password !== $confirmPassword) {
                $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setResetToken(null); // enlever le token après succès
                $em->flush();

                $this->addFlash('success', 'Mot de passe modifié. Vous pouvez vous connecter.');
                return $this->redirectToRoute('login');
            }
        }

        return $this->render('security/reset_password.html.twig', ['token' => $token]);
    }
}
