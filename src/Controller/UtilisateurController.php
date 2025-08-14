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
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Form\CsvImportType;
use Symfony\Component\Validator\Validator\ValidatorInterface;


final class UtilisateurController extends AbstractController
{

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


            $actifValue = $form->get('actif')->getData();
            if (method_exists($participant, 'setActif')) {
                $participant->setActif($actifValue ?? true); // true par défaut si null
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
            return $this->redirectToRoute('app_login');
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

        return $this->render('utilisateur/profil_prive.html.twig', [
            'utilisateur' => $this->getUser(),
        ]);
    }

    #[Route('/utilisateur/profil/{id}', name: 'app_utilisateur_profil_public', requirements: ['id' => '\d+'])]
    public function profilPublic(int $id, EntityManagerInterface $em): Response
    {
        $participant = $em->getRepository(Participant::class)->find($id);

        if (!$participant) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Vérifier si l'utilisateur est actif (optionnel)
        if (!$participant->isActif()) {
            throw $this->createNotFoundException('Profil non disponible');
        }

        return $this->render('utilisateur/profil_public.html.twig', [
            'utilisateur' => $participant,
        ]);
    }

    #[Route('/profil/edit', name: 'app_utilisateur_edit_profil', methods: ['GET', 'POST'])]
    public function profilEdit(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var Participant $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            // CSRF
            $token = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('edit_profil', $token)) {
                $this->addFlash('danger', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('app_utilisateur_edit_profil');
            }

            // Récupération/normalisation des champs
            $pseudo     = trim((string) $request->request->get('pseudo', ''));
            $nom        = trim((string) $request->request->get('nom', ''));
            $prenom     = trim((string) $request->request->get('prenom', ''));
            $telephone  = trim((string) $request->request->get('telephone', ''));
            $mail       = trim((string) $request->request->get('mail', ''));
            $actif      = trim((string) $request->request->get('actif', ''));

            // Validations simples (adaptez selon vos règles)
            $errors = [];
            if ($pseudo === '')   { $errors[] = 'Le pseudo est obligatoire.'; }
            if ($nom === '')      { $errors[] = 'Le nom est obligatoire.'; }
            if ($prenom === '')   { $errors[] = 'Le prénom est obligatoire.'; }
            if ($mail === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse e‑mail invalide.';
            }

            // Gestion upload photo (optionnelle)
            $uploadedFile = $request->files->get('photoFile');
            if ($uploadedFile) {
                try {
                    $newName = uniqid('pp_', true) . '.' . $uploadedFile->guessExtension();
                    $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
                    $uploadedFile->move($targetDir, $newName);
                    if (method_exists($user, 'setPhoto')) {
                        $user->setPhoto($newName);
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Échec de l'upload de la photo.";
                }
            }

            if (count($errors) > 0) {
                foreach ($errors as $err) {
                    $this->addFlash('danger', $err);
                }
                // On ré-affiche le formulaire avec les messages
                return $this->render('utilisateur/edit_profil.html.twig', [
                    'utilisateur' => $user,
                ]);
            }

            // Persistance des modifications
            $user->setPseudo($pseudo);
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setTelephone($telephone !== '' ? $telephone : null);
            $user->setMail($mail);
            $user->setActif($actif);

            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_utilisateur_profil');
        }

        // GET: affichage du formulaire
        return $this->render('utilisateur/edit_profil.html.twig', [
            'utilisateur' => $user,
        ]);
    }

}