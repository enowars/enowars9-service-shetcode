<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250618100241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE admin_messages (id SERIAL NOT NULL, admin_id INT NOT NULL, message TEXT NOT NULL, year INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4FA21990642B8210 ON admin_messages (admin_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE admin_messages ADD CONSTRAINT FK_4FA21990642B8210 FOREIGN KEY (admin_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE admin_messages DROP CONSTRAINT FK_4FA21990642B8210
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE admin_messages
        SQL);
    }
}
