<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207112313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY FK_DADD4A251E27F6BF');
        $this->addSql('ALTER TABLE forul_comment DROP FOREIGN KEY FK_72C30B5E4B89032C');
        $this->addSql('ALTER TABLE forul_comment DROP FOREIGN KEY FK_72C30B5EA76ED395');
        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY FK_996BCC5AA76ED395');
        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY FK_996BCC5A12469DE2');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF27ECF78B0');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2A76ED395');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D9A1887DC');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DA76ED395');
        $this->addSql('ALTER TABLE plan_cours DROP FOREIGN KEY FK_AE6CC960E899029B');
        $this->addSql('ALTER TABLE plan_cours DROP FOREIGN KEY FK_AE6CC9607ECF78B0');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA927ECF78B0');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY FK_FE2E314A853CD175');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY FK_FE2E314AA76ED395');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE user_answer DROP FOREIGN KEY FK_BF8F5118A76ED395');
        $this->addSql('ALTER TABLE user_answer DROP FOREIGN KEY FK_BF8F5118AA334807');
        $this->addSql('ALTER TABLE user_answer DROP FOREIGN KEY FK_BF8F51181E27F6BF');
        $this->addSql('DROP TABLE answer');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE forul_comment');
        $this->addSql('DROP TABLE forum_post');
        $this->addSql('DROP TABLE panier');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE plan');
        $this->addSql('DROP TABLE plan_cours');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE quiz_result');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_answer');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE answer (id INT AUTO_INCREMENT NOT NULL, question_id INT DEFAULT NULL, text VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_correct TINYINT(1) NOT NULL, INDEX IDX_DADD4A251E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, slug VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, couleur VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, icone VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_64C19C1989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE forul_comment (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, user_id INT NOT NULL, contenu VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_72C30B5E4B89032C (post_id), INDEX IDX_72C30B5EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE forum_post (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, category_id INT DEFAULT NULL, titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, contenu LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', enabled TINYINT(1) NOT NULL, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, likes INT DEFAULT NULL, vues INT DEFAULT 0 NOT NULL, INDEX IDX_996BCC5AA76ED395 (user_id), INDEX IDX_996BCC5A12469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE panier (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, cours_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_24CC0DF27ECF78B0 (cours_id), INDEX IDX_24CC0DF2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, subscription_id INT DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, currency VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_6D28840DA76ED395 (user_id), INDEX IDX_6D28840D9A1887DC (subscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, price NUMERIC(10, 2) NOT NULL, duration VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_active TINYINT(1) NOT NULL, max_courses INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE plan_cours (plan_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_AE6CC960E899029B (plan_id), INDEX IDX_AE6CC9607ECF78B0 (cours_id), PRIMARY KEY(plan_id, cours_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, quiz_id INT NOT NULL, text VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, points INT NOT NULL, INDEX IDX_B6F7494E853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE quiz (id INT AUTO_INCREMENT NOT NULL, cours_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, total_points INT DEFAULT NULL, time_limit INT DEFAULT NULL, is_published TINYINT(1) NOT NULL, is_visible TINYINT(1) NOT NULL, INDEX IDX_A412FA927ECF78B0 (cours_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE quiz_result (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, quiz_id INT DEFAULT NULL, score DOUBLE PRECISION NOT NULL, completed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', certificate_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, total_points INT DEFAULT NULL, passed TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_FE2E314AA76ED395 (user_id), INDEX IDX_FE2E314A853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, plan_id INT NOT NULL, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, auto_renew TINYINT(1) NOT NULL, INDEX IDX_A3C664D3A76ED395 (user_id), INDEX IDX_A3C664D3E899029B (plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, lastname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_verified TINYINT(1) NOT NULL, birthdate DATE DEFAULT NULL, sexe VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_answer (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, question_id INT NOT NULL, answer_id INT DEFAULT NULL, is_correct TINYINT(1) NOT NULL, answered_at DATETIME NOT NULL, INDEX IDX_BF8F51181E27F6BF (question_id), INDEX IDX_BF8F5118AA334807 (answer_id), INDEX IDX_BF8F5118A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A251E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE forul_comment ADD CONSTRAINT FK_72C30B5E4B89032C FOREIGN KEY (post_id) REFERENCES forum_post (id)');
        $this->addSql('ALTER TABLE forul_comment ADD CONSTRAINT FK_72C30B5EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT FK_996BCC5AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT FK_996BCC5A12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF27ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D9A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE plan_cours ADD CONSTRAINT FK_AE6CC960E899029B FOREIGN KEY (plan_id) REFERENCES plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_cours ADD CONSTRAINT FK_AE6CC9607ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA927ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT FK_FE2E314A853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT FK_FE2E314AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES plan (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_answer ADD CONSTRAINT FK_BF8F5118A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_answer ADD CONSTRAINT FK_BF8F5118AA334807 FOREIGN KEY (answer_id) REFERENCES answer (id)');
        $this->addSql('ALTER TABLE user_answer ADD CONSTRAINT FK_BF8F51181E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
    }
}
