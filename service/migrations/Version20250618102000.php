<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds indexes necessary for faster listing queries on the problems table.
 */
final class Version20250618102000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite index on problems(is_published, author_id) for list/filter queries';
    }

    public function up(Schema $schema): void
    {
        // The combination (is_published, author_id) is used by the list endpoint.
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_PROBLEMS_PUBLISHED_AUTHOR ON problems (is_published, author_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP INDEX IF EXISTS IDX_PROBLEMS_PUBLISHED_AUTHOR
        SQL);
    }
} 