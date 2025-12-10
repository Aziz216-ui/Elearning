<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203144227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Les tables auteur / cours / messenger_messages existent déjà dans la base.
        // On rend donc cette migration neutre (no-op) pour éviter les erreurs
        // de doublon lors de la création des tables ou des clés étrangères.
        // Intentionnellement : aucun SQL exécuté ici.
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9C60BB6FE6');
        $this->addSql('DROP TABLE auteur');
        $this->addSql('DROP TABLE cours');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
