<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250428085154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE task ADD CONSTRAINT FK_527EDB25A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_527EDB25A76ED395 ON task (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP username
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user RENAME INDEX uniq_identifier_email TO UNIQ_8D93D649E7927C74
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE task DROP FOREIGN KEY FK_527EDB25A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_527EDB25A76ED395 ON task
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD username VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user RENAME INDEX uniq_8d93d649e7927c74 TO UNIQ_IDENTIFIER_EMAIL
        SQL);
    }
}
