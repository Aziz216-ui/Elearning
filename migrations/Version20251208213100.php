<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Recreate notification table after it was dropped by Version20251208200017,
 * to match the Notification entity currently used by the application.
 */
final class Version20251208213100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recreate notification table (id, admin_id, title, message, type, is_read, created_at, data) with FK to user.';
    }

    public function up(Schema $schema): void
    {
        // Recreate notification table if it does not exist
        $this->addSql("CREATE TABLE IF NOT EXISTS notification (
            id INT AUTO_INCREMENT NOT NULL,
            admin_id INT NOT NULL,
            title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
            message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
            type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
            is_read TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            data JSON DEFAULT NULL COMMENT '(DC2Type:json)',
            INDEX IDX_BF5476CA642B8210 (admin_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''");

        // Add FK constraint (will be ignored by MySQL if it already exists)
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA642B8210 FOREIGN KEY (admin_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // Drop notification table if we rollback
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA642B8210');
        $this->addSql('DROP TABLE notification');
    }
}
