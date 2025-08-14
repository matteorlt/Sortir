<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\SortieRepository;
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
        // Récupérer les sorties auxquelles l'utilisateur est inscrit
        $user = $this->getUser();
        $inscriptions = $user->getInscritption();
        
        $sorties = [];
        foreach ($inscriptions as $inscription) {
            $sorties[] = $inscription->getSortie();
        }

        return $this->render('chat/index.html.twig', [
            'sorties' => $sorties,
        ]);
    }

    #[Route('/sortie/{id}', name: 'app_chat_sortie', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function chatSortie(int $id, MessageRepository $messageRepository, SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée');
        }

        // Vérifier que l'utilisateur est inscrit à cette sortie
        $user = $this->getUser();
        $isInscrit = false;
        
        foreach ($user->getInscritption() as $inscription) {
            if ($inscription->getSortie()->getId() === $id) {
                $isInscrit = true;
                break;
            }
        }

        if (!$isInscrit) {
            throw $this->createAccessDeniedException('Vous devez être inscrit à cette sortie pour accéder au chat');
        }

        // Récupérer les messages de cette sortie
        $messages = $messageRepository->findRecentMessagesBySortie($id, 50);
        
        // Marquer tous les messages comme lus
        $messageRepository->markAllAsRead();

        return $this->render('chat/sortie.html.twig', [
            'sortie' => $sortie,
            'messages' => array_reverse($messages),
        ]);
    }

    #[Route('/messages/sortie/{id}', name: 'app_chat_messages_sortie', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMessagesSortie(int $id, MessageRepository $messageRepository, SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée');
        }

        // Vérifier que l'utilisateur est inscrit à cette sortie
        $user = $this->getUser();
        $isInscrit = false;
        
        foreach ($user->getInscritption() as $inscription) {
            if ($inscription->getSortie()->getId() === $id) {
                $isInscrit = true;
                break;
            }
        }

        if (!$isInscrit) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        $messages = $messageRepository->findRecentMessagesBySortie($id, 50);
        
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

    #[Route('/unread-count/sortie/{id}', name: 'app_chat_unread_count_sortie', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getUnreadCountSortie(int $id, MessageRepository $messageRepository, SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée');
        }

        // Vérifier que l'utilisateur est inscrit à cette sortie
        $user = $this->getUser();
        $isInscrit = false;
        
        foreach ($user->getInscritption() as $inscription) {
            if ($inscription->getSortie()->getId() === $id) {
                $isInscrit = true;
                break;
            }
        }

        if (!$isInscrit) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        $count = $messageRepository->findUnreadMessagesCountBySortie($id);
        return $this->json(['count' => $count]);
    }
}
