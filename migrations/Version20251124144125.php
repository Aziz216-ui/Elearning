<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124144125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forul_comment ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE forul_comment ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE forum_post CHANGE vues vues INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forul_comment DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE forum_post CHANGE vues vues INT DEFAULT NULL');
    }
}
