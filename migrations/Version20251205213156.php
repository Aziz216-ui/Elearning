<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205213156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Rendre la migration idempotente: créer les tables et contraintes seulement si elles n'existent pas
        $createdPayment = false;
        $createdPlan = false;
        $createdPlanCours = false;
        $createdSubscription = false;

        if (!$schema->hasTable('payment')) {
            $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, subscription_id INT DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, currency VARCHAR(10) NOT NULL, INDEX IDX_6D28840DA76ED395 (user_id), INDEX IDX_6D28840D9A1887DC (subscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $createdPayment = true;
        }

        if (!$schema->hasTable('plan')) {
            $this->addSql('CREATE TABLE plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, duration VARCHAR(50) NOT NULL, is_active TINYINT(1) NOT NULL, max_courses INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $createdPlan = true;
        }

        if (!$schema->hasTable('subscription')) {
            $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, plan_id INT NOT NULL, status VARCHAR(50) NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, auto_renew TINYINT(1) NOT NULL, INDEX IDX_A3C664D3A76ED395 (user_id), INDEX IDX_A3C664D3E899029B (plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $createdSubscription = true;
        }

        if (!$schema->hasTable('plan_cours')) {
            $this->addSql('CREATE TABLE plan_cours (plan_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_AE6CC960E899029B (plan_id), INDEX IDX_AE6CC9607ECF78B0 (cours_id), PRIMARY KEY(plan_id, cours_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $createdPlanCours = true;
        }

        // Ajouter les clés étrangères uniquement si nous avons créé les tables correspondantes dans cette migration
        if ($createdPayment) {
            $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D9A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)');
        }
        if ($createdPlanCours) {
            $this->addSql('ALTER TABLE plan_cours ADD CONSTRAINT FK_AE6CC960E899029B FOREIGN KEY (plan_id) REFERENCES plan (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE plan_cours ADD CONSTRAINT FK_AE6CC9607ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        }
        if ($createdSubscription) {
            $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES plan (id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DA76ED395');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D9A1887DC');
        $this->addSql('ALTER TABLE plan_cours DROP FOREIGN KEY FK_AE6CC960E899029B');
        $this->addSql('ALTER TABLE plan_cours DROP FOREIGN KEY FK_AE6CC9607ECF78B0');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE plan');
        $this->addSql('DROP TABLE plan_cours');
        $this->addSql('DROP TABLE subscription');
    }
}
