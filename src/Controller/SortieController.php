<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\Enum\Statut;
use App\Enum\CampusEnum;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\LieuRepository;
use App\Repository\CampusRepository;
use App\Repository\SortieRepository;
use App\Repository\VilleRepository;
use App\Service\SortieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sortie', name: 'app_sortie_')]
final class SortieController extends AbstractController
{

    #[Route('/create', name: 'create')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        EtatRepository $etatRepo,
        LieuRepository $lieuRepo,
        CampusRepository $campusRepo,
        VilleRepository $villeRepo
    ): Response
    {
        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Données issues de l’API Adresse via champs cachés
            $labelComplet = (string) $form->get('adresse_full')->getData();
            $rue = (string) $form->get('rue')->getData();
            $lat = (float) $form->get('latitude')->getData();
            $lon = (float) $form->get('longitude')->getData();
            $villeNom = (string) $form->get('ville_nom')->getData();
            $cp = (string) $form->get('code_postal')->getData();

            if (!$labelComplet || !$rue || !$lat || !$lon || !$villeNom || !$cp) {
                $this->addFlash('danger', "Veuillez sélectionner une adresse dans les suggestions.");
                return $this->render('sortie/create.html.twig', [
                    'sortieForm' => $form->createView(),
                ]);
            }

            // Ville: recherche par nom + code postal, sinon création
            $ville = $villeRepo->findOneBy(['nomVille' => $villeNom, 'codePostal' => $cp]);
            if (!$ville) {
                $ville = (new Ville())
                    ->setNomVille($villeNom)
                    ->setCodePostal($cp);
                $em->persist($ville);
            }

            // Lieu: soit on retrouve par label complet, soit on crée
            $lieu = $lieuRepo->findOneBy(['nomLieu' => $labelComplet]);
            if (!$lieu) {
                $lieu = (new Lieu())
                    ->setNomLieu(sprintf('%s (%.6f, %.6f)', $labelComplet, $lat, $lon))
                    ->setRue($rue)
                    ->setLatitude($lat)
                    ->setLongitude($lon)
                    ->setVille($ville);
                $em->persist($lieu);
            }
            $sortie->setLieu($lieu);

            // Etat via enum Statut
            /** @var Statut $statut */
            $statut = $form->get('statut')->getData();
            $etat = $etatRepo->findOneBy(['libelle' => $statut]);
            if (!$etat) {
                $etat = (new Etat())->setLibelle($statut);
                $em->persist($etat);
            }
            $sortie->setEtat($etat);

            // Campus via enum CampusEnum (recherche par nomCampus)
            /** @var CampusEnum|null $campusEnum */
            $campusEnum = $form->get('campus')->getData();
            if ($campusEnum) {
                $campus = $campusRepo->findOneBy(['nomCampus' => $campusEnum->value]);
                if ($campus) {
                    $sortie->setCampus($campus);
                }
            }

            // Enregistrement
            $em->persist($sortie);
            $em->flush();

            $this->addFlash('success', 'La sortie a bien été créée.');
            return $this->redirectToRoute('app_sortie_list');
        }

        return $this->render('sortie/create.html.twig', [
            'sortieForm' => $form->createView(),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request, SortieService $sortieService): Response
    {
        $sortDate = $request->query->get('sortDate');
        $participantRange = $request->query->get('participantRange');
        $campus = $request->query->get('campus');
        $search = $request->query->get('search');
        $categorie = $request->query->get('categorie');
        $isInscrit = $request->query->getBoolean('isInscrit');
        $isOuvert = $request->query->getBoolean('isOuvert');

        $sorties = $sortieService->filterSorties($sortDate, $participantRange, $campus, $search, $categorie, $isInscrit, $isOuvert, $this->getUser());

        return $this->render('sortie/list.html.twig', [
            'sorties' => $sorties,
        ]);
    }

    #[Route('/{id}', name: 'show')]
    public function show(int $id, SortieService $sortieService): Response
    {
        $sortie = $sortieService->getSortieDetails($id);

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }


    #[Route('/{id}/rejoindre', name: 'rejoindre', methods: ['POST'])]
    public function rejoindre(int $id, SortieService $sortieService, Request $request): Response
    {
        $user = $this->getUser();


        if (!$user) {
            $this->addFlash('danger', "Vous devez être connecté.");
            return $this->redirectToRoute('app_utilisateur_inscription');
        }

        /** @var Participant $participant */
        $participant = $user;

        try {
            $result = $sortieService->rejoindreSortie($id, $participant);

            if ($result) {
                $this->addFlash('success', 'Vous avez rejoint la sortie !');
            } else {
                $this->addFlash('info', 'Vous êtes déjà inscrit à cette sortie.');
            }

        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_sortie_list');
    }
}
