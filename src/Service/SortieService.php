<?php

namespace App\Service;

use App\Entity\Inscription;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SortieService
{
    public function __construct(
        private SortieRepository $sortieRepository,
        private LoggerInterface $logger,
        private EntityManagerInterface $em
    ) {}

    /**
     * Récupère toutes les sorties publiées.
     */
    public function getPublishedSorties(): array
    {
        $sorties = $this->sortieRepository->findAll(); // tu peux adapter le filtre si besoin

        $this->logger->info("La liste des sorties publiées a été récupérée avec succès !");

        return $sorties;
    }

    /**
     * Récupère une sortie par son ID, ou déclenche une 404.
     */
    public function getSortieDetails(int $id): Sortie
    {
        $sortie = $this->sortieRepository->find($id);

        if (!$sortie) {
            $this->logger->warning("Sortie non trouvée pour l'ID {$id}");
            throw new NotFoundHttpException("Sortie introuvable.");
        }

        return $sortie;
    }

    public function filterSorties(?string $sortDate, ?string $participantRange, ?string $campus, ?string $search, ?string $categorie, bool $isInscrit = false, bool $isOuvert = false, ?Participant $participant = null)
    : array
    {
        return $this->sortieRepository->findFiltered($sortDate, $participantRange, $campus, $search, $categorie, $isInscrit, $isOuvert, $participant);
    }


    public function rejoindreSortie(int $sortieId, Participant $participant): bool
    {
        $sortie = $this->sortieRepository->find($sortieId);

        if (!$sortie) {
            throw new \InvalidArgumentException("Sortie introuvable.");
        }

        // Vérifier si déjà inscrit
        foreach ($sortie->getInscriptions() as $inscription) {
            if ($inscription->getParticipant() === $participant) {
                return false; // déjà inscrit
            }
        }

        // Créer une nouvelle inscription
        $inscription = new Inscription();
        $inscription->setParticipant($participant);
        $inscription->setSortie($sortie);
        $inscription->setDateInscription(new \DateTimeImmutable());

        $this->em->persist($inscription);
        $this->em->flush();

        return true;
    }
}
