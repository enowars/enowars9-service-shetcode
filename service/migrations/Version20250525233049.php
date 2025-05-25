<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250525233049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE private_access ALTER created_at DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE private_problems ALTER created_at DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE problems ADD max_runtime DOUBLE PRECISION NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE problems ALTER created_at DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ALTER created_at DROP DEFAULT
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE problems DROP max_runtime
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE problems ALTER created_at SET DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE private_problems ALTER created_at SET DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE private_access ALTER created_at SET DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ALTER created_at SET DEFAULT CURRENT_TIMESTAMP
        SQL);
    }
}
