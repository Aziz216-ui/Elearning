<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208185616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Intentionally left blank: subscription.cours_id already exists and FK/index are managed by a later migration.
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D37ECF78B0');
        $this->addSql('DROP INDEX IDX_A3C664D37ECF78B0 ON subscription');
        $this->addSql('ALTER TABLE subscription DROP cours_id, CHANGE plan_id plan_id INT NOT NULL');
    }
}
