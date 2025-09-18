<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'forgot_password')]
    public function forgotPassword(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $emailInput = $request->request->get('email');
            $user = $em->getRepository(User::class)->findOneBy(['email' => $emailInput]);

            if ($user) {
                $token = Uuid::v4()->toRfc4122();
                $user->setResetToken($token);
                $em->flush();

                $resetUrl = $this->generateUrl(
                    'reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $to = $_ENV['APP_ADMIN_EMAIL'] ?? $user->getEmail();

                $email = (new Email())
                    ->from(new Address('no-reply@send.task-manager.be', 'Task Manager'))
                    ->to($to)
                    ->subject('Réinitialisation du mot de passe')
                    ->html($this->renderView('emails/reset_password.html.twig', [
                        'resetToken' => $token,
                        'resetUrl'   => $this->generateUrl('reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]));

                try {
                    $mailer->send($email);
                    $this->addFlash('success', '📬 Un email de réinitialisation a été envoyé.');
                } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                    $this->addFlash('danger', "❌ Erreur d’envoi : ".$e->getMessage());
                }
            } else {
                // On ne révèle pas si l’email existe réellement (bonne pratique)
                $this->addFlash('error', '📬 Si un compte existe avec cet email, vous recevrez un message.');
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
            } elseif (
                strlen($password) < 8 ||
                !preg_match('/[A-Z]/', $password) ||
                !preg_match('/[a-z]/', $password) ||
                !preg_match('/\d/', $password)
            ) {
                $this->addFlash('danger', 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.');
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setResetToken(null);
                $em->flush();

                $this->addFlash('success', 'Mot de passe modifié. Vous pouvez vous connecter.');
                return $this->redirectToRoute('login');
            }
        }

        return $this->render('security/reset_password.html.twig', ['token' => $token]);
    }
}