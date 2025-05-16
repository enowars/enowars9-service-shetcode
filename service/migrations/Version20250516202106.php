<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250516202106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE private_access (id SERIAL NOT NULL, problem_id INT DEFAULT NULL, user_id INT DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2B478D85A0DCED86 ON private_access (problem_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2B478D85A76ED395 ON private_access (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE private_problems (id SERIAL NOT NULL, author_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, difficulty VARCHAR(50) NOT NULL, test_cases JSON NOT NULL, expected_outputs JSON NOT NULL, max_runtime DOUBLE PRECISION NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_29F0BDE8F675F31B ON private_problems (author_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE private_access ADD CONSTRAINT FK_2B478D85A0DCED86 FOREIGN KEY (problem_id) REFERENCES private_problems (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE private_access ADD CONSTRAINT FK_2B478D85A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE private_problems ADD CONSTRAINT FK_29F0BDE8F675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE private_access DROP CONSTRAINT FK_2B478D85A0DCED86
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE private_access DROP CONSTRAINT FK_2B478D85A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE private_problems DROP CONSTRAINT FK_29F0BDE8F675F31B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE private_access
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE private_problems
        SQL);
    }
}
