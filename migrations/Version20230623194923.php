<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230623194923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sticker (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sticker_file (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', owner_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', sticker LONGTEXT NOT NULL, sticker_format VARCHAR(255) NOT NULL, file_id VARCHAR(255) DEFAULT NULL, file_unique_id VARCHAR(255) DEFAULT NULL, file_size BIGINT DEFAULT NULL, file_path VARCHAR(255) DEFAULT NULL, INDEX IDX_2B9BE4B27E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sticker_set (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', owner_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(64) NOT NULL, title VARCHAR(64) NOT NULL, sticker_format VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_85E0E6385E237E06 (name), INDEX IDX_85E0E6387E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sticker_file ADD CONSTRAINT FK_2B9BE4B27E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sticker_set ADD CONSTRAINT FK_85E0E6387E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sticker_file DROP FOREIGN KEY FK_2B9BE4B27E3C61F9');
        $this->addSql('ALTER TABLE sticker_set DROP FOREIGN KEY FK_85E0E6387E3C61F9');
        $this->addSql('DROP TABLE sticker');
        $this->addSql('DROP TABLE sticker_file');
        $this->addSql('DROP TABLE sticker_set');
    }
}
