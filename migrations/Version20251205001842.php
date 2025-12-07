<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205001842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE plan_cours (plan_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_AE6CC960E899029B (plan_id), INDEX IDX_AE6CC9607ECF78B0 (cours_id), PRIMARY KEY(plan_id, cours_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE plan_cours ADD CONSTRAINT FK_AE6CC960E899029B FOREIGN KEY (plan_id) REFERENCES plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_cours ADD CONSTRAINT FK_AE6CC9607ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE plan_cours DROP FOREIGN KEY FK_AE6CC960E899029B');
        $this->addSql('ALTER TABLE plan_cours DROP FOREIGN KEY FK_AE6CC9607ECF78B0');
        $this->addSql('DROP TABLE plan_cours');
    }
}
