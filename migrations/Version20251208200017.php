<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208200017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA642B8210');
        $this->addSql('ALTER TABLE user_favorite_cours DROP FOREIGN KEY FK_20C778E17ECF78B0');
        $this->addSql('ALTER TABLE user_favorite_cours DROP FOREIGN KEY FK_20C778E1A76ED395');
        $this->addSql('ALTER TABLE webhook_subscription DROP FOREIGN KEY FK_97AE521C642B8210');
        $this->addSql('DROP TABLE bouthaina');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE user_favorite_cours');
        $this->addSql('DROP TABLE webhook_subscription');
        $this->addSql('ALTER TABLE quiz_result DROP started_at');
        $this->addSql('ALTER TABLE subscription ADD cours_id INT DEFAULT NULL, CHANGE plan_id plan_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D37ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('CREATE INDEX IDX_A3C664D37ECF78B0 ON subscription (cours_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bouthaina (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, admin_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_BF5476CA642B8210 (admin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_favorite_cours (user_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_20C778E17ECF78B0 (cours_id), INDEX IDX_20C778E1A76ED395 (user_id), PRIMARY KEY(user_id, cours_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE webhook_subscription (id INT AUTO_INCREMENT NOT NULL, admin_id INT NOT NULL, url VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, event_type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_triggered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', failure_count INT DEFAULT NULL, secret VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX FK_97AE521C642B8210 (admin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA642B8210 FOREIGN KEY (admin_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_favorite_cours ADD CONSTRAINT FK_20C778E17ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorite_cours ADD CONSTRAINT FK_20C778E1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE webhook_subscription ADD CONSTRAINT FK_97AE521C642B8210 FOREIGN KEY (admin_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE quiz_result ADD started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D37ECF78B0');
        $this->addSql('DROP INDEX IDX_A3C664D37ECF78B0 ON subscription');
        $this->addSql('ALTER TABLE subscription DROP cours_id, CHANGE plan_id plan_id INT NOT NULL');
    }
}
