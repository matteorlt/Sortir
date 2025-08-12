<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs.
 */
final class Version20250812100342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insertion des campus de base';
    }

    public function up(Schema $schema): void
    {
        // Insertion des campus de base
        $this->addSql("INSERT INTO campus (nom_campus) VALUES ('Rennes')");
        $this->addSql("INSERT INTO campus (nom_campus) VALUES ('Niort')");
        $this->addSql("INSERT INTO campus (nom_campus) VALUES ('Nantes')");
        $this->addSql("INSERT INTO campus (nom_campus) VALUES ('Quimper')");
    }

    public function down(Schema $schema): void
    {
        // Suppression des campus de base
        $this->addSql("DELETE FROM campus WHERE nom_campus IN ('Rennes', 'Niort', 'Nantes', 'Quimper')");
    }
} 