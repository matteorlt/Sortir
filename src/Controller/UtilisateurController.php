<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\CampusRepository;
use App\Form\InscriptionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UtilisateurController extends AbstractController
{
    #[Route('/utilisateur', name: 'app_utilisateur')]
    public function index(): Response
    {
        return $this->render('utilisateur/index.html.twig', [
            'controller_name' => 'UtilisateurController',
        ]);
    }

    #[Route('/utilisateur/inscription', name: 'app_utilisateur_inscription')]
    public function inscription(
        CampusRepository $campusRepository,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $participant = new Participant();
        $form = $this->createForm(InscriptionType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mot de passe
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $hashed = $passwordHasher->hashPassword($participant, $plainPassword);
            $participant->setMotDePasse($hashed);


            // Activer par défaut
            if (method_exists($participant, 'setActif')) {
                $participant->setActif(true);
            }

            // Upload photo (optionnel)
            $photoFile = $form->get('photoFile')->getData();
            if ($photoFile) {
                $newName = uniqid('pp_', true).'.'.$photoFile->guessExtension();
                try {
                    $photoFile->move($this->getParameter('kernel.project_dir').'/public/uploads/photos', $newName);
                    if (method_exists($participant, 'setPhoto')) {
                        $participant->setPhoto($newName);
                    }
                } catch (FileException $e) {
                    $this->addFlash('danger', "Échec de l'upload de la photo.");
                    return $this->render('utilisateur/inscription.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }

            // Le campus est maintenant directement assigné par le formulaire


            $em->persist($participant);
            $em->flush();

            $this->addFlash('success', 'Compte créé avec succès !');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('utilisateur/inscription.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
