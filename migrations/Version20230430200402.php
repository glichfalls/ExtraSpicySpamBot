<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230430200402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_subscription CHANGE chat_id chat_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE chat_subscription ADD CONSTRAINT FK_5D2D0D521A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('CREATE INDEX IDX_5D2D0D521A9A7125 ON chat_subscription (chat_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_subscription DROP FOREIGN KEY FK_5D2D0D521A9A7125');
        $this->addSql('DROP INDEX IDX_5D2D0D521A9A7125 ON chat_subscription');
        $this->addSql('ALTER TABLE chat_subscription CHANGE chat_id chat_id VARCHAR(255) NOT NULL');
    }
}
