<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114125342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE auteur DROP FOREIGN KEY FK_55AB1407ECF78B0');
        $this->addSql('DROP INDEX IDX_55AB1407ECF78B0 ON auteur');
        $this->addSql('ALTER TABLE auteur DROP cours_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE auteur ADD cours_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE auteur ADD CONSTRAINT FK_55AB1407ECF78B0 FOREIGN KEY (cours_id) REFERENCES auteur (id)');
        $this->addSql('CREATE INDEX IDX_55AB1407ECF78B0 ON auteur (cours_id)');
    }
}
