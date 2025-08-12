<?php

namespace App\Controller;

use App\Entity\Participant; // Importation de l'entité Participant
use App\Form\InscriptionType; // Importation du formulaire InscriptionFormType
use Doctrine\ORM\EntityManagerInterface; // Interface pour interagir avec la base de données
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Classe de base pour les contrôleurs Symfony
use Symfony\Component\HttpFoundation\Request; // Classe pour gérer les requêtes HTTP
use Symfony\Component\HttpFoundation\Response; // Classe pour gérer les réponses HTTP
use Symfony\Component\Routing\Annotation\Route; // Annotation pour définir les routes
#[Route('/utilisateur', name: 'utilisateur_')]
final class UtilisateurController extends AbstractController
{
    #[Route('/inscription', name: 'inscription', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        // Création d'une nouvelle instance de Participant
        $participant = new Participant();

        // Création du formulaire basé sur le type InscriptionFormType et l'entité Participant
        $form = $this->createForm(InscriptionType::class, $participant);

        // Traitement de la requête HTTP pour le formulaire
        $form->handleRequest($request);

        // Vérification si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Persistance de l'entité Participant dans la base de données
            $em->persist($participant);
            $em->flush();

            // Redirection vers la page de confirmation ou une autre page après l'inscription
            return $this->redirectToRoute('utilisateur');
        }

        // Rendu du formulaire dans la vue inscription.html.twig
        return $this->render('utilisateur/inscription.html.twig', [
            'form' => $form->createView(), // Passage de la vue du formulaire à la vue Twig
        ]);
    }
}