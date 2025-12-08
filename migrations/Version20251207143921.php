<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207143921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bouthaina (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, admin_id INT NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_BF5476CA642B8210 (admin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA642B8210 FOREIGN KEY (admin_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE question DROP image');
        $this->addSql('ALTER TABLE webhook_subscription DROP FOREIGN KEY FK_WEBHOOK_ADMIN');
        $this->addSql('DROP INDEX idx_event_type ON webhook_subscription');
        $this->addSql('DROP INDEX idx_is_active ON webhook_subscription');
        $this->addSql('ALTER TABLE webhook_subscription DROP FOREIGN KEY FK_WEBHOOK_ADMIN');
        $this->addSql('ALTER TABLE webhook_subscription CHANGE event_type event_type VARCHAR(50) NOT NULL, CHANGE failure_count failure_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE webhook_subscription ADD CONSTRAINT FK_97AE521C642B8210 FOREIGN KEY (admin_id) REFERENCES `user` (id)');
        $this->addSql('DROP INDEX fk_webhook_admin ON webhook_subscription');
        $this->addSql('CREATE INDEX IDX_97AE521C642B8210 ON webhook_subscription (admin_id)');
        $this->addSql('ALTER TABLE webhook_subscription ADD CONSTRAINT FK_WEBHOOK_ADMIN FOREIGN KEY (admin_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA642B8210');
        $this->addSql('DROP TABLE bouthaina');
        $this->addSql('DROP TABLE notification');
        $this->addSql('ALTER TABLE question ADD image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE webhook_subscription DROP FOREIGN KEY FK_97AE521C642B8210');
        $this->addSql('ALTER TABLE webhook_subscription DROP FOREIGN KEY FK_97AE521C642B8210');
        $this->addSql('ALTER TABLE webhook_subscription CHANGE event_type event_type VARCHAR(50) DEFAULT \'quiz_started\' NOT NULL, CHANGE failure_count failure_count INT DEFAULT 0');
        $this->addSql('ALTER TABLE webhook_subscription ADD CONSTRAINT FK_WEBHOOK_ADMIN FOREIGN KEY (admin_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_event_type ON webhook_subscription (event_type)');
        $this->addSql('CREATE INDEX idx_is_active ON webhook_subscription (is_active)');
        $this->addSql('DROP INDEX idx_97ae521c642b8210 ON webhook_subscription');
        $this->addSql('CREATE INDEX FK_WEBHOOK_ADMIN ON webhook_subscription (admin_id)');
        $this->addSql('ALTER TABLE webhook_subscription ADD CONSTRAINT FK_97AE521C642B8210 FOREIGN KEY (admin_id) REFERENCES `user` (id)');
    }
}
