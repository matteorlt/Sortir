<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function save(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère les derniers messages pour une sortie spécifique
     */
    public function findRecentMessagesBySortie(int $sortieId, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->select('m', 'e')
            ->leftJoin('m.expediteur', 'e')
            ->where('m.sortie = :sortieId')
            ->setParameter('sortieId', $sortieId)
            ->orderBy('m.dateEnvoi', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les derniers messages pour le chat (toutes sorties confondues)
     */
    public function findRecentMessages(int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->select('m', 'e')
            ->leftJoin('m.expediteur', 'e')
            ->orderBy('m.dateEnvoi', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les messages non lus pour un utilisateur dans une sortie spécifique
     */
    public function findUnreadMessagesCountBySortie(int $sortieId): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.sortie = :sortieId')
            ->andWhere('m.lu = :lu')
            ->setParameter('sortieId', $sortieId)
            ->setParameter('lu', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les messages non lus pour un utilisateur
     */
    public function findUnreadMessagesCount(): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.lu = :lu')
            ->setParameter('lu', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Marque tous les messages comme lus
     */
    public function markAllAsRead(): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.lu', ':lu')
            ->setParameter('lu', true)
            ->getQuery()
            ->execute();
    }
}
