<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Additional performance indexes on created_at columns for purge queries and on admin_messages banner lookup.
 */
final class Version20250618102500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at indexes on problems, private_problems, feedback and admin_messages tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_ADMIN_MESSAGES_CREATED_AT ON admin_messages (created_at DESC)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_PROBLEMS_CREATED_AT ON problems (created_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_PRIVATE_PROBLEMS_CREATED_AT ON private_problems (created_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FEEDBACK_CREATED_AT ON feedback (created_at)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP INDEX IF EXISTS IDX_ADMIN_MESSAGES_CREATED_AT
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IF EXISTS IDX_PROBLEMS_CREATED_AT
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IF EXISTS IDX_PRIVATE_PROBLEMS_CREATED_AT
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IF EXISTS IDX_FEEDBACK_CREATED_AT
        SQL);
    }
} 