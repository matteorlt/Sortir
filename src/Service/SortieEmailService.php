<?php

namespace App\Service;

use App\Entity\Inscription;
use App\Entity\Sortie;
use App\Entity\Participant;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SortieEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * Envoie un email de confirmation d'inscription à une sortie
     */
    public function sendInscriptionConfirmation(Inscription $inscription): void
    {
        $sortie = $inscription->getSortie();
        $participant = $inscription->getParticipant();

        $message = (new Email())
            ->from('no-reply@sortir.fr')
            ->to($participant->getMail())
            ->subject('Confirmation d\'inscription - ' . $sortie->getNomSortie())
            ->html($this->renderInscriptionEmail($sortie, $participant, 'confirmation'));

        $this->mailer->send($message);
    }

    /**
     * Envoie un email de confirmation de désistement
     */
    public function sendDesistementConfirmation(Inscription $inscription): void
    {
        $sortie = $inscription->getSortie();
        $participant = $inscription->getParticipant();

        $message = (new Email())
            ->from('no-reply@sortir.fr')
            ->to($participant->getMail())
            ->subject('Confirmation de désistement - ' . $sortie->getNomSortie())
            ->html($this->renderInscriptionEmail($sortie, $participant, 'desistement'));

        $this->mailer->send($message);
    }

    /**
     * Envoie un email de rappel 48h avant la sortie
     */
    public function sendRappel48h(Sortie $sortie): void
    {
        foreach ($sortie->getInscriptions() as $inscription) {
            $participant = $inscription->getParticipant();
            
            $message = (new Email())
                ->from('no-reply@sortir.fr')
                ->to($participant->getMail())
                ->subject('Rappel - Sortie dans 48h : ' . $sortie->getNomSortie())
                ->html($this->renderRappelEmail($sortie, $participant));

            $this->mailer->send($message);
        }
    }

    /**
     * Rend le template d'email pour inscription/désistement
     */
    private function renderInscriptionEmail(Sortie $sortie, Participant $participant, string $type): string
    {
        $action = $type === 'confirmation' ? 'inscrit' : 'désinscrit';
        $actionPast = $type === 'confirmation' ? 'inscrit' : 'désinscrit';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Confirmation {$action}</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h1 style='color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;'>
                    Confirmation de {$action}
                </h1>
                
                <p>Bonjour {$participant->getPrenom()},</p>
                
                <p>Vous vous êtes {$actionPast} à la sortie suivante :</p>
                
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>{$sortie->getNomSortie()}</h3>
                    <p><strong>Date :</strong> {$sortie->getDateDebut()->format('d/m/Y à H:i')}</p>
                    <p><strong>Lieu :</strong> {$sortie->getLieu()->getNomLieu()}</p>
                    <p><strong>Durée :</strong> {$sortie->getDuree()} minutes</p>
                    <p><strong>Description :</strong> {$sortie->getDescriptionInfos()}</p>
                </div>
                
                <p>Merci de votre participation !</p>
                
                <p>Cordialement,<br>L'équipe Sortir</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Rend le template d'email de rappel
     */
    private function renderRappelEmail(Sortie $sortie, Participant $participant): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Rappel sortie</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h1 style='color: #e74c3c; border-bottom: 2px solid #e74c3c; padding-bottom: 10px;'>
                    ⏰ Rappel - Sortie dans 48h !
                </h1>
                
                <p>Bonjour {$participant->getPrenom()},</p>
                
                <p>N'oubliez pas que vous participez à cette sortie dans 48h :</p>
                
                <div style='background-color: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <h3 style='color: #856404; margin-top: 0;'>{$sortie->getNomSortie()}</h3>
                    <p><strong>Date :</strong> {$sortie->getDateDebut()->format('d/m/Y à H:i')}</p>
                    <p><strong>Lieu :</strong> {$sortie->getLieu()->getNomLieu()}</p>
                    <p><strong>Durée :</strong> {$sortie->getDuree()} minutes</p>
                    <p><strong>Description :</strong> {$sortie->getDescriptionInfos()}</p>
                </div>
                
                <p>Préparez-vous et profitez bien de votre sortie !</p>
                
                <p>Cordialement,<br>L'équipe Sortir</p>
            </div>
        </body>
        </html>";
    }
}
