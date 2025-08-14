<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

class ChatNotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotifierInterface $notifier
    ) {}

    /**
     * Envoyer une notification pour un nouveau message
     */
    public function notifyNewMessage(Message $message): void
    {
        // Récupérer tous les participants connectés (sauf l'expéditeur)
        $participants = $this->entityManager->getRepository(Participant::class)->findAll();
        
        foreach ($participants as $participant) {
            if ($participant->getId() !== $message->getExpediteur()->getId()) {
                $this->sendNotification(
                    $participant,
                    'Nouveau message',
                    sprintf(
                        '%s %s a envoyé un message: %s',
                        $message->getExpediteur()->getPrenom(),
                        $message->getExpediteur()->getNom(),
                        substr($message->getContenu(), 0, 100) . (strlen($message->getContenu()) > 100 ? '...' : '')
                    )
                );
            }
        }
    }

    /**
     * Envoyer une notification personnalisée
     */
    public function sendNotification(Participant $participant, string $title, string $message): void
    {
        $notification = (new Notification($title, ['email']))
            ->content($message);

        $this->notifier->send($notification, new Recipient($participant->getEmail()));
    }

    /**
     * Marquer tous les messages comme lus pour un participant
     */
    public function markAllAsRead(Participant $participant): void
    {
        $this->entityManager->createQueryBuilder()
            ->update('App:Message', 'm')
            ->set('m.lu', ':lu')
            ->where('m.expediteur != :participant')
            ->setParameter('lu', true)
            ->setParameter('participant', $participant)
            ->getQuery()
            ->execute();
    }

    /**
     * Obtenir le nombre de messages non lus pour un participant
     */
    public function getUnreadCount(Participant $participant): int
    {
        return $this->entityManager->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from('App:Message', 'm')
            ->where('m.expediteur != :participant')
            ->andWhere('m.lu = :lu')
            ->setParameter('participant', $participant)
            ->setParameter('lu', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
