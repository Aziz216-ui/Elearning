<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add WebhookSubscription table and startedAt column to QuizResult';
    }

    public function up(Schema $schema): void
    {
        // Add startedAt column to quiz_result table
        $table = $schema->getTable('quiz_result');
        if (!$table->hasColumn('started_at')) {
            $table->addColumn('started_at', 'datetime_immutable', [
                'notnull' => false,
            ]);
        }

        // Create webhook_subscription table
        if (!$schema->hasTable('webhook_subscription')) {
            $webhookTable = $schema->createTable('webhook_subscription');
            $webhookTable->addColumn('id', 'integer', ['autoincrement' => true]);
            $webhookTable->addColumn('admin_id', 'integer', ['notnull' => false]);
            $webhookTable->addColumn('url', 'string', ['length' => 500]);
            $webhookTable->addColumn('event_type', 'string', ['length' => 50]);
            $webhookTable->addColumn('is_active', 'boolean', ['default' => true]);
            $webhookTable->addColumn('created_at', 'datetime_immutable');
            $webhookTable->addColumn('last_triggered_at', 'datetime_immutable', ['notnull' => false]);
            $webhookTable->addColumn('failure_count', 'integer', ['notnull' => false, 'default' => 0]);
            $webhookTable->addColumn('secret', 'string', ['length' => 255, 'notnull' => false]);
            
            $webhookTable->setPrimaryKey(['id']);
            $webhookTable->addForeignKeyConstraint('user', ['admin_id'], ['id'], [
                'onDelete' => 'CASCADE',
                'onUpdate' => 'CASCADE',
            ]);
            $webhookTable->addIndex(['admin_id']);
            $webhookTable->addIndex(['is_active']);
            $webhookTable->addIndex(['event_type']);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('quiz_result');
        if ($table->hasColumn('started_at')) {
            $table->dropColumn('started_at');
        }

        if ($schema->hasTable('webhook_subscription')) {
            $schema->dropTable('webhook_subscription');
        }
    }
}
