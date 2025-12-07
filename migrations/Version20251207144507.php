<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207144507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bouthaina (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE webhook_subscription CHANGE event_type event_type VARCHAR(50) NOT NULL, CHANGE failure_count failure_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE webhook_subscription ADD CONSTRAINT FK_97AE521C642B8210 FOREIGN KEY (admin_id) REFERENCES `user` (id)');
        $this->addSql('DROP INDEX fk_webhook_admin ON webhook_subscription');
        $this->addSql('CREATE INDEX IDX_97AE521C642B8210 ON webhook_subscription (admin_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE bouthaina');
        $this->addSql('ALTER TABLE webhook_subscription DROP FOREIGN KEY FK_97AE521C642B8210');
        $this->addSql('ALTER TABLE webhook_subscription DROP FOREIGN KEY FK_97AE521C642B8210');
        $this->addSql('ALTER TABLE webhook_subscription CHANGE event_type event_type VARCHAR(50) DEFAULT \'quiz_started\' NOT NULL, CHANGE failure_count failure_count INT DEFAULT 0');
        $this->addSql('DROP INDEX idx_97ae521c642b8210 ON webhook_subscription');
        $this->addSql('CREATE INDEX FK_WEBHOOK_ADMIN ON webhook_subscription (admin_id)');
        $this->addSql('ALTER TABLE webhook_subscription ADD CONSTRAINT FK_97AE521C642B8210 FOREIGN KEY (admin_id) REFERENCES `user` (id)');
    }
}
