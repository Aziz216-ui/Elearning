<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208004600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');

        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY IF EXISTS FK_DADD4A251E27F6BF');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY IF EXISTS FK_6D28840DA76ED395');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY IF EXISTS FK_6D28840D9A1887DC');
        $this->addSql('ALTER TABLE plan_cours DROP FOREIGN KEY IF EXISTS FK_AE6CC960E899029B');
        $this->addSql('ALTER TABLE plan_cours DROP FOREIGN KEY IF EXISTS FK_AE6CC9607ECF78B0');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY IF EXISTS FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY IF EXISTS FK_A412FA927ECF78B0');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY IF EXISTS FK_FE2E314AA76ED395');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY IF EXISTS FK_FE2E314A853CD175');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY IF EXISTS FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY IF EXISTS FK_A3C664D3E899029B');
        $this->addSql('ALTER TABLE user_answer DROP FOREIGN KEY IF EXISTS FK_BF8F5118A76ED395');
        $this->addSql('ALTER TABLE user_answer DROP FOREIGN KEY IF EXISTS FK_BF8F51181E27F6BF');
        $this->addSql('ALTER TABLE user_answer DROP FOREIGN KEY IF EXISTS FK_BF8F5118AA334807');

        $this->addSql('DROP TABLE IF EXISTS answer');
        $this->addSql('DROP TABLE IF EXISTS payment');
        $this->addSql('DROP TABLE IF EXISTS plan');
        $this->addSql('DROP TABLE IF EXISTS plan_cours');
        $this->addSql('DROP TABLE IF EXISTS question');
        $this->addSql('DROP TABLE IF EXISTS quiz');
        $this->addSql('DROP TABLE IF EXISTS quiz_result');
        $this->addSql('DROP TABLE IF EXISTS subscription');
        $this->addSql('DROP TABLE IF EXISTS user_answer');

        $this->addSql('CREATE TABLE plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, duration VARCHAR(50) NOT NULL, is_active TINYINT(1) NOT NULL, max_courses INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quiz (id INT AUTO_INCREMENT NOT NULL, cours_id INT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(500) NOT NULL, total_points INT DEFAULT NULL, time_limit INT DEFAULT NULL, is_published TINYINT(1) NOT NULL, is_visible TINYINT(1) NOT NULL, INDEX IDX_A412FA927ECF78B0 (cours_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, quiz_id INT NOT NULL, text VARCHAR(255) NOT NULL, type VARCHAR(255) DEFAULT NULL, points INT NOT NULL, INDEX IDX_B6F7494E853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE answer (id INT AUTO_INCREMENT NOT NULL, question_id INT DEFAULT NULL, text VARCHAR(255) NOT NULL, is_correct TINYINT(1) NOT NULL, INDEX IDX_DADD4A251E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, plan_id INT NOT NULL, status VARCHAR(50) NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, auto_renew TINYINT(1) NOT NULL, INDEX IDX_A3C664D3A76ED395 (user_id), INDEX IDX_A3C664D3E899029B (plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, subscription_id INT DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, currency VARCHAR(10) NOT NULL, INDEX IDX_6D28840DA76ED395 (user_id), INDEX IDX_6D28840D9A1887DC (subscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plan_cours (plan_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_AE6CC960E899029B (plan_id), INDEX IDX_AE6CC9607ECF78B0 (cours_id), PRIMARY KEY(plan_id, cours_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quiz_result (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, quiz_id INT DEFAULT NULL, score DOUBLE PRECISION NOT NULL, completed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', certificate_path VARCHAR(255) DEFAULT NULL, total_points INT DEFAULT NULL, passed TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_FE2E314AA76ED395 (user_id), INDEX IDX_FE2E314A853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_answer (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, question_id INT NOT NULL, answer_id INT DEFAULT NULL, is_correct TINYINT(1) NOT NULL, answered_at DATETIME NOT NULL, INDEX IDX_BF8F5118A76ED395 (user_id), INDEX IDX_BF8F51181E27F6BF (question_id), INDEX IDX_BF8F5118AA334807 (answer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A251E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D9A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)');
        $this->addSql('ALTER TABLE plan_cours ADD CONSTRAINT FK_AE6CC960E899029B FOREIGN KEY (plan_id) REFERENCES plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_cours ADD CONSTRAINT FK_AE6CC9607ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA927ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT FK_FE2E314AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT FK_FE2E314A853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES plan (id)');
        $this->addSql('ALTER TABLE user_answer ADD CONSTRAINT FK_BF8F5118A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_answer ADD CONSTRAINT FK_BF8F51181E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE user_answer ADD CONSTRAINT FK_BF8F5118AA334807 FOREIGN KEY (answer_id) REFERENCES answer (id)');
        $this->addSql('ALTER TABLE cours CHANGE description description LONGTEXT NOT NULL, CHANGE duration duration DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        // $this->addSql('ALTER TABLE panier ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');

        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY IF EXISTS FK_DADD4A251E27F6BF');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY IF EXISTS FK_6D28840DA76ED395');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY IF EXISTS FK_6D28840D9A1887DC');
        $this->addSql('ALTER TABLE plan_cours DROP FOREIGN KEY IF EXISTS FK_AE6CC960E899029B');
        $this->addSql('ALTER TABLE plan_cours DROP FOREIGN KEY IF EXISTS FK_AE6CC9607ECF78B0');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY IF EXISTS FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY IF EXISTS FK_A412FA927ECF78B0');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY IF EXISTS FK_FE2E314AA76ED395');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY IF EXISTS FK_FE2E314A853CD175');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY IF EXISTS FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY IF EXISTS FK_A3C664D3E899029B');
        $this->addSql('ALTER TABLE user_answer DROP FOREIGN KEY IF EXISTS FK_BF8F5118A76ED395');
        $this->addSql('ALTER TABLE user_answer DROP FOREIGN KEY IF EXISTS FK_BF8F51181E27F6BF');
        $this->addSql('ALTER TABLE user_answer DROP FOREIGN KEY IF EXISTS FK_BF8F5118AA334807');
        $this->addSql('DROP TABLE IF EXISTS answer');
        $this->addSql('DROP TABLE IF EXISTS payment');
        $this->addSql('DROP TABLE IF EXISTS plan');
        $this->addSql('DROP TABLE IF EXISTS plan_cours');
        $this->addSql('DROP TABLE IF EXISTS question');
        $this->addSql('DROP TABLE IF EXISTS quiz');
        $this->addSql('DROP TABLE IF EXISTS quiz_result');
        $this->addSql('DROP TABLE IF EXISTS subscription');
        $this->addSql('DROP TABLE IF EXISTS user_answer');
        $this->addSql('ALTER TABLE cours ADD user_id INT NOT NULL, CHANGE description description VARCHAR(255) NOT NULL, CHANGE duration duration DATETIME NOT NULL');
        // $this->addSql('ALTER TABLE panier DROP created_at');

        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }
}
