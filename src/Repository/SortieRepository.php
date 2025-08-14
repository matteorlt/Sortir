<?php

namespace App\Repository;

use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    /**
     * Rangements des évènements dans l'ordre croissant
     * @return Sortie[] Returns an array of Sortie objects
     */

    public function findFiltered(?string $sortDate, ?string $participantRange, ?string $campus, ?string $search, ?string $categorie, ?string $etat, bool $isInscrit = false, ?Participant $participant = null, bool $isOrganisateur = false): array
    {
        $qb = $this->createQueryBuilder('s');

        if ($campus) {
            $qb->join('s.campus', 'c')
                ->andWhere('c.nomCampus = :campus')
                ->setParameter('campus', $campus);
        }

        if ($search) {
            $qb->andWhere('s.nomSortie LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($categorie) {
            $qb->andWhere('s.categorie = :categorie')
                ->setParameter('categorie', $categorie);
        }

        // Tri
        if ($sortDate) {
            $qb->addOrderBy('s.dateDebut', strtoupper($sortDate) === 'DESC' ? 'DESC' : 'ASC');
        }

        if ($participantRange) {
            switch ($participantRange) {
                case '0-5':
                    $qb->andWhere('s.nbInscriptionMax BETWEEN 0 AND 5');
                    break;
                case '6-10':
                    $qb->andWhere('s.nbInscriptionMax BETWEEN 6 AND 10');
                    break;
                case '11-15':
                    $qb->andWhere('s.nbInscriptionMax BETWEEN 11 AND 15');
                    break;
                case '16-20':
                    $qb->andWhere('s.nbInscriptionMax BETWEEN 16 AND 20');
                    break;
                case '21+':
                    $qb->andWhere('s.nbInscriptionMax > 20');
                    break;
            }
        }

        if ($isInscrit && $participant) {
            $qb->join('s.inscriptions', 'i')
                ->andWhere('i.participant = :participant')
                ->setParameter('participant', $participant);
        }

        if ($isOrganisateur && $participant) {
            // Suppose que la propriété organisateur dans Sortie est un ManyToOne vers Participant
            $qb->andWhere('s.participant = :participantOrganisateur')
                ->setParameter('participantOrganisateur', $participant);
        }

        if ($etat) {
            $now = new \DateTime();

            if ($etat === 'ouvert') {
                // Sorties où dateCloture est dans le futur et nombre d'inscrits < max
                $qb
                    ->andWhere('s.dateCloture > :now')
                    ->setParameter('now', $now)
                    ->andWhere('(SIZE(s.inscriptions) < s.nbInscriptionMax)');
            } elseif ($etat === 'ferme') {
                // Sorties où dateCloture est passé ou nombre d'inscrits >= max
                $qb->andWhere('s.dateCloture <= :now OR SIZE(s.inscriptions) >= s.nbInscriptionMax')
                    ->setParameter('now', $now);
            } elseif ($etat === 'encours') {
                // Sorties où dateDebut <= now <= dateFin (dateDebut + durée)
                $qb
                    ->andWhere('s.dateDebut <= :now AND DATE_ADD(s.dateDebut, s.duree, \'HOUR\') >= :now')
                    ->setParameter('now', $now);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les sorties qui ont lieu dans 48h
     */
    public function findSortiesIn48h(\DateTime $date48h): array
    {
        $qb = $this->createQueryBuilder('s');
        
        $qb->andWhere('s.dateDebut BETWEEN :now AND :date48h')
            ->andWhere('s.etat IS NOT NULL')
            ->setParameter('now', new \DateTime())
            ->setParameter('date48h', $date48h)
            ->orderBy('s.dateDebut', 'ASC');

        return $qb->getQuery()->getResult();
    }




//    /**
//     * @return Sortie[] Returns an array of Sortie objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Sortie
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
