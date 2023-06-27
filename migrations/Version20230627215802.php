<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230627215802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE honor_upgrade (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', chat_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', type_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_5B55FF561A9A7125 (chat_id), INDEX IDX_5B55FF56A76ED395 (user_id), INDEX IDX_5B55FF56C54C8C93 (type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE upgrade_type (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, price INT NOT NULL, UNIQUE INDEX UNIQ_F82810375E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE honor_upgrade ADD CONSTRAINT FK_5B55FF561A9A7125 FOREIGN KEY (chat_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE honor_upgrade ADD CONSTRAINT FK_5B55FF56A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE honor_upgrade ADD CONSTRAINT FK_5B55FF56C54C8C93 FOREIGN KEY (type_id) REFERENCES upgrade_type (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE honor_upgrade DROP FOREIGN KEY FK_5B55FF561A9A7125');
        $this->addSql('ALTER TABLE honor_upgrade DROP FOREIGN KEY FK_5B55FF56A76ED395');
        $this->addSql('ALTER TABLE honor_upgrade DROP FOREIGN KEY FK_5B55FF56C54C8C93');
        $this->addSql('DROP TABLE honor_upgrade');
        $this->addSql('DROP TABLE upgrade_type');
    }
}
