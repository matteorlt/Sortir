<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Repository\SortieRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SortieService
{
    public function __construct(
        private SortieRepository $sortieRepository,
        private LoggerInterface $logger
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
        $sortie = $this->sortieRepository->find($id); // ici : $sortieRepository (pas $sortieRepo)

        if (!$sortie) {
            $this->logger->warning("Sortie non trouvée pour l'ID {$id}");
            throw new NotFoundHttpException("Sortie introuvable.");
        }

        return $sortie;
    }
}
