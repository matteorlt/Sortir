<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chat')]
class ChatController extends AbstractController
{
    #[Route('/', name: 'app_chat_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(MessageRepository $messageRepository): Response
    {
        // Récupérer les derniers messages
        $messages = $messageRepository->findRecentMessages(50);
        
        // Marquer tous les messages comme lus
        $messageRepository->markAllAsRead();

        return $this->render('chat/index.html.twig', [
            'messages' => array_reverse($messages), // Inverser pour afficher du plus ancien au plus récent
        ]);
    }

    #[Route('/messages', name: 'app_chat_messages', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMessages(MessageRepository $messageRepository): Response
    {
        $messages = $messageRepository->findRecentMessages(50);
        
        $data = [];
        foreach ($messages as $message) {
            $data[] = [
                'id' => $message->getId(),
                'content' => $message->getContenu(),
                'sender' => [
                    'id' => $message->getExpediteur()->getId(),
                    'prenom' => $message->getExpediteur()->getPrenom(),
                    'nom' => $message->getExpediteur()->getNom()
                ],
                'timestamp' => $message->getDateEnvoi()->format('Y-m-d H:i:s'),
                'isOwn' => $message->getExpediteur()->getId() === $this->getUser()->getId()
            ];
        }

        return $this->json($data);
    }

    #[Route('/unread-count', name: 'app_chat_unread_count', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getUnreadCount(MessageRepository $messageRepository): Response
    {
        $count = $messageRepository->findUnreadMessagesCount();
        return $this->json(['count' => $count]);
    }
}
