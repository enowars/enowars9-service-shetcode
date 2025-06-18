<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Performance optimization: Add index on users.username for faster lookups
 */
final class Version20250120000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on users.username for performance optimization during high load';
    }

    public function up(Schema $schema): void
    {
        // Add index on username column for faster user lookups
        $this->addSql('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_username ON users (username)');
    }

    public function down(Schema $schema): void
    {
        // Remove the index
        $this->addSql('DROP INDEX IF EXISTS idx_users_username');
    }
} 