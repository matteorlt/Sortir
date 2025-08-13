<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\CampusRepository;
use App\Form\InscriptionType;
use App\Service\Importer\ParticipantCsvImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Form\CsvImportType;

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

    #[Route ('/utilisateur/admin', name: 'app_utilisateur_admin', methods: ['GET', 'POST'])]
    public function admin(
        Request $request,
        ParticipantCsvImporter $importer
    ): Response
    {
        $form = $this->createForm(CsvImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploaded */
            $uploaded = $form->get('file')->getData();
            $separator = (string) ($form->get('separator')->getData() ?: ';');

            $targetDir = sys_get_temp_dir();
            $targetName = 'participants_import_' . bin2hex(random_bytes(8)) . '.csv';
            $targetPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $targetName;

            try {
                $uploaded->move($targetDir, $targetName);
            } catch (FileException $e) {
                $this->addFlash('danger', 'Erreur lors de la copie du fichier: ' . $e->getMessage());
                return $this->render('utilisateur/admin.html.twig', [
                    'controller_name' => 'UtilisateurController',
                    'importForm' => $form->createView(),
                ]);
            }

            try {
                $result = $importer->import($targetPath, $separator);
                @unlink($targetPath);

                $this->addFlash('success', sprintf(
                    'Import terminé: %d inséré(s), %d ignoré(s), %d erreur(s).',
                    $result['inserted'] ?? 0,
                    $result['skipped'] ?? 0,
                    $result['errors'] ?? 0
                ));

                return $this->redirectToRoute('app_utilisateur_admin');
            } catch (\Throwable $e) {
                @unlink($targetPath);
                $this->addFlash('danger', 'Erreur lors de l’import: ' . $e->getMessage());
            }
        }

        return $this->render('utilisateur/admin.html.twig', [
            'controller_name' => 'UtilisateurController',
            'importForm' => $form->createView(),
        ]);
    }

    #[Route('/profil', name: 'app_utilisateur_profil')]
    public function profil(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('utilisateur/profil.html.twig', [
            'utilisateur' => $this->getUser(),
        ]);
    }
}