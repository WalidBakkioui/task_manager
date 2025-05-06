<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231217100746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blog_post ADD photo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE blog_post ADD CONSTRAINT FK_BA5AE01D7E9E4C8C FOREIGN KEY (photo_id) REFERENCES photo (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BA5AE01D7E9E4C8C ON blog_post (photo_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blog_post DROP FOREIGN KEY FK_BA5AE01D7E9E4C8C');
        $this->addSql('DROP INDEX UNIQ_BA5AE01D7E9E4C8C ON blog_post');
        $this->addSql('ALTER TABLE blog_post DROP photo_id');
    }
}
