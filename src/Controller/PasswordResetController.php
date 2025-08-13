<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use App\Service\PasswordResetTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PasswordResetController extends AbstractController
{
    #[Route('/mot-de-passe/oublie', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        ParticipantRepository $participantRepository,
        MailerInterface $mailer,
        PasswordResetTokenService $tokenService,
        ValidatorInterface $validator,
    ): Response {
        if ($request->isMethod('GET')) {
            return $this->render('security/forgot_password.html.twig');
        }

        $email = (string) $request->request->get('email');

        $violations = $validator->validate($email, [
            new NotBlank(message: 'Merci de renseigner votre email.'),
            new EmailConstraint(message: 'Email invalide.'),
        ]);
        if (count($violations) > 0) {
            $this->addFlash('danger', (string) $violations[0]->getMessage());
            return $this->render('security/forgot_password.html.twig', [
                'email' => $email,
            ]);
        }

        $user = $participantRepository->findOneBy(['mail' => $email]);

        // Ne pas révéler si l'email est enregistré
        if ($user instanceof Participant) {
            $expiresAt = new \DateTimeImmutable('+1 hour');
            $token = $tokenService->generateToken($user, $expiresAt);
            $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], 0);
            $absoluteUrl = $request->getSchemeAndHttpHost() . $resetUrl;

            $message = (new Email())
                ->from('no-reply@example.test')
                ->to($email)
                ->subject('Réinitialisation de votre mot de passe')
                ->html($this->renderView('emails/reset_password.html.twig', [
                    'user' => $user,
                    'resetUrl' => $absoluteUrl,
                    'expiresAt' => $expiresAt,
                ]));

            try {
                $mailer->send($message);
            } catch (\Throwable $e) {
                // On ignore en prod, mais on signale en dev
                $this->addFlash('warning', "L'email n'a pas pu être envoyé en local: " . $e->getMessage());
            }
        }

        $this->addFlash('success', 'Si un compte correspond à cet email, vous recevrez un lien de réinitialisation.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/mot-de-passe/reinitialiser/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(
        string $token,
        Request $request,
        PasswordResetTokenService $tokenService,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $tokenService->validateAndGetUser($token);
        if (!$user instanceof Participant) {
            $this->addFlash('danger', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('GET')) {
            return $this->render('security/reset_password.html.twig', [
                'token' => $token,
            ]);
        }

        $password = (string) $request->request->get('password');
        $confirm = (string) $request->request->get('password_confirm');

        if ($password === '' || strlen($password) < 8) {
            $this->addFlash('danger', 'Le mot de passe doit contenir au moins 8 caractères.');
            return $this->render('security/reset_password.html.twig', [
                'token' => $token,
            ]);
        }

        if ($password !== $confirm) {
            $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
            return $this->render('security/reset_password.html.twig', [
                'token' => $token,
            ]);
        }

        $hashed = $passwordHasher->hashPassword($user, $password);
        $user->setMotDePasse($hashed);
        $em->flush();

        $this->addFlash('success', 'Votre mot de passe a été mis à jour. Vous pouvez vous connecter.');
        return $this->redirectToRoute('app_login');
    }
}