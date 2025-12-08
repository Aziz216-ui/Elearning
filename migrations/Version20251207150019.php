<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207150019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Intentionally left blank: webhook_subscription schema already handled by later migrations.
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE webhook_subscription DROP FOREIGN KEY FK_97AE521C642B8210');
        $this->addSql('ALTER TABLE webhook_subscription DROP FOREIGN KEY FK_97AE521C642B8210');
        $this->addSql('ALTER TABLE webhook_subscription CHANGE event_type event_type VARCHAR(50) DEFAULT \'quiz_started\' NOT NULL, CHANGE failure_count failure_count INT DEFAULT 0');
        $this->addSql('DROP INDEX idx_97ae521c642b8210 ON webhook_subscription');
        $this->addSql('CREATE INDEX FK_WEBHOOK_ADMIN ON webhook_subscription (admin_id)');
        $this->addSql('ALTER TABLE webhook_subscription ADD CONSTRAINT FK_97AE521C642B8210 FOREIGN KEY (admin_id) REFERENCES `user` (id)');
    }
}
