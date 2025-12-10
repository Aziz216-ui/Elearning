<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Re-add started_at column on quiz_result to match the QuizResult entity.
 */
final class Version20251208221000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recreate quiz_result.started_at (datetime_immutable, nullable) after it was dropped.';
    }

    public function up(Schema $schema): void
    {
        // Add the column only if it does not already exist
        // Doctrine migrations SQL is not conditional, so we just re-add it; if you rerun on a fresh DB, it will align.
        $this->addSql("ALTER TABLE quiz_result ADD started_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz_result DROP started_at');
    }
}
