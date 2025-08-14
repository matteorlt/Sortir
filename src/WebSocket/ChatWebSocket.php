<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Entity\Message;
use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ChatWebSocket implements MessageComponentInterface
{
    protected $clients;
    protected $entityManager;
    protected $serializer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->clients = new \SplObjectStorage;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Stocke la nouvelle connexion
        $this->clients->attach($conn);
        echo "Nouvelle connexion! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (!$data) {
            return;
        }

        switch ($data['type']) {
            case 'join_sortie':
                $this->handleJoinSortie($from, $data);
                break;
            case 'message':
                $this->handleNewMessage($from, $data);
                break;
            case 'typing':
                $this->broadcastTyping($from, $data);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // La connexion est fermée, on la retire
        $this->clients->detach($conn);
        echo "Connexion {$conn->resourceId} fermée\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Erreur: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function handleNewMessage(ConnectionInterface $from, array $data)
    {
        // Créer et sauvegarder le message en base
        $message = new Message();
        $message->setContenu($data['content']);
        
        // Récupérer l'expéditeur et la sortie
        $participant = $this->entityManager->getRepository(Participant::class)->find($data['userId']);
        $sortie = $this->entityManager->getRepository(\App\Entity\Sortie::class)->find($data['sortieId']);
        
        if ($participant && $sortie) {
            $message->setExpediteur($participant);
            $message->setSortie($sortie);
            $this->entityManager->persist($message);
            $this->entityManager->flush();

            // Préparer le message à diffuser
            $messageData = [
                'type' => 'message',
                'id' => $message->getId(),
                'content' => $message->getContenu(),
                'sortieId' => $sortie->getId(),
                'sender' => [
                    'id' => $participant->getId(),
                    'prenom' => $participant->getPrenom(),
                    'nom' => $participant->getNom()
                ],
                'timestamp' => $message->getDateEnvoi()->format('Y-m-d H:i:s')
            ];

            // Diffuser le message à tous les clients connectés à cette sortie
            $this->broadcastToSortie($messageData, $sortie->getId());
        }
    }

    protected function broadcastTyping(ConnectionInterface $from, array $data)
    {
        $typingData = [
            'type' => 'typing',
            'userId' => $data['userId'],
            'isTyping' => $data['isTyping']
        ];

        foreach ($this->clients as $client) {
            if ($client !== $from) {
                $client->send(json_encode($typingData));
            }
        }
    }

    protected function handleJoinSortie(ConnectionInterface $from, array $data)
    {
        // Associer le client à une sortie spécifique
        $from->sortieId = $data['sortieId'];
        echo "Client {$from->resourceId} rejoint la sortie {$data['sortieId']}\n";
    }

    protected function broadcastToSortie($data, $sortieId)
    {
        // Diffuser le message seulement aux clients connectés à cette sortie
        foreach ($this->clients as $client) {
            if (isset($client->sortieId) && $client->sortieId == $sortieId) {
                $client->send(json_encode($data));
            }
        }
    }

    protected function broadcast($data)
    {
        foreach ($this->clients as $client) {
            $client->send(json_encode($data));
        }
    }
}
