<?php

namespace App\Service\Importer;

use App\Entity\Participant;
use App\Entity\Campus;
use App\Repository\CampusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ParticipantCsvImporter
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CampusRepository $campusRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array{inserted:int, skipped:int, errors:int}
     */
    public function import(string $filepath, string $separator = ';'): array
    {
        if (!is_readable($filepath)) {
            throw new \RuntimeException('Fichier CSV illisible.');
        }

        $inserted = 0;
        $skipped = 0;
        $errors = 0;

        $handle = fopen($filepath, 'rb');
        if (false === $handle) {
            throw new \RuntimeException('Impossible d’ouvrir le fichier CSV.');
        }

        $headers = null;
        $batchSize = 50;
        $processed = 0;

        $this->em->beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, $separator)) !== false) {
                if ($row === [null] || $row === [] || (count($row) === 1 && trim((string)$row[0]) === '')) {
                    continue;
                }

                if ($headers === null) {
                    $headers = array_map(static fn($h) => trim((string)$h), $row);
                    continue;
                }

                $data = [];
                foreach ($headers as $i => $key) {
                    $data[$key] = $row[$i] ?? null;
                }

                $pseudo = trim((string)($data['pseudo'] ?? ''));
                $nom = trim((string)($data['nom'] ?? ''));
                $prenom = trim((string)($data['prenom'] ?? ''));
                $telephone = trim((string)($data['telephone'] ?? ''));
                $mail = strtolower(trim((string)($data['mail'] ?? '')));
                $plainPassword = (string)($data['plainPassword'] ?? '');
                $actifRaw = (string)($data['actif'] ?? '1');
                $campusName = trim((string)($data['campus'] ?? ''));

                if ($pseudo === '' || $nom === '' || $prenom === '' || $mail === '' || $plainPassword === '') {
                    $errors++;
                    continue;
                }

                $exists = $this->em->getRepository(Participant::class)->findOneBy(['mail' => $mail]);
                if ($exists) {
                    $skipped++;
                    continue;
                }

                $campus = null;
                if ($campusName !== '') {
                    $campus = $this->campusRepository->findOneBy(['nomCampus' => $campusName]);
                    if (!$campus instanceof Campus) {
                        $errors++;
                        continue;
                    }
                }

                $participant = new Participant();
                $participant->setPseudo($pseudo);
                $participant->setNom($nom);
                $participant->setPrenom($prenom);
                if ($telephone !== '') {
                    $participant->setTelephone($telephone);
                }
                $participant->setMail($mail);

                $hashed = $this->passwordHasher->hashPassword($participant, $plainPassword);
                $participant->setMotDePasse($hashed);

                $actif = in_array(strtolower($actifRaw), ['1', 'true', 'oui', 'yes'], true);
                $participant->setActif($actif);

                if ($campus) {
                    $participant->setCampus($campus);
                }

                $this->em->persist($participant);
                $inserted++;
                $processed++;

                if ($processed % $batchSize === 0) {
                    $this->em->flush();
                    $this->em->clear();
                }
            }

            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            $this->logger->error('Erreur import CSV Participants: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        } finally {
            fclose($handle);
        }

        return compact('inserted', 'skipped', 'errors');
    }
}