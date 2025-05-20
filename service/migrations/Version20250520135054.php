<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds created_at timestamp to entities that need it
 */
final class Version20250520135054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at timestamp to all tables that need it';
    }

    public function up(Schema $schema): void
    {
        // Add timestamps to problems table
        $this->addSql('ALTER TABLE problems ADD created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        
        // Add timestamps to private_problems table
        $this->addSql('ALTER TABLE private_problems ADD created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        
        // Add timestamps to private_access table
        $this->addSql('ALTER TABLE private_access ADD created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        
        // Add timestamps to users table
        $this->addSql('ALTER TABLE users ADD created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // Remove timestamps from problems table
        $this->addSql('ALTER TABLE problems DROP COLUMN created_at');
        
        // Remove timestamps from private_problems table
        $this->addSql('ALTER TABLE private_problems DROP COLUMN created_at');
        
        // Remove timestamps from private_access table
        $this->addSql('ALTER TABLE private_access DROP COLUMN created_at');
        
        // Remove timestamps from users table
        $this->addSql('ALTER TABLE users DROP COLUMN created_at');
    }
}
