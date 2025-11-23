<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251123165909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Les tables sont déjà créées par Version20251119125328
        // Cette migration est laissée vide intentionnellement pour éviter les doublons
    }

    public function down(Schema $schema): void
    {
        // Ne rien supprimer car les tables sont gérées par Version20251119125328
    }
}
