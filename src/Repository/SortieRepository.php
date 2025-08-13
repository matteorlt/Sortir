<?php

namespace App\Repository;

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

    public function findFiltered(?string $sortDate, ?string $sortInscription, ?string $campus, ?string $search, ?string $categorie): array
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

        if ($sortInscription) {
            $qb->addOrderBy('s.nbInscriptionMax', strtoupper($sortInscription) === 'DESC' ? 'DESC' : 'ASC');
        }

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
