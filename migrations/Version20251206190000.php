<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add quiz session tracking and webhook subscription system';
    }

    public function up(Schema $schema): void
    {
        // Add started_at column to quiz_result table
        $this->addSql('ALTER TABLE quiz_result ADD COLUMN started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // Create webhook_subscription table
        $this->addSql('CREATE TABLE webhook_subscription (
            id INT AUTO_INCREMENT NOT NULL,
            admin_id INT NOT NULL,
            url VARCHAR(500) NOT NULL,
            event_type VARCHAR(50) NOT NULL DEFAULT \'quiz_started\',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            last_triggered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            failure_count INT DEFAULT 0,
            secret VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id),
            CONSTRAINT FK_WEBHOOK_ADMIN FOREIGN KEY (admin_id) REFERENCES user(id) ON DELETE CASCADE,
            INDEX idx_event_type (event_type),
            INDEX idx_is_active (is_active)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS webhook_subscription');
        $this->addSql('ALTER TABLE quiz_result DROP COLUMN started_at');
    }
}
