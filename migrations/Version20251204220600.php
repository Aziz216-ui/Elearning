<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204220600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    /**
     * Check if a column exists on a table (MySQL).
     */
    private function columnExists(string $table, string $column): bool
    {
        $dbName = $this->connection->getDatabase();
        $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
        return (int) $this->connection->fetchOne($sql, [$dbName, $table, $column]) > 0;
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if (!$this->columnExists('panier', 'created_at')) {
            $this->addSql('ALTER TABLE panier ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE panier DROP created_at');
    }
}
