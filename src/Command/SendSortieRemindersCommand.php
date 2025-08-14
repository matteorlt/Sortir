<?php

namespace App\Command;

use App\Entity\Sortie;
use App\Repository\SortieRepository;
use App\Service\SortieEmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-sortie-reminders',
    description: 'Envoie les rappels 48h avant les sorties',
)]
class SendSortieRemindersCommand extends Command
{
    public function __construct(
        private readonly SortieRepository $sortieRepository,
        private readonly SortieEmailService $emailService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Envoi des rappels de sorties');

        // Trouver les sorties dans 48h
        $date48h = new \DateTime('+48 hours');
        $sorties = $this->sortieRepository->findSortiesIn48h($date48h);

        if (empty($sorties)) {
            $io->info('Aucune sortie dans 48h à rappeler.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Trouvé %d sortie(s) à rappeler.', count($sorties)));

        $emailsSent = 0;
        foreach ($sorties as $sortie) {
            try {
                $this->emailService->sendRappel48h($sortie);
                $emailsSent += count($sortie->getInscriptions());
                
                $io->text(sprintf(
                    '✅ Rappels envoyés pour "%s" (%d participants)',
                    $sortie->getNom(),
                    count($sortie->getInscriptions())
                ));
            } catch (\Exception $e) {
                $io->error(sprintf(
                    '❌ Erreur lors de l\'envoi des rappels pour "%s": %s',
                    $sortie->getNom(),
                    $e->getMessage()
                ));
            }
        }

        $io->success(sprintf('Rappels envoyés avec succès : %d emails', $emailsSent));

        return Command::SUCCESS;
    }
}
