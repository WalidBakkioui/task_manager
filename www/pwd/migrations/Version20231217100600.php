<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231217100600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B78418A77FBEAF');
        $this->addSql('DROP INDEX IDX_14B78418A77FBEAF ON photo');
        $this->addSql('ALTER TABLE photo DROP blog_post_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE photo ADD blog_post_id INT NOT NULL');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B78418A77FBEAF FOREIGN KEY (blog_post_id) REFERENCES blog_post (id)');
        $this->addSql('CREATE INDEX IDX_14B78418A77FBEAF ON photo (blog_post_id)');
    }
}
