<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250514222804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pgcrypto extension for password hashing';
    }

    public function up(Schema $schema): void
    {
        // Add pgcrypto extension for password encryption functions like gen_salt
        $this->addSql(<<<'SQL'
            CREATE EXTENSION IF NOT EXISTS pgcrypto;
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Remove pgcrypto extension if needed
        $this->addSql(<<<'SQL'
            DROP EXTENSION IF EXISTS pgcrypto;
        SQL);
    }
}
