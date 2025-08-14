<?php

namespace App\Service;

use App\Entity\Inscription;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Repository\SortieRepository;
use App\Service\SortieEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SortieService
{
    public function __construct(
        private SortieRepository $sortieRepository,
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private SortieEmailService $emailService
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

    public function filterSorties(?string $sortDate, ?string $participantRange, ?string $campus, ?string $search, ?string $categorie, ?string $etat,bool $isInscrit = false, ?Participant $participant = null, bool $isOrganisateur = false)
    : array
    {
        return $this->sortieRepository->findFiltered($sortDate, $participantRange, $campus, $search, $categorie, $etat, $isInscrit, $participant, $isOrganisateur);
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

        // Vérifier si la sortie est pleine
        if (count($sortie->getInscriptions()) >= $sortie->getNbInscriptionMax()) {
            return false; // sortie pleine, pas d'inscription possible
        }

        // Créer une nouvelle inscription
        $inscription = new Inscription();
        $inscription->setParticipant($participant);
        $inscription->setSortie($sortie);
        $inscription->setDateInscription(new \DateTimeImmutable());

        $this->em->persist($inscription);
        $this->em->flush();

        // Envoyer l'email de confirmation d'inscription
        try {
            $this->emailService->sendInscriptionConfirmation($inscription);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de confirmation: ' . $e->getMessage());
        }

        return true;
    }

    public function seDesisterSortie(int $sortieId, Participant $participant): bool
    {
        $sortie = $this->sortieRepository->find($sortieId);

        if (!$sortie) {
            throw new \InvalidArgumentException("Sortie introuvable.");
        }

        // Trouver l'inscription
        $inscription = null;
        foreach ($sortie->getInscriptions() as $insc) {
            if ($insc->getParticipant() === $participant) {
                $inscription = $insc;
                break;
            }
        }

        if (!$inscription) {
            return false; // pas inscrit
        }

        // Supprimer l'inscription
        $this->em->remove($inscription);
        $this->em->flush();

        // Envoyer l'email de confirmation de désistement
        try {
            $this->emailService->sendDesistementConfirmation($inscription);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de désistement: ' . $e->getMessage());
        }

        return true;
    }

    public function desisterSortie(int $sortieId, Participant $participant): void
    {
        $sortie = $this->sortieRepository->find($sortieId);

        if (!$sortie) {
            throw new \InvalidArgumentException("Sortie introuvable.");
        }

        foreach ($sortie->getInscriptions() as $inscription) {
            if ($inscription->getParticipant() === $participant) {
                $this->em->remove($inscription);
                $this->em->flush();
                return;
            }
        }

        throw new \InvalidArgumentException("Vous n'êtes pas inscrit à cette sortie.");
    }

}
