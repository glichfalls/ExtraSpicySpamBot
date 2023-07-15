<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230715005738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chat_config (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', chat_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', passive_honor_enabled TINYINT(1) NOT NULL, passive_honor_amount INT NOT NULL, UNIQUE INDEX UNIQ_B356185D1A9A7125 (chat_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE chat_config ADD CONSTRAINT FK_B356185D1A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('ALTER TABLE chat ADD config_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE chat ADD CONSTRAINT FK_659DF2AA24DB0683 FOREIGN KEY (config_id) REFERENCES chat_config (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_659DF2AA24DB0683 ON chat (config_id)');
        // create chat config for all chats and add chat config id to chat
        $this->addSql('INSERT INTO chat_config (id, chat_id, passive_honor_enabled, passive_honor_amount) SELECT UUID(), id, 0, 0 FROM chat');
        $this->addSql('UPDATE chat SET config_id = (SELECT id FROM chat_config WHERE chat_id = chat.id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat DROP FOREIGN KEY FK_659DF2AA24DB0683');
        $this->addSql('ALTER TABLE chat_config DROP FOREIGN KEY FK_B356185D1A9A7125');
        $this->addSql('DROP TABLE chat_config');
        $this->addSql('DROP INDEX UNIQ_659DF2AA24DB0683 ON chat');
        $this->addSql('ALTER TABLE chat DROP config_id');
    }
}
