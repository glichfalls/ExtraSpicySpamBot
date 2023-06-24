<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230624081120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sticker ADD sticker_set_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', ADD file_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', ADD emojis JSON NOT NULL');
        $this->addSql('ALTER TABLE sticker ADD CONSTRAINT FK_8FEDBCFDE417C78F FOREIGN KEY (sticker_set_id) REFERENCES sticker_set (id)');
        $this->addSql('ALTER TABLE sticker ADD CONSTRAINT FK_8FEDBCFD93CB796C FOREIGN KEY (file_id) REFERENCES sticker_file (id)');
        $this->addSql('CREATE INDEX IDX_8FEDBCFDE417C78F ON sticker (sticker_set_id)');
        $this->addSql('CREATE INDEX IDX_8FEDBCFD93CB796C ON sticker (file_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sticker DROP FOREIGN KEY FK_8FEDBCFDE417C78F');
        $this->addSql('ALTER TABLE sticker DROP FOREIGN KEY FK_8FEDBCFD93CB796C');
        $this->addSql('DROP INDEX IDX_8FEDBCFDE417C78F ON sticker');
        $this->addSql('DROP INDEX IDX_8FEDBCFD93CB796C ON sticker');
        $this->addSql('ALTER TABLE sticker DROP sticker_set_id, DROP file_id, DROP emojis');
    }
}
